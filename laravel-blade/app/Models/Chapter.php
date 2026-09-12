<?php
// app/Models/Chapter.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Chapter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'comic_id',
        'chapter_number',
        'title',
        'slug',
        'pages',
        'page_dimensions',
        'content',
        'views',
        'published_at',
        'followers_notified_at',
        'processing_status',
    ];

    protected $casts = [
        'pages'                   => 'array',
        'page_dimensions'         => 'array',
        'published_at'            => 'datetime',
        'followers_notified_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Chapter $chapter) {
            if (empty($chapter->slug)) {
                $chapter->slug = 'chapter-' . ($chapter->chapter_number ?? 1);
            }
        });
    }

    public static function isValidNumber(float|int|string|null $number): bool
    {
        if ($number === null) {
            return false;
        }

        $str = trim((string) $number);
        return (bool) preg_match('/^\d+(?:\.\d+)?$/', $str);
    }

    public static function formatNumber(float|int|string|null $number): string
    {
        if ($number === null) {
            return '';
        }

        $str = trim((string) $number);
        if ($str === '') {
            return '';
        }

        if (!preg_match('/^\d+(?:\.\d+)?$/', $str)) {
            return $str;
        }

        if (str_contains($str, '.')) {
            [$intPart, $decPart] = explode('.', $str, 2);
            $intPart = ltrim($intPart, '0');
            if ($intPart === '') {
                $intPart = '0';
            }
            $decPart = rtrim($decPart, '0');
            if ($decPart === '') {
                return $intPart;
            }
            return $intPart . '.' . $decPart;
        }

        $intPart = ltrim($str, '0');
        return $intPart === '' ? '0' : $intPart;
    }

    public function getChapterNumberAttribute($value): int|float|string
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $formatted = self::formatNumber($value);
        if (!str_contains($formatted, '.')) {
            return (int) $formatted;
        }

        $floatVal = (float) $formatted;
        if ((string) $floatVal === $formatted) {
            return $floatVal;
        }

        return $formatted;
    }

    public function setChapterNumberAttribute($value): void
    {
        $this->attributes['chapter_number'] = ($value !== null && $value !== '')
            ? self::formatNumber($value)
            : null;
    }

    public function getSlugAttribute(?string $value): string
    {
        return !empty($value) ? $value : 'chapter-' . ($this->chapter_number ?? $this->id ?? 1);
    }

    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('created_at', 'desc');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopePreview($query)
    {
        return $query;
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->published_at
            ? Carbon::parse($this->published_at)->diffForHumans()
            : 'Unknown';
    }

    public function getLabelAttribute(): string
    {
        return 'Ch.' . $this->chapter_number;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function getPagesWithDimensionsAttribute(): array
    {
        $rawPages = $this->pages;
        $pages = is_array($rawPages) ? $rawPages : (json_decode($rawPages, true) ?? []);
        $rawDims = $this->page_dimensions;
        $dimensions = is_array($rawDims) ? $rawDims : (json_decode($rawDims, true) ?? []);

        $result = [];
        foreach ($pages as $index => $page) {
            $path = is_array($page) ? ($page['path'] ?? $page['url'] ?? '') : (string) $page;
            $width = is_array($page) && isset($page['width'])
                ? (int) $page['width']
                : (int) ($dimensions[$index]['width'] ?? 800);
            $height = is_array($page) && isset($page['height'])
                ? (int) $page['height']
                : (int) ($dimensions[$index]['height'] ?? 1200);

            $url = preg_match('~^(https?:)?//~i', $path) ? $path
                : (str_starts_with($path, '/storage/') ? asset(ltrim($path, '/')) : asset('storage/' . $path));

            $result[] = [
                'path'   => $path,
                'url'    => $url,
                'width'  => $width > 0 ? $width : 800,
                'height' => $height > 0 ? $height : 1200,
            ];
        }

        return $result;
    }

    public function getReaderPagesAttribute(): array
    {
        return app(\App\Services\ReaderImageService::class)->pages($this);
    }
}
