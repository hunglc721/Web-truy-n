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
        'is_free',
        'processing_status',
    ];

    protected $casts = [
        'pages'           => 'array',       // JSON → PHP array tự động
        'page_dimensions' => 'array',       // JSON → PHP array kích thước [width, height]
        'published_at'    => 'datetime',
        'is_free'         => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /**
     * Chương thuộc về một truyện.
     * Chapter::find(1)->comic->title  →  "Solo Leveling"
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    /**
     * Bình luận thuộc về chương này.
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('created_at', 'desc');
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────

    /**
     * Chỉ lấy chương đã tới giờ phát hành:
     *   - published_at NOT NULL
     *   - published_at <= now()
     *
     * Dùng cho mọi truy vấn hướng đến độc giả (reader, dropdown, trang chủ).
     * Admin dùng scopePreview() để xem trước chương chưa phát hành.
     *
     * Ví dụ: Chapter::published()->where('comic_id', 1)->get()
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Không áp dụng bộ lọc published_at — dành cho Admin preview.
     * Đặt tên rõ ràng để code tại Controller dễ đọc và self-documenting.
     *
     * Ví dụ: Chapter::preview()->where('comic_id', 1)->get()
     */
    public function scopePreview($query)
    {
        // Không thêm điều kiện gì — trả về query gốc (kể cả chương tương lai)
        return $query;
    }

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    /**
     * Trả về thời gian đăng dạng "2h ago", "3h ago" như trên giao diện.
     * Blade: {{ $chapter->time_ago }}
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->published_at
            ? Carbon::parse($this->published_at)->diffForHumans()
            : 'Unknown';
    }

    /**
     * Trả về label hiển thị: "Ch.200"
     * Blade: {{ $chapter->label }}
     */
    public function getLabelAttribute(): string
    {
        return 'Ch.' . $this->chapter_number;
    }

    /**
     * Kiểm tra chương này đã tới giờ phát hành chưa.
     * Dùng trong Blade hoặc Policy để check nhanh.
     *
     * Blade: @if($chapter->isPublished())
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * Trả về danh sách trang ảnh kèm kích thước width, height và URL chuẩn hóa.
     * Dùng cho trình đọc Reader để chống giật/nhảy layout (CLS = 0) và prefetch ảnh.
     *
     * @return array<int, array{path: string, url: string, width: int, height: int}>
     */
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
}
