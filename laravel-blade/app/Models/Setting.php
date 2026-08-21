<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'updated_by'];

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting:{$key}", 60, fn () => static::query()->where('key', $key)->first());

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'bool' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'int'  => (int) $setting->value,
            default => $setting->value,
        };
    }

    public static function putValue(string $key, mixed $value, string $type = 'string', ?int $updatedBy = null): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $type === 'bool' ? ($value ? '1' : '0') : $value,
                'type' => $type,
                'updated_by' => $updatedBy,
            ]
        );

        Cache::forget("setting:{$key}");
    }
}
