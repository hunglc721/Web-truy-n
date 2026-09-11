<?php

namespace App\Console\Commands;

use App\Models\Chapter;
use App\Services\ReaderImageService;
use Illuminate\Console\Command;

class GenerateReaderVariantsCommand extends Command
{
    protected $signature = 'reader:variants {chapter? : Chapter ID; omit to queue all chapters}';
    protected $description = 'Queue non-destructive reader image variants, including for existing chapters';

    public function handle(ReaderImageService $images): int
    {
        Chapter::query()->when($this->argument('chapter'), fn ($q, $id) => $q->whereKey($id))
            ->select('id')->chunkById(100, function ($chapters) use ($images) {
                foreach ($chapters as $chapter) {
                    $images->enqueue($chapter->id);
                }
            });
        $this->info('Queue dispatch attempted. Check application logs for unavailable queues.');
        return self::SUCCESS;
    }
}
