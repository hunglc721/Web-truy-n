<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class BulkChapterUploadService
{
    public const MAX_IMAGE_BYTES = 20 * 1024 * 1024;
    public const MAX_SESSION_BYTES = 20 * 1024 * 1024 * 1024;
    public const MAX_SESSION_FILES = 20000;
    public const MAX_PAGES_PER_CHAPTER = 2000;
    public const SESSION_TTL_SECONDS = 86400;

    private string $rootPath;

    private array $mimeExtensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
    ];

    public function __construct(
        protected ChapterService $chapterService,
        protected ChapterNotificationService $notificationService,
    ) {
        $this->rootPath = storage_path('app/bulk-chapter-uploads');
    }

    public function start(Comic $comic, User $user): array
    {
        $this->cleanupExpiredSessions();
        File::ensureDirectoryExists($this->rootPath);

        $session = (string) Str::uuid();
        $sessionDir = $this->sessionDir($session);
        File::ensureDirectoryExists($sessionDir);

        $state = [
            'session' => $session,
            'comic_id' => $comic->id,
            'user_id' => $user->id,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'bytes_received' => 0,
            'files_received' => 0,
            'finalized' => [],
        ];

        $this->writeJson($this->sessionStatePath($session), $state);

        return [
            'session' => $session,
            'max_image_bytes' => self::MAX_IMAGE_BYTES,
            'max_session_bytes' => self::MAX_SESSION_BYTES,
            'max_session_files' => self::MAX_SESSION_FILES,
            'expires_in_seconds' => self::SESSION_TTL_SECONDS,
        ];
    }

    /**
     * @param UploadedFile[] $files
     * @param int[] $pageIndexes
     * @param string[] $checksums
     */
    public function storeChunk(
        Comic $comic,
        User $user,
        string $session,
        string $chapterKey,
        array $files,
        array $pageIndexes,
        array $checksums,
    ): array {
        $this->assertChapterKey($chapterKey);

        if (count($files) !== count($pageIndexes) || count($files) !== count($checksums)) {
            throw ValidationException::withMessages([
                'files' => 'Số file, vị trí trang và checksum trong batch không khớp nhau.',
            ]);
        }

        if (count(array_unique(array_map('intval', $pageIndexes))) !== count($pageIndexes)) {
            throw ValidationException::withMessages([
                'page_indexes' => 'Batch có vị trí trang bị trùng.',
            ]);
        }

        return $this->withSessionLock($session, function () use ($comic, $user, $session, $chapterKey, $files, $pageIndexes, $checksums) {
            $state = $this->loadAuthorizedSession($session, $comic, $user);
            $manifestPath = $this->chapterManifestPath($session, $chapterKey);
            $manifest = is_file($manifestPath)
                ? $this->readJson($manifestPath)
                : ['pages' => []];

            $validated = [];
            $deltaBytes = 0;
            $deltaFiles = 0;

            foreach ($files as $offset => $file) {
                if (!$file instanceof UploadedFile || !$file->isValid()) {
                    throw ValidationException::withMessages([
                        'files' => 'Có file upload không hợp lệ hoặc upload chưa hoàn tất.',
                    ]);
                }

                $index = (int) $pageIndexes[$offset];
                if ($index < 0 || $index >= self::MAX_PAGES_PER_CHAPTER) {
                    throw ValidationException::withMessages([
                        'page_indexes' => 'Vị trí trang vượt giới hạn cho phép.',
                    ]);
                }

                $size = (int) ($file->getSize() ?: 0);
                if ($size <= 0 || $size > self::MAX_IMAGE_BYTES) {
                    throw ValidationException::withMessages([
                        'files' => 'Mỗi ảnh phải lớn hơn 0 byte và không vượt quá 20MB.',
                    ]);
                }

                $expectedHash = strtolower((string) $checksums[$offset]);
                if (preg_match('/^[a-f0-9]{64}$/', $expectedHash) !== 1) {
                    throw ValidationException::withMessages([
                        'checksums' => 'Checksum SHA-256 không hợp lệ.',
                    ]);
                }

                $sourcePath = $file->getRealPath();
                $actualHash = hash_file('sha256', $sourcePath);
                if (!is_string($actualHash) || !hash_equals($expectedHash, $actualHash)) {
                    throw ValidationException::withMessages([
                        'checksums' => 'Checksum không khớp. File có thể đã bị lỗi trong lúc truyền, hệ thống không lưu file này.',
                    ]);
                }

                [$extension, $width, $height] = $this->inspectImage($sourcePath);
                $existing = $manifest['pages'][(string) $index] ?? null;
                $existingSize = is_array($existing) ? (int) ($existing['size'] ?? 0) : 0;

                $deltaBytes += $size - $existingSize;
                if ($existing === null) {
                    $deltaFiles++;
                }

                $validated[] = [
                    'index' => $index,
                    'size' => $size,
                    'sha256' => $actualHash,
                    'source_path' => $sourcePath,
                    'extension' => $extension,
                    'width' => $width,
                    'height' => $height,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    'existing' => $existing,
                ];
            }

            $newTotalBytes = max(0, (int) ($state['bytes_received'] ?? 0) + $deltaBytes);
            $newTotalFiles = max(0, (int) ($state['files_received'] ?? 0) + $deltaFiles);

            if ($newTotalBytes > self::MAX_SESSION_BYTES) {
                throw ValidationException::withMessages([
                    'files' => 'Phiên upload vượt quá giới hạn an toàn 20GB.',
                ]);
            }

            if ($newTotalFiles > self::MAX_SESSION_FILES) {
                throw ValidationException::withMessages([
                    'files' => 'Phiên upload vượt quá giới hạn 20.000 ảnh.',
                ]);
            }

            $pagesDir = $this->chapterPagesDir($session, $chapterKey);
            File::ensureDirectoryExists($pagesDir);

            foreach ($validated as $page) {
                $destination = $pagesDir . '/' . sprintf('%06d', $page['index']) . '.' . $page['extension'];
                $temporary = $destination . '.uploading-' . Str::random(8);

                if (!@copy($page['source_path'], $temporary)) {
                    @unlink($temporary);
                    throw new RuntimeException('Không thể ghi file ảnh tạm lên máy chủ.');
                }

                $storedHash = hash_file('sha256', $temporary);
                if (!is_string($storedHash) || !hash_equals($page['sha256'], $storedHash)) {
                    @unlink($temporary);
                    throw ValidationException::withMessages([
                        'files' => 'Checksum sau khi ghi file không khớp. File đã bị loại bỏ để tránh ảnh hỏng.',
                    ]);
                }

                $oldPath = is_array($page['existing']) ? ($page['existing']['path'] ?? null) : null;
                if (is_string($oldPath) && $oldPath !== $destination && is_file($oldPath)) {
                    @unlink($oldPath);
                }

                if (is_file($destination)) {
                    @unlink($destination);
                }

                if (!@rename($temporary, $destination)) {
                    @unlink($temporary);
                    throw new RuntimeException('Không thể chốt file ảnh đã upload.');
                }

                $manifest['pages'][(string) $page['index']] = [
                    'index' => $page['index'],
                    'path' => $destination,
                    'sha256' => $page['sha256'],
                    'size' => $page['size'],
                    'extension' => $page['extension'],
                    'width' => $page['width'],
                    'height' => $page['height'],
                    'original_name' => $page['original_name'],
                ];
            }

            $this->writeJson($manifestPath, $manifest);

            $state['bytes_received'] = $newTotalBytes;
            $state['files_received'] = $newTotalFiles;
            $state['updated_at'] = now()->toIso8601String();
            $this->writeJson($this->sessionStatePath($session), $state);

            return [
                'accepted' => count($validated),
                'chapter_uploaded_pages' => count($manifest['pages']),
                'session_bytes_received' => $newTotalBytes,
                'session_files_received' => $newTotalFiles,
            ];
        });
    }

    public function finalizeChapter(
        Comic $comic,
        User $user,
        string $session,
        string $chapterKey,
        int $chapterNumber,
        ?string $title,
        int $pageCount,
    ): array {
        $this->assertChapterKey($chapterKey);

        if ($pageCount < 1 || $pageCount > self::MAX_PAGES_PER_CHAPTER) {
            throw ValidationException::withMessages([
                'page_count' => 'Số trang của chapter không hợp lệ.',
            ]);
        }

        return $this->withSessionLock($session, function () use ($comic, $user, $session, $chapterKey, $chapterNumber, $title, $pageCount) {
            $state = $this->loadAuthorizedSession($session, $comic, $user);

            $finalizedId = $state['finalized'][$chapterKey] ?? null;
            if ($finalizedId) {
                $existing = Chapter::query()->find($finalizedId);
                if ($existing) {
                    return [
                        'chapter_id' => $existing->id,
                        'chapter_number' => $existing->chapter_number,
                        'pages' => count($existing->pages ?? []),
                        'already_finalized' => true,
                    ];
                }
            }

            $manifestPath = $this->chapterManifestPath($session, $chapterKey);
            if (!is_file($manifestPath)) {
                throw ValidationException::withMessages([
                    'chapter_key' => 'Chưa có ảnh nào được upload cho chapter này.',
                ]);
            }

            $manifest = $this->readJson($manifestPath);
            $pages = $manifest['pages'] ?? [];

            if (count($pages) !== $pageCount) {
                throw ValidationException::withMessages([
                    'page_count' => "Server đã nhận " . count($pages) . " / {$pageCount} trang. Không finalize để tránh thiếu ảnh.",
                ]);
            }

            $orderedPages = [];
            for ($index = 0; $index < $pageCount; $index++) {
                $page = $pages[(string) $index] ?? null;
                if (!is_array($page) || !is_file((string) ($page['path'] ?? ''))) {
                    throw ValidationException::withMessages([
                        'page_count' => 'Thiếu trang số ' . ($index + 1) . '. Hãy upload lại batch bị thiếu.',
                    ]);
                }

                $stageHash = hash_file('sha256', $page['path']);
                if (!is_string($stageHash) || !hash_equals((string) $page['sha256'], $stageHash)) {
                    throw ValidationException::withMessages([
                        'files' => 'Phát hiện file staging bị thay đổi checksum. Chapter chưa được tạo.',
                    ]);
                }

                $orderedPages[] = $page;
            }

            if (Chapter::withTrashed()
                ->where('comic_id', $comic->id)
                ->where('chapter_number', $chapterNumber)
                ->exists()) {
                throw ValidationException::withMessages([
                    'chapter_number' => "Chapter {$chapterNumber} đã tồn tại trong truyện này.",
                ]);
            }

            $chapter = $this->chapterService->createWithPages($comic, [
                'chapter_number' => $chapterNumber,
                'title' => $title,
                'is_free' => true,
            ], []);
            $chapter->update(['processing_status' => 'processing']);

            $targetFolder = "comics/{$comic->id}/chapters/{$chapter->id}";
            $finalPages = [];
            $dimensions = [];

            try {
                foreach ($orderedPages as $index => $page) {
                    $targetPath = $targetFolder . '/' . sprintf('%03d', $index + 1) . '.' . $page['extension'];
                    $source = @fopen($page['path'], 'rb');
                    if ($source === false) {
                        throw new RuntimeException('Không thể mở file staging để finalize.');
                    }

                    try {
                        $written = Storage::disk('public')->put($targetPath, $source);
                    } finally {
                        fclose($source);
                    }

                    if ($written === false) {
                        throw new RuntimeException('Không thể lưu ảnh vào public storage.');
                    }

                    $finalHash = $this->hashPublicFile($targetPath);
                    if (!hash_equals((string) $page['sha256'], $finalHash)) {
                        throw new RuntimeException('Checksum file đích không khớp với file gốc.');
                    }

                    $finalPages[] = $targetPath;
                    $dimensions[] = [
                        'width' => (int) $page['width'],
                        'height' => (int) $page['height'],
                    ];
                }

                $chapter->update([
                    'pages' => $finalPages,
                    'page_dimensions' => $dimensions,
                    'processing_status' => 'ready',
                ]);

                $state['finalized'][$chapterKey] = $chapter->id;
                $state['updated_at'] = now()->toIso8601String();
                $this->writeJson($this->sessionStatePath($session), $state);

                File::deleteDirectory($this->chapterDir($session, $chapterKey));

                try {
                    $this->notificationService->dispatchIfEligible($chapter);
                } catch (Throwable $e) {
                    Log::warning('Bulk chapter upload completed but notification dispatch failed.', [
                        'chapter_id' => $chapter->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            } catch (Throwable $e) {
                Storage::disk('public')->deleteDirectory($targetFolder);
                $chapter->forceDelete();
                throw $e;
            }

            return [
                'chapter_id' => $chapter->id,
                'chapter_number' => $chapter->chapter_number,
                'pages' => count($finalPages),
                'already_finalized' => false,
            ];
        });
    }

    public function complete(Comic $comic, User $user, string $session): array
    {
        $state = $this->withSessionLock($session, function () use ($comic, $user, $session) {
            return $this->loadAuthorizedSession($session, $comic, $user);
        });

        $finalizedCount = count($state['finalized'] ?? []);
        File::deleteDirectory($this->sessionDir($session));

        return [
            'completed' => true,
            'chapters_created' => $finalizedCount,
        ];
    }

    private function inspectImage(string $path): array
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Máy chủ không thể khởi tạo MIME detector.');
        }

        try {
            $mime = finfo_file($finfo, $path);
        } finally {
            finfo_close($finfo);
        }

        if (!is_string($mime) || !isset($this->mimeExtensions[$mime])) {
            throw ValidationException::withMessages([
                'files' => 'Có file không phải ảnh hợp lệ hoặc định dạng không được hỗ trợ.',
            ]);
        }

        $dimensions = @getimagesize($path);
        if ($dimensions === false || ($dimensions[0] ?? 0) <= 0 || ($dimensions[1] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'files' => 'Có file ảnh bị lỗi hoặc không đọc được kích thước.',
            ]);
        }

        return [
            $this->mimeExtensions[$mime],
            (int) $dimensions[0],
            (int) $dimensions[1],
        ];
    }

    private function hashPublicFile(string $path): string
    {
        $stream = Storage::disk('public')->readStream($path);
        if ($stream === false) {
            throw new RuntimeException('Không thể đọc lại file sau khi lưu.');
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }

    private function loadAuthorizedSession(string $session, Comic $comic, User $user): array
    {
        $this->assertSessionId($session);
        $path = $this->sessionStatePath($session);
        if (!is_file($path)) {
            throw ValidationException::withMessages([
                'session' => 'Phiên upload không tồn tại hoặc đã hết hạn.',
            ]);
        }

        $state = $this->readJson($path);
        if ((int) ($state['comic_id'] ?? 0) !== (int) $comic->id
            || (int) ($state['user_id'] ?? 0) !== (int) $user->id) {
            throw ValidationException::withMessages([
                'session' => 'Phiên upload không thuộc truyện hoặc tài khoản hiện tại.',
            ]);
        }

        $updatedAt = strtotime((string) ($state['updated_at'] ?? $state['created_at'] ?? '')) ?: 0;
        if ($updatedAt < time() - self::SESSION_TTL_SECONDS) {
            File::deleteDirectory($this->sessionDir($session));
            throw ValidationException::withMessages([
                'session' => 'Phiên upload đã hết hạn sau 24 giờ. Hãy chọn lại thư mục để tiếp tục.',
            ]);
        }

        return $state;
    }

    private function cleanupExpiredSessions(): void
    {
        if (!is_dir($this->rootPath)) {
            return;
        }

        foreach (File::directories($this->rootPath) as $directory) {
            $statePath = $directory . '/session.json';
            $timestamp = is_file($statePath) ? filemtime($statePath) : filemtime($directory);
            if ($timestamp !== false && $timestamp < time() - self::SESSION_TTL_SECONDS) {
                File::deleteDirectory($directory);
            }
        }
    }

    private function withSessionLock(string $session, callable $callback): mixed
    {
        $this->assertSessionId($session);
        $sessionDir = $this->sessionDir($session);
        if (!is_dir($sessionDir)) {
            throw ValidationException::withMessages([
                'session' => 'Phiên upload không tồn tại hoặc đã hết hạn.',
            ]);
        }

        $handle = fopen($sessionDir . '/.lock', 'c+');
        if ($handle === false) {
            throw new RuntimeException('Không thể khóa phiên upload.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Không thể khóa phiên upload.');
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function assertSessionId(string $session): void
    {
        if (!Str::isUuid($session)) {
            throw ValidationException::withMessages([
                'session' => 'Mã phiên upload không hợp lệ.',
            ]);
        }
    }

    private function assertChapterKey(string $chapterKey): void
    {
        if (preg_match('/^[A-Za-z0-9_-]{1,80}$/', $chapterKey) !== 1) {
            throw ValidationException::withMessages([
                'chapter_key' => 'Mã chapter trong phiên upload không hợp lệ.',
            ]);
        }
    }

    private function readJson(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Metadata phiên upload bị lỗi.');
        }

        return $decoded;
    }

    private function writeJson(string $path, array $data): void
    {
        File::ensureDirectoryExists(dirname($path));
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($encoded) || file_put_contents($path, $encoded, LOCK_EX) === false) {
            throw new RuntimeException('Không thể ghi metadata phiên upload.');
        }
    }

    private function sessionDir(string $session): string
    {
        return $this->rootPath . '/' . $session;
    }

    private function sessionStatePath(string $session): string
    {
        return $this->sessionDir($session) . '/session.json';
    }

    private function chapterDir(string $session, string $chapterKey): string
    {
        return $this->sessionDir($session) . '/chapters/' . $chapterKey;
    }

    private function chapterPagesDir(string $session, string $chapterKey): string
    {
        return $this->chapterDir($session, $chapterKey) . '/pages';
    }

    private function chapterManifestPath(string $session, string $chapterKey): string
    {
        return $this->chapterDir($session, $chapterKey) . '/manifest.json';
    }
}
