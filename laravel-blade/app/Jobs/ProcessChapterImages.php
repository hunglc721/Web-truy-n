<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\Comic;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job xử lý upload bulk ảnh cho Chapter bất đồng bộ.
 *
 * Flow:
 *  1. AdminChapterController::store() lưu files vào tmp/
 *  2. Dispatch job này, redirect về admin ngay lập tức
 *  3. Job: move từ tmp/ → comics/{id}/chapters/{id}/, merge URLs, update pages
 *  4. Cập nhật processing_status = 'ready' | 'failed'
 */
class ProcessChapterImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần retry khi job thất bại.
     * (upload thất bại do disk full, network glitch...)
     */
    public int $tries = 3;

    /**
     * Timeout tối đa (giây) — 100 ảnh * ~3s = ~300s
     */
    public int $timeout = 300;

    /**
     * Tên queue riêng biệt để không block queue chính
     */
    public string $queue = 'chapter-images';

    public function __construct(
        public readonly Comic   $comic,
        public readonly Chapter $chapter,
        public readonly array   $tmpPaths,  // Đường dẫn tmp/ trên disk 'public'
        public readonly array   $urlList = [], // URL list từ textarea
    ) {}

    public function handle(ImageService $imageService): void
    {
        $this->chapter->update(['processing_status' => 'processing']);

        try {
            $folder     = $imageService->chapterFolder($this->comic->id, $this->chapter->id);
            $finalPages = [];

            // Di chuyển từng file từ tmp/ sang thư mục chính thức
            foreach ($this->tmpPaths as $idx => $tmpPath) {
                $filename  = basename($tmpPath);
                $destPath  = "{$folder}/{$filename}";

                // Đọc nội dung từ tmp rồi lưu vào dest
                $content = \Storage::disk('public')->get($tmpPath);
                \Storage::disk('public')->put($destPath, $content);
                \Storage::disk('public')->delete($tmpPath);

                $finalPages[] = $destPath;
            }

            // Merge URL list
            $finalPages = array_merge($finalPages, $this->urlList);

            $this->chapter->update([
                'pages'             => $finalPages,
                'processing_status' => 'ready',
            ]);

        } catch (\Throwable $e) {
            $this->chapter->update(['processing_status' => 'failed']);

            Log::channel('queue')->error('ProcessChapterImages failed', [
                'chapter_id' => $this->chapter->id,
                'comic_id'   => $this->comic->id,
                'error'      => $e->getMessage(),
                'attempt'    => $this->attempts(),
                'max_tries'  => $this->tries,
            ]);

            throw $e; // Cho phép retry
        }
    }

    /**
     * Gọi khi job thất bại sau tất cả các lần retry.
     */
    public function failed(\Throwable $e): void
    {
        $this->chapter->update(['processing_status' => 'failed']);

        Log::channel('queue')->critical('ProcessChapterImages permanently failed', [
            'chapter_id' => $this->chapter->id,
            'comic_id'   => $this->comic->id,
            'error'      => $e->getMessage(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
        ]);
    }
}
