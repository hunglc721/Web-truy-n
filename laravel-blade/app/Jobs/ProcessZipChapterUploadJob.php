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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessZipChapterUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        if (!$chapter) return;

        $chapter->update(['processing_status' => 'processing']);

        $extractPath = storage_path("app/tmp/extracted_{$this->chapter->id}_" . time());
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive();
        if ($zip->open($this->zipAbsolutePath) !== true) {
            $chapter->update(['processing_status' => 'failed']);
            @unlink($this->zipAbsolutePath);
            File::deleteDirectory($extractPath);
            Log::error("Failed to open ZIP file at: {$this->zipAbsolutePath}");
            return;
        }

        $zip->extractTo($extractPath);
        $zip->close();
        @unlink($this->zipAbsolutePath);

        // Quét tìm tất cả các file ảnh hợp lệ trong thư mục vừa giải nén
        $allFiles = File::allFiles($extractPath);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];
        $imageFiles = [];

        foreach ($allFiles as $file) {
            $ext = strtolower($file->getExtension());
            $relPath = $file->getRelativePathname();

            // Bỏ qua file rác hệ thống (macOS __MACOSX, .DS_Store, Windows Thumbs.db)
            if (str_starts_with($relPath, '__MACOSX') || str_contains($relPath, '.DS_Store')) {
                continue;
            }

            if (in_array($ext, $imageExtensions, true)) {
                $imageFiles[] = $file->getRealPath();
            }
        }

        if (empty($imageFiles)) {
            $chapter->update(['processing_status' => 'failed']);
            File::deleteDirectory($extractPath);
            Log::error("No valid images found in ZIP for chapter {$this->chapter->id}");
            return;
        }

        // Sắp xếp tự nhiên theo tên file (natsort) để 1.jpg, 2.jpg, 10.jpg đúng thứ tự
        natsort($imageFiles);
        $imageFiles = array_values($imageFiles);

        $finalPageUrls = [];
        $pageDimensions = [];
        $targetFolder = "chapters/{$this->comic->id}/{$this->chapter->id}";

        foreach ($imageFiles as $index => $filePath) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $filename  = sprintf('%03d.%s', $index + 1, $extension);
            $storageRelativePath = "{$targetFolder}/{$filename}";

            // Lấy kích thước ảnh thực tế để phục vụ aspect-ratio chống CLS layout shift
            $dimensions = @getimagesize($filePath);
            $width = ($dimensions && $dimensions[0] > 0) ? $dimensions[0] : 800;
            $height = ($dimensions && $dimensions[1] > 0) ? $dimensions[1] : 1200;

            Storage::disk('public')->put($storageRelativePath, File::get($filePath));

            $finalPageUrls[] = Storage::disk('public')->url($storageRelativePath);
            $pageDimensions[] = [
                'width'  => $width,
                'height' => $height,
            ];
        }

        // Dọn dẹp thư mục tạm giải nén
        File::deleteDirectory($extractPath);

        $chapter->update([
            'pages'             => $finalPageUrls,
            'page_dimensions'   => $pageDimensions,
            'processing_status' => 'ready',
        ]);

        $notificationService->dispatchIfEligible($chapter);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ProcessZipChapterUploadJob failed for chapter {$this->chapter->id}: " . $e->getMessage());
        $this->chapter->update(['processing_status' => 'failed']);
    }
}
