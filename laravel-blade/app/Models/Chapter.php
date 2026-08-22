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
        'is_free',
        'coin_price',
        'early_access_until',
        'processing_status',
    ];

    protected $casts = [
        'pages'                   => 'array',
        'page_dimensions'         => 'array',
        'published_at'            => 'datetime',
        'followers_notified_at'   => 'datetime',
        'is_free'                 => 'boolean',
        'coin_price'              => 'integer',
        'early_access_until'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Chapter $chapter) {
            if (empty($chapter->slug)) {
                $chapter->slug = 'chapter-' . ($chapter->chapter_number ?? 1);
            }
        });
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

            $url = str_starts_with($path, 'http') ? $path : asset('storage/' . $path);

            $result[] = [
                'path'   => $path,
                'url'    => $url,
                'width'  => $width > 0 ? $width : 800,
                'height' => $height > 0 ? $height : 1200,
            ];
        }

        return $result;
    }

    public function unlocks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChapterUnlock::class);
    }

    public function isEarlyAccess(): bool
    {
        return $this->early_access_until !== null && $this->early_access_until->isFuture();
    }

    public function isUnlockedFor(?User $user): bool
    {
        // 1. Chương hoàn toàn miễn phí và không trong giai đoạn early access
        if ($this->is_free && !$this->isEarlyAccess()) {
            return true;
        }

        // 2. Chưa đăng nhập
        if (!$user) {
            return false;
        }

        // 3. Admin / Moderator có quyền đọc mọi chương
        if ($user->canAccessAdmin() || $user->isAdmin()) {
            return true;
        }

        // 4. Hội viên VIP đọc được mọi chương early access
        if ($user->isVip()) {
            return true;
        }

        // 5. Đã mua mở khóa bằng coin
        return $this->unlocks()->where('user_id', $user->id)->exists();
    }
}
