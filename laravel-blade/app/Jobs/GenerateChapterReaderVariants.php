<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Services\ReaderImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateChapterReaderVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(public readonly int $chapterId)
    {
        $this->onQueue(config('reader.queue', 'reader-images'));
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function middleware(): array
    {
        return [(new \Illuminate\Queue\Middleware\WithoutOverlapping('reader-' . $this->chapterId))
            ->releaseAfter(30)->expireAfter(660)];
    }

    public function handle(ReaderImageService $images): void
    {
        $chapter = Chapter::find($this->chapterId);
        if ($chapter) {
            $images->generate($chapter);
        }
    }

    public function failed(\Throwable $error): void
    {
        Log::error('Reader optimization exhausted retries; originals remain readable.', [
            'chapter_id' => $this->chapterId, 'error' => $error->getMessage(),
        ]);
    }
}
