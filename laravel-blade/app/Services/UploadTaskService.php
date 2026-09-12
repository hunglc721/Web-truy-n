<?php

namespace App\Services;

use App\Models\{Chapter, Comic, UploadTask, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadTaskService
{
    public function __construct(private BulkChapterUploadService $bulk) {}

    public function create(User $user, Comic $comic, array $manifest, string $conflictMode): UploadTask
    {
        return DB::transaction(function () use ($user, $comic, $manifest, $conflictMode) {
            // Serialize creation across tabs, even when no task row exists yet.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = UploadTask::where('user_id', $user->id)->whereNotIn('status', ['completed', 'cancelled'])->first();
            if ($existing) return $existing;
            $conflicts = $this->bulk->checkExistingChapters($comic, array_column($manifest, 'number'));
            foreach ($manifest as &$chapter) {
                $chapter['number'] = Chapter::formatNumber($chapter['number']);
                $chapter['skipped'] = isset($conflicts[$chapter['number']]);
                if ($chapter['skipped'] && $conflictMode === 'stop') {
                    throw ValidationException::withMessages(['chapters' => 'Chapter đã tồn tại.']);
                }
            }
            unset($chapter);
            if (count(array_unique(array_column($manifest, 'number'))) !== count($manifest)) {
                throw ValidationException::withMessages(['chapters' => 'Số chapter bị trùng sau chuẩn hóa.']);
            }
            $uploadable = array_filter($manifest, fn ($ch) => !$ch['skipped']);
            foreach ($uploadable as $chapter) {
                foreach ($chapter['files'] as $file) {
                    if ($file['size'] <= 0 || $file['size'] > BulkChapterUploadService::MAX_IMAGE_BYTES) {
                        throw ValidationException::withMessages(['chapters' => 'Mỗi ảnh upload phải từ 1 byte đến 20MB.']);
                    }
                }
            }
            $files = array_sum(array_map(fn ($ch) => count($ch['files']), $uploadable));
            $bytes = array_sum(array_map(fn ($ch) => array_sum(array_column($ch['files'], 'size')), $uploadable));
            if ($files > BulkChapterUploadService::MAX_SESSION_FILES || $bytes > BulkChapterUploadService::MAX_SESSION_BYTES) {
                throw ValidationException::withMessages(['chapters' => 'Vượt giới hạn phiên upload.']);
            }
            $session = $this->bulk->start($comic, $user);
            return UploadTask::create([
                'id' => (string) Str::uuid(), 'user_id' => $user->id, 'comic_id' => $comic->id,
                'upload_session_id' => $session['session'], 'manifest' => $manifest,
                'total_chapters' => count($manifest), 'uploadable_chapters' => count($uploadable),
                'skipped_chapters' => count($manifest) - count($uploadable), 'total_files' => $files,
                'total_bytes' => $bytes, 'started_at' => now(), 'expires_at' => now()->addDay(),
            ]);
        });
    }

    public function snapshot(UploadTask $task): array
    {
        // Derive stale state on reads, without requiring a scheduler or holding a stream open.
        UploadTask::whereKey($task->id)->whereIn('status', ['preparing', 'uploading', 'processing'])
            ->where(fn ($q) => $q->where('last_heartbeat_at', '<', now()->subSeconds(30))
                ->orWhere(fn ($q) => $q->whereNull('last_heartbeat_at')->where('created_at', '<', now()->subSeconds(30))))
            ->update(['status' => 'waiting_for_client', 'version' => DB::raw('version + 1')]);
        $task->refresh()->load('comic:id,title,slug');
        $data = $task->toArray();
        unset($data['manifest']);
        $data['worker_alive'] = $task->last_heartbeat_at?->gt(now()->subSeconds(30)) && $task->worker_id !== null;
        $data['uploader_url'] = route('admin.comics.chapters.create', $task->comic_id);
        return $data;
    }

    public function control(UploadTask $task, string $action, string $workerId, array $error = []): array
    {
        return DB::transaction(function () use ($task, $action, $workerId, $error) {
            $task = UploadTask::whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($action === 'cancel') {
                if ($task->status !== 'completed') $task->update(['status' => 'cancelled', 'worker_id' => null]);
                return $this->snapshot($task);
            }
            abort_if(in_array($task->status, ['completed', 'cancelled']), 409, 'Task đã kết thúc.');
            abort_if($task->expires_at->isPast(), 409, 'Phiên đã hết hạn; hủy task rồi chọn lại folder.');
            if ($action === 'claim') {
                abort_if($task->worker_id && $task->worker_id !== $workerId && $task->last_heartbeat_at?->gt(now()->subSeconds(30)), 409, 'Upload worker khác đang hoạt động.');
                $task->update(['worker_id' => $workerId, 'last_heartbeat_at' => now(), 'status' => 'uploading', 'error_message' => null, 'error_context' => null]);
                return $this->snapshot($task) + ['manifest' => $task->manifest, 'session_state' => $this->bulk->snapshot($task->comic, $task->user, $task->upload_session_id, true)];
            }
            abort_unless($task->worker_id === $workerId, 409, 'Worker không còn quyền upload.');
            $task->last_heartbeat_at = now();
            if ($action === 'error') {
                $task->status = 'failed';
                $task->error_message = $error['message'] ?? 'Upload lỗi';
                $task->error_context = $error;
            } elseif ($action === 'release') {
                $task->status = 'waiting_for_client';
                $task->worker_id = null;
            } elseif ($task->status === 'waiting_for_client') {
                $task->status = 'uploading';
            }
            $task->save();
            return $this->snapshot($task);
        });
    }

    public function upload(UploadTask $task, Request $request): array
    {
        return DB::transaction(function () use ($task, $request) {
            $task = UploadTask::whereKey($task->id)->lockForUpdate()->firstOrFail();
            $action = $request->string('bulk_action')->toString();
            abort_unless($task->worker_id === $request->input('worker_id'), 409, 'Worker không còn quyền upload.');
            if ($action === 'complete' && $task->status === 'completed') return $this->snapshot($task);
            abort_unless(in_array($task->status, ['uploading', 'processing']), 409, 'Task không đang upload.');
            abort_if($task->expires_at->isPast(), 409, 'Phiên upload đã hết hạn.');
            abort_unless($request->input('session') === $task->upload_session_id, 422);
            $manifest = $task->manifest;
            $index = array_search($request->input('chapter_key'), array_column($manifest, 'key'), true);
            if ($action !== 'complete') {
                abort_if($index === false || $manifest[$index]['skipped'], 422, 'Chapter không thuộc task.');
                $chapter = $manifest[$index];
                $task->current_chapter_number = $chapter['number'];
                $task->current_chapter_index = $index + 1;
                $task->current_batch = (int) $request->input('current_batch', 1);
                $task->total_batches = (int) $request->input('total_batches', 1);
            }
            if ($action === 'chunk') {
                foreach ($request->input('page_indexes') as $i => $page) {
                    abort_unless(isset($chapter['files'][$page]) && $request->file('files')[$i]->getSize() === $chapter['files'][$page]['size'], 422, 'Folder không khớp task.');
                }
                $this->bulk->storeChunk($task->comic, $task->user, $task->upload_session_id, $chapter['key'], $request->file('files'), $request->input('page_indexes'), $request->input('checksums'));
            } elseif ($action === 'finalize') {
                $task->status = 'processing';
                $this->bulk->finalizeChapter($task->comic, $task->user, $task->upload_session_id, $chapter['key'], $chapter['number'], $chapter['title'] ?? null, count($chapter['files']));
            } elseif ($action !== 'complete') {
                abort(422);
            }
            // Re-read durable session counters: retries and finalize retries cannot double count.
            $state = $this->bulk->snapshot($task->comic, $task->user, $task->upload_session_id);
            $task->uploaded_bytes = $state['bytes_received'];
            $task->uploaded_files = $state['files_received'];
            $task->completed_chapters = count($state['finalized']);
            $task->last_heartbeat_at = now();
            $task->status = 'uploading';
            if ($action === 'complete') {
                abort_unless($task->completed_chapters === $task->uploadable_chapters, 422, 'Chưa finalize đủ chapter.');
                $task->status = 'completed';
                $task->completed_at = now();
                $task->user->notifications()->firstOrCreate(['id' => $task->id], [
                    'type' => 'bulk_upload_completed',
                    'data' => ['type' => 'bulk_upload_completed', 'title' => 'Upload hoàn tất',
                        'message' => $task->comic->title . ' đã upload thành công ' . $task->completed_chapters . ' chapter.',
                        'url' => route('admin.comics.chapters.index', $task->comic_id), 'icon' => '✅', 'task_id' => $task->id,
                        'chapter_number' => $task->current_chapter_number],
                ]);
                // Keep session receipt until its normal TTL so completion retry is recoverable.
            }
            $task->save();
            return $this->snapshot($task);
        });
    }
}
