<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChapterRequest;
use App\Models\{Comic, UploadTask};
use App\Services\UploadTaskService;
use Illuminate\Http\Request;

class UploadTaskController extends Controller
{
    public function __construct(private UploadTaskService $tasks) {}

    private function authorizeTask(Request $request, ?UploadTask $task = null): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
        if ($task) abort_unless($task->user_id === $request->user()->id, 403);
    }

    public function worker(Request $request)
    {
        $this->authorizeTask($request);
        return response()->view('admin.upload-worker')->header('Cache-Control', 'no-store');
    }

    public function active(Request $request)
    {
        $this->authorizeTask($request);
        $task = UploadTask::where('user_id', $request->user()->id)
            ->when($request->filled('comic_id'), fn ($q) => $q->where('comic_id', $request->input('comic_id')))
            ->whereNotIn('status', ['completed', 'cancelled'])->latest()->first();
        return response()->json(['task' => $task ? $this->tasks->snapshot($task) : null])->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, UploadTask $task)
    {
        $this->authorizeTask($request, $task);
        return response()->json(['task' => $this->tasks->snapshot($task)])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        $this->authorizeTask($request);
        $data = $request->validate([
            'comic_id' => 'required|integer|exists:comics,id', 'conflict_mode' => 'required|in:skip,stop',
            'chapters' => 'required|array|min:1|max:500',
            'chapters.*.key' => ['required', 'string', 'distinct', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'chapters.*.number' => ['required', 'string', 'distinct', 'regex:/^(?=.{1,32}$)\d+(?:\.\d+)?$/', 'numeric', 'max:9999999999'],
            'chapters.*.title' => 'nullable|string|max:255',
            'chapters.*.files' => 'required|array|min:1|max:2000',
            'chapters.*.files.*.name' => 'required|string|max:255',
            'chapters.*.files.*.size' => 'required|integer|min:0',
        ]);
        $task = $this->tasks->create($request->user(), Comic::findOrFail($data['comic_id']), $data['chapters'], $data['conflict_mode']);
        return response()->json(['task' => $this->tasks->snapshot($task)]);
    }

    public function control(Request $request, UploadTask $task)
    {
        $this->authorizeTask($request, $task);
        $data = $request->validate([
            'action' => 'required|in:claim,heartbeat,release,error,cancel',
            'worker_id' => 'required_unless:action,cancel|uuid',
            'error' => 'nullable|array', 'error.message' => 'required_with:error|string|max:2000',
            'error.httpStatus' => 'nullable|integer', 'error.chapterNumber' => 'nullable|string|max:32',
            'error.chapterIndex' => 'nullable|integer', 'error.batchIndex' => 'nullable|integer',
            'error.totalBatches' => 'nullable|integer', 'error.fileNames' => 'nullable|array|max:20',
            'error.fileNames.*' => 'string|max:255', 'error.pageIndexes' => 'nullable|array|max:20',
            'error.pageIndexes.*' => 'integer|min:0|max:1999',
        ]);
        return response()->json(['task' => $this->tasks->control($task, $data['action'], $data['worker_id'] ?? '', $data['error'] ?? [])]);
    }

    public function upload(StoreChapterRequest $request, UploadTask $task)
    {
        $this->authorizeTask($request, $task);
        $request->validate(['worker_id' => 'required|uuid', 'current_batch' => 'nullable|integer|min:1|max:2000', 'total_batches' => 'nullable|integer|min:1|max:2000']);
        return response()->json(['task' => $this->tasks->upload($task, $request)]);
    }
}
