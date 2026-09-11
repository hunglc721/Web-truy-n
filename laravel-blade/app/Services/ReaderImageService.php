<?php

namespace App\Services;

use App\Jobs\GenerateChapterReaderVariants;
use App\Models\Chapter;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RuntimeException;
use Throwable;

class ReaderImageService
{
    public const WIDTHS = [480, 800, 1200];

    public function enqueue(int $chapterId): void
    {
        try {
            if (!config('reader.enabled', true)) return;
            $connection = config('reader.connection', 'reader-images');
            if (config("queue.connections.{$connection}.driver") === 'sync') {
                throw new RuntimeException('Reader variants require an asynchronous queue connection.');
            }
            Bus::dispatch((new GenerateChapterReaderVariants($chapterId))
                ->onConnection($connection)->afterCommit());
        } catch (Throwable $e) {
            Log::warning('Reader variants were not queued; originals remain available. Run reader:variants to retry.', [
                'chapter_id' => $chapterId, 'error' => $e->getMessage(),
            ]);
        }
    }

    /** Resolve only our local public storage, never fetch remote URLs or follow traversal. */
    public function localPath(string $value): ?string
    {
        if (!Storage::disk('public')->getAdapter() instanceof LocalFilesystemAdapter) {
            return null;
        }
        if (preg_match('~^(https?:)?//~i', $value)) {
            $base = rtrim((string) Storage::disk('public')->url(''), '/');
            if (!str_starts_with($base, 'http') || !str_starts_with($value, $base . '/')) {
                return null;
            }
            $value = substr($value, strlen($base) + 1);
        } elseif (str_starts_with($value, '/storage/')) {
            $value = substr($value, 9);
        }
        $value = rawurldecode($value);
        if ($value === '' || preg_match('~[\\\\\x00-\x1f:?#]~', $value)
            || str_starts_with($value, '/') || in_array('..', explode('/', $value), true)
            || in_array('.', explode('/', $value), true) || str_contains($value, '/reader/')) {
            return null;
        }
        $root = realpath(Storage::disk('public')->path(''));
        $file = realpath(Storage::disk('public')->path($value));
        if (!$root || !$file || !is_file($file)
            || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $value;
    }

    public function pages(Chapter $chapter): array
    {
        $manifest = config('reader.enabled', true) ? $this->manifest($chapter->id) : [];
        return array_map(function (array $page) use ($manifest) {
            $page['srcset'] = '';
            $page['variants'] = [];
            $path = $this->localPath($page['path']);
            if (!$path) {
                return $page;
            }
            $page['url'] = Storage::disk('public')->url($path);
            $entry = $manifest[$path] ?? null;
            $absolute = Storage::disk('public')->path($path);
            if (!$entry || ($entry['size'] ?? null) !== filesize($absolute)
                || ($entry['mtime'] ?? null) !== filemtime($absolute)) {
                return $page;
            }
            $page['width'] = $entry['width'];
            $page['height'] = $entry['height'];
            foreach ($entry['variants'] as $variant) {
                if (Storage::disk('public')->exists($variant['path'])) {
                    $page['variants'][] = $variant + ['url' => Storage::disk('public')->url($variant['path'])];
                }
            }
            // Original is a fallback, not a high-DPR candidate: mobile must never select a huge original.
            $page['srcset'] = implode(', ', array_map(
                fn ($v) => $v['url'] . ' ' . $v['width'] . 'w', $page['variants']
            ));
            return $page;
        }, $chapter->pages_with_dimensions);
    }

    public function supported(): bool
    {
        return config('reader.enabled', true) && function_exists('imagewebp')
            && function_exists('imagecreatefromstring') && (imagetypes() & IMG_WEBP);
    }

    public function generate(Chapter $chapter): void
    {
        if (!$this->supported() || !Storage::disk('public')->getAdapter() instanceof LocalFilesystemAdapter) {
            Log::notice('Reader optimization unavailable; using originals.', ['chapter_id' => $chapter->id]);
            return;
        }
        $manifest = $this->manifest($chapter->id);
        $errors = [];
        foreach ($chapter->pages_with_dimensions as $page) {
            $path = $this->localPath($page['path']);
            if (!$path) {
                continue;
            }
            try {
                $entry = $this->generatePage($path);
                if ($entry) {
                    $manifest[$path] = $entry;
                }
            } catch (Throwable $e) {
                $errors[] = $path;
                Log::warning('Reader variant generation failed for page.', [
                    'chapter_id' => $chapter->id, 'path' => $path, 'error' => $e->getMessage(),
                ]);
            }
        }
        // Publish only complete page metadata; a failed page never invalidates another page.
        $file = $this->manifestPath($chapter->id);
        File::ensureDirectoryExists(dirname($file));
        File::replace($file, json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        if ($errors) {
            throw new RuntimeException('Reader variants need retry for ' . count($errors) . ' page(s).');
        }
    }

    private function generatePage(string $path): ?array
    {
        $disk = Storage::disk('public');
        $original = $disk->path($path);
        $info = @getimagesize($original);
        if (!$info) {
            throw new RuntimeException('Original image cannot be decoded.');
        }
        // Keep potentially animated GIF/AVIF/WebP as originals; GD would silently flatten animation.
        if (!in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
            return null;
        }
        [$width, $height] = $info;
        // Browser auto-orientation/animation must not change when switching from original to variant.
        $header = file_get_contents($original, false, null, 0, 65536);
        if ($info['mime'] === 'image/jpeg' && str_contains($header, "Exif\0\0")) {
            $exif = function_exists('exif_read_data') ? @exif_read_data($original) : false;
            if (!$exif || (int) ($exif['Orientation'] ?? 1) !== 1) return null;
        }
        if ($info['mime'] === 'image/png' && str_contains($header, 'acTL')) return null;
        if ($width * $height > config('reader.max_pixels', 24000000)) {
            throw new RuntimeException('Image exceeds safe GD pixel budget; original retained.');
        }
        $limit = ini_parse_quantity(ini_get('memory_limit'));
        $estimated = $width * $height * 10 + filesize($original) * 2 + 16 * 1024 * 1024;
        if ($limit > 0 && $estimated + memory_get_usage(true) > $limit) {
            throw new RuntimeException('Insufficient GD memory budget; original retained.');
        }
        $hash = hash_file('sha256', $original);
        $entry = ['width' => $width, 'height' => $height, 'size' => filesize($original),
            'mtime' => filemtime($original), 'sha256' => $hash, 'variants' => []];
        $source = null;
        try {
            foreach (self::WIDTHS as $target) {
                if ($target > $width) {
                    continue;
                }
                $targetHeight = max(1, (int) round($height * $target / $width));
                $relative = dirname($path) . '/reader/v1-q' . config('reader.quality', 88) . '/' . $hash
                    . '/' . $target . '/' . basename($path) . '.webp';
                $output = $disk->path($relative);
                if (!$this->validVariant($output, $target, $targetHeight)) {
                    $source ??= @imagecreatefromstring(file_get_contents($original));
                    if (!$source) {
                        throw new RuntimeException('GD could not decode original.');
                    }
                    File::ensureDirectoryExists(dirname($output));
                    $temp = $output . '.' . bin2hex(random_bytes(6)) . '.tmp';
                    $image = imagecreatetruecolor($target, $targetHeight);
                    try {
                        imagealphablending($image, false);
                        imagesavealpha($image, true);
                        imagecopyresampled($image, $source, 0, 0, 0, 0, $target, $targetHeight, $width, $height);
                        if (!imagewebp($image, $temp, config('reader.quality', 88))
                            || !$this->validVariant($temp, $target, $targetHeight)) {
                            throw new RuntimeException('WebP encoding failed.');
                        }
                        if (!rename($temp, $output)) {
                            throw new RuntimeException('Could not publish WebP variant.');
                        }
                    } finally {
                        imagedestroy($image);
                        if (is_file($temp)) {
                            unlink($temp);
                        }
                    }
                }
                $entry['variants'][] = ['path' => $relative, 'width' => $target, 'height' => $targetHeight];
            }
        } finally {
            if ($source) {
                imagedestroy($source);
            }
        }
        return $entry;
    }

    private function validVariant(string $path, int $width, int $height): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $info = @getimagesize($path);
        if (!$info || $info[0] !== $width || $info[1] !== $height || $info['mime'] !== 'image/webp') {
            return false;
        }
        $decoded = @imagecreatefromwebp($path);
        if (!$decoded) {
            return false;
        }
        imagedestroy($decoded);
        return true;
    }

    private function manifestPath(int $chapterId): string
    {
        // Keep metadata beside the local storage root so Storage::fake isolates it in tests too.
        return Storage::disk('public')->path('.reader-manifests/' . $chapterId . '.json');
    }

    private function manifest(int $chapterId): array
    {
        if (!Storage::disk('public')->getAdapter() instanceof LocalFilesystemAdapter) {
            return [];
        }
        $file = $this->manifestPath($chapterId);
        $data = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        return is_array($data) ? $data : [];
    }
}
