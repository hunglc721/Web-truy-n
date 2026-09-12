<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BrandingService
{
    public function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($path, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) {
            return $path;
        }

        $path = preg_replace('#^/storage/#', '', $path);
        if (!preg_match('#^[a-zA-Z0-9_.-]+(?:/[a-zA-Z0-9_.-]+)*$#', $path) || str_contains($path, '..')) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function urls(array $settings): array
    {
        $settings['site_logo_url'] = $this->url($settings['site_logo'] ?? null);
        $settings['site_favicon_url'] = $this->url($settings['site_favicon'] ?? null);
        $settings['favicon_url'] = $settings['site_favicon_url'] ?? $settings['site_logo_url'] ?? asset('images/default-brand.svg');

        return $settings;
    }

    /** Persist all settings before deleting any previous, app-owned branding files. */
    public function save(array $data, array $uploads, array $removals, ?int $userId): void
    {
        $disk = Storage::disk('public');
        $newPaths = [];
        $oldPaths = [];

        try {
            foreach (['site_logo' => 'logo', 'site_favicon' => 'favicon'] as $key => $directory) {
                if (isset($uploads[$key])) {
                    $path = $uploads[$key]->store("branding/{$directory}", 'public');
                    if (!$path) {
                        throw new RuntimeException('Không thể lưu ảnh nhận diện thương hiệu.');
                    }
                    $newPaths[] = $path;
                    $data[$key] = $path;
                } elseif ($removals[$key] ?? false) {
                    $data[$key] = null;
                }
            }

            DB::transaction(function () use ($data, $userId, &$oldPaths) {
                // Read the current values under a lock, not a potentially stale cache.
                $oldPaths = Setting::query()->whereIn('key', ['site_logo', 'site_favicon'])
                    ->lockForUpdate()->pluck('value', 'key')->all();
                foreach ($data as $key => $value) {
                    Setting::putValue($key, $value, $key === 'maintenance_mode' ? 'bool' : 'string', $userId);
                }
            });
        } catch (Throwable $exception) {
            $disk->delete($newPaths);
            throw $exception;
        } finally {
            // Clear again after commit/rollback, including values read during a failed write.
            foreach (array_keys($data) as $key) {
                Cache::forget("setting:{$key}");
            }
        }

        $currentPaths = Setting::query()->whereIn('key', ['site_logo', 'site_favicon'])->pluck('value')
            ->map(fn ($path) => preg_replace('#^/storage/#', '', $path ?? ''))->all();
        foreach ($oldPaths as $key => $oldPath) {
            $ownedPath = preg_replace('#^/storage/#', '', $oldPath ?? '');
            if (!array_key_exists($key, $data) || in_array($ownedPath, $currentPaths, true)) {
                continue;
            }
            if (preg_match('#^branding/(logo|favicon)/[a-zA-Z0-9_-]+\.(png|jpe?g|webp|ico)$#i', $ownedPath)) {
                $disk->delete($ownedPath);
            }
        }
    }
}
