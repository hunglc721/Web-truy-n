<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    protected string $disk;

    public function __construct(string $disk = 'public')
    {
        $this->disk = $disk;
    }

    /**
     * Upload nhiều ảnh cùng lúc (Bulk Upload) vào $folder trên disk.
     * Tôn trọng thứ tự kéo thả từ frontend nếu $orderIndices được truyền vào.
     *
     * @param  UploadedFile[]  $files
     * @param  string          $folder        Thư mục đích: "comics/1/chapters/5"
     * @param  int[]|null      $orderIndices  Thứ tự index mảng $files sau khi kéo thả
     * @return string[]        Mảng đường dẫn tương đối đã lưu
     */
    public function uploadBulk(array $files, string $folder, ?array $orderIndices = null): array
    {
        // Sắp xếp lại theo thứ tự kéo thả (nếu có và hợp lệ)
        if (!empty($orderIndices) && count($orderIndices) === count($files)) {
            $ordered = [];
            foreach ($orderIndices as $idx) {
                if (isset($files[$idx])) {
                    $ordered[] = $files[$idx];
                }
            }
            if (count($ordered) === count($files)) {
                $files = $ordered;
            }
        }

        $paths = [];
        foreach ($files as $idx => $file) {
            $paths[] = $this->uploadSingle($file, $folder, $idx);
        }

        return $paths;
    }

    /**
     * Danh sách MIME type ảnh hợp lệ được phép upload (Kiểm tra Magic Bytes).
     */
    protected array $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/avif',
    ];

    /**
     * Kiểm tra MIME thực tế từ Magic Bytes của file.
     */
    public function validateRealMime(UploadedFile $file): bool
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        return in_array($realMime, $this->allowedMimes, true);
    }

    /**
     * Upload 1 file ảnh, kiểm tra MIME thật, strip EXIF metadata và lưu vào storage.
     *
     * @param  UploadedFile $file
     * @param  string       $folder
     * @param  int          $index   Vị trí trong batch (0-based)
     * @return string       Đường dẫn tương đối
     */
    public function uploadSingle(UploadedFile $file, string $folder, int $index = 0): string
    {
        if (!$this->validateRealMime($file)) {
            throw new \InvalidArgumentException('Tệp tải lên không phải là hình ảnh hợp lệ (Phát hiện MIME không an toàn).');
        }

        $pageNumber = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
        $extension  = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename   = "page_{$pageNumber}_" . Str::random(6) . ".{$extension}";
        $targetPath = "{$folder}/{$filename}";

        // Tự động Strip EXIF Metadata nếu là JPEG/PNG và có GD extension
        if (function_exists('imagecreatefromstring') && in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $content = file_get_contents($file->getRealPath());
            $gdImg = @imagecreatefromstring($content);

            if ($gdImg !== false) {
                ob_start();
                if ($extension === 'png') {
                    imagealphablending($gdImg, false);
                    imagesavealpha($gdImg, true);
                    imagepng($gdImg);
                } else {
                    imagejpeg($gdImg, null, 90);
                }
                $cleanData = ob_get_clean();
                imagedestroy($gdImg);

                Storage::disk($this->disk)->put($targetPath, $cleanData);
                return $targetPath;
            }
        }

        return $file->storeAs($folder, $filename, $this->disk);
    }

    /**
     * Parse textarea chứa danh sách URL ảnh (mỗi dòng 1 URL) thành mảng.
     *
     * @param  string   $raw
     * @return string[]
     */
    public function parseUrlList(string $raw): array
    {
        return array_values(
            array_filter(
                array_map('trim', explode("\n", $raw)),
                fn($url) => !empty($url)
            )
        );
    }

    /**
     * Xóa danh sách file cục bộ (đường dẫn bắt đầu bằng "comics/") khỏi Storage.
     * Bỏ qua file URL ngoài (http://...) để tránh lỗi.
     *
     * @param  string[] $paths
     */
    public function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (str_starts_with($path, 'comics/') && Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }
        }
    }

    /**
     * Xóa toàn bộ thư mục ảnh của một chương.
     *
     * @param  string $folder  Ví dụ: "comics/1/chapters/5"
     */
    public function deleteFolder(string $folder): void
    {
        if (Storage::disk($this->disk)->exists($folder)) {
            Storage::disk($this->disk)->deleteDirectory($folder);
        }
    }

    /**
     * Upload ảnh bìa truyện vào thư mục comics/covers/.
     * Trả về đường dẫn tương đối (dùng với Storage::url()).
     *
     * @param  UploadedFile $file
     * @return string       Đường dẫn tương đối, ví dụ: "comics/covers/abc123.webp"
     */
    public function uploadCover(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename  = Str::random(16) . '.' . $extension;

        return $file->storeAs('comics/covers', $filename, $this->disk);
    }

    /**
     * Trả về đường dẫn thư mục lưu ảnh theo quy ước.
     * comics/{comic_id}/chapters/{chapter_id}
     */
    public function chapterFolder(int $comicId, int $chapterId): string
    {
        return "comics/{$comicId}/chapters/{$chapterId}";
    }
}
