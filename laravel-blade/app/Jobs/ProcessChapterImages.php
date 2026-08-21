<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\Comic;
use App\Services\ChapterNotificationService;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessChapterImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly Comic   $comic,
        public readonly Chapter $chapter,
        public readonly array   $tmpPaths,
        public readonly array   $urlList = [],
    ) {
        $this->onQueue('chapter-images');
    }

    public function handle(ImageService $imageService, ChapterNotificationService $notificationService): void
    {
        $this->chapter->update(['processing_status' => 'processing']);

        try {
            $folder          = $imageService->chapterFolder($this->comic->id, $this->chapter->id);
            $finalPages      = [];
            $pageDimensions  = [];

            foreach ($this->tmpPaths as $idx => $tmpPath) {
                $filename  = basename($tmpPath);
                $destPath  = "{$folder}/{$filename}";

                $content = \Storage::disk('public')->get($tmpPath);
                \Storage::disk('public')->put($destPath, $content);
                \Storage::disk('public')->delete($tmpPath);

                $dimensions = @getimagesizefromstring($content);
                if (!$dimensions) {
                    $fullPath = \Storage::disk('public')->path($destPath);
                    $dimensions = @getimagesize($fullPath);
                }
                $width  = $dimensions[0] ?? 800;
                $height = $dimensions[1] ?? 1200;

                $finalPages[]     = $destPath;
                $pageDimensions[] = [
                    'width'  => (int) $width,
                    'height' => (int) $height,
                ];
            }

            foreach ($this->urlList as $url) {
                $finalPages[]     = $url;
                $pageDimensions[] = [
                    'width'  => 800,
                    'height' => 1200,
                ];
            }

            $this->chapter->update([
                'pages'             => $finalPages,
                'page_dimensions'   => $pageDimensions,
                'processing_status' => 'ready',
            ]);

            $notificationService->dispatchIfEligible($this->chapter);
        } catch (\Throwable $e) {
            $this->chapter->update(['processing_status' => 'failed']);

            Log::channel('queue')->error('ProcessChapterImages failed', [
                'chapter_id' => $this->chapter->id,
                'comic_id'   => $this->comic->id,
                'error'      => $e->getMessage(),
                'attempt'    => $this->attempts(),
                'max_tries'  => $this->tries,
            ]);

            throw $e;
        }
    }

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
