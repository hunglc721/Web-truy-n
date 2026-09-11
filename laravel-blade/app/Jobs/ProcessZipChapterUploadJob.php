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
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessZipChapterUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_ARCHIVE_FILES = 1000;
    private const MAX_UNCOMPRESSED_BYTES = 524288000; // 500 MB
    private const MAX_IMAGE_BYTES = 20971520; // 20 MB / ảnh sau giải nén

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        public Comic $comic,
        public Chapter $chapter,
        public string $zipAbsolutePath
    ) {
        $this->onQueue('chapter-images');
    }

    public function handle(ImageService $imageService, ChapterNotificationService $notificationService): void
    {
        $chapter = $this->chapter->fresh();
        if (!$chapter) {
            $this->cleanupZip();
            return;
        }

        $chapter->update(['processing_status' => 'processing']);

        $zip = new ZipArchive();
        if ($zip->open($this->zipAbsolutePath) !== true) {
            $this->markFailed($chapter, 'Không thể mở file ZIP.');
            return;
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        $imageEntries = [];
        $fileCount = 0;
        $totalUncompressedBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if ($stat === false) {
                continue;
            }

            $entryName = (string) ($stat['name'] ?? '');

            if (!$this->isSafeEntryName($entryName)) {
                $zip->close();
                $this->markFailed($chapter, "ZIP chứa đường dẫn không an toàn: {$entryName}");
                return;
            }

            if ($entryName === '' || str_ends_with(str_replace('\\', '/', $entryName), '/')) {
                continue;
            }

            $fileCount++;
            if ($fileCount > self::MAX_ARCHIVE_FILES) {
                $zip->close();
                $this->markFailed($chapter, 'ZIP chứa quá nhiều file. Giới hạn là ' . self::MAX_ARCHIVE_FILES . ' file.');
                return;
            }

            $entrySize = max(0, (int) ($stat['size'] ?? 0));
            $totalUncompressedBytes += $entrySize;
            if ($totalUncompressedBytes > self::MAX_UNCOMPRESSED_BYTES) {
                $zip->close();
                $this->markFailed($chapter, 'Tổng dung lượng sau giải nén vượt quá 500MB.');
                return;
            }

            $normalizedName = str_replace('\\', '/', $entryName);
            if (str_starts_with($normalizedName, '__MACOSX/') || str_contains($normalizedName, '/.DS_Store') || basename($normalizedName) === '.DS_Store') {
                continue;
            }

            $extension = strtolower(pathinfo($normalizedName, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            if ($entrySize > self::MAX_IMAGE_BYTES) {
                $zip->close();
                $this->markFailed($chapter, "Ảnh {$entryName} vượt quá giới hạn 20MB sau giải nén.");
                return;
            }

            $imageEntries[] = [
                'index' => $index,
                'name' => $normalizedName,
                'extension' => $extension,
            ];
        }

        if (empty($imageEntries)) {
            $zip->close();
            $this->markFailed($chapter, 'Không tìm thấy ảnh hợp lệ trong file ZIP.');
            return;
        }

        // Natural sort giúp 1.jpg, 2.jpg, 10.jpg nằm đúng thứ tự, kể cả khi ảnh ở trong thư mục con.
        usort($imageEntries, static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        $finalPageUrls = [];
        $pageDimensions = [];
        $targetFolder = "chapters/{$this->comic->id}/{$this->chapter->id}";

        foreach ($imageEntries as $pageIndex => $entry) {
            $contents = $zip->getFromIndex($entry['index']);
            if ($contents === false) {
                $zip->close();
                $this->markFailed($chapter, "Không thể đọc ảnh {$entry['name']} từ ZIP.");
                return;
            }

            $dimensions = @getimagesizefromstring($contents);
            if ($dimensions === false) {
                $zip->close();
                $this->markFailed($chapter, "File {$entry['name']} không phải ảnh hợp lệ.");
                return;
            }

            $filename = sprintf('%03d.%s', $pageIndex + 1, $entry['extension']);
            $storageRelativePath = "{$targetFolder}/{$filename}";

            Storage::disk('public')->put($storageRelativePath, $contents);

            $finalPageUrls[] = Storage::disk('public')->url($storageRelativePath);
            $pageDimensions[] = [
                'width' => max(1, (int) $dimensions[0]),
                'height' => max(1, (int) $dimensions[1]),
            ];
        }

        $zip->close();
        $this->cleanupZip();

        $chapter->update([
            'pages' => $finalPageUrls,
            'page_dimensions' => $pageDimensions,
            'processing_status' => 'ready',
        ]);

        $notificationService->dispatchIfEligible($chapter);
    }

    private function isSafeEntryName(string $entryName): bool
    {
        if ($entryName === '' || str_contains($entryName, "\0")) {
            return false;
        }

        $normalized = str_replace('\\', '/', $entryName);

        if (str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
            return false;
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function markFailed(Chapter $chapter, string $message): void
    {
        $chapter->update(['processing_status' => 'failed']);
        $this->cleanupZip();
        Log::warning("ProcessZipChapterUploadJob rejected chapter {$chapter->id}: {$message}");
    }

    private function cleanupZip(): void
    {
        if (is_file($this->zipAbsolutePath)) {
            @unlink($this->zipAbsolutePath);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessZipChapterUploadJob failed for chapter {$this->chapter->id}: " . $e->getMessage());
        $this->cleanupZip();
        $this->chapter->update(['processing_status' => 'failed']);
    }
}
