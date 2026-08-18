<?php
// app/Models/Comic.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Comic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'cover_image',
        'description',
        'status',
        'is_original',
        'is_featured',
        'views',
        'avg_rating',
        'total_ratings',
        'trending_rank',
        'published_at',
    ];

    protected $casts = [
        'is_original'  => 'boolean',
        'is_featured'  => 'boolean',
        'avg_rating'   => 'float',
        'published_at' => 'date',
    ];

    // ─────────────────────────────────────────────────────────────
    // AUTO SLUG
    // ─────────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Comic $comic) {
            if (empty($comic->slug)) {
                $comic->slug = Str::slug($comic->title);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /**
     * Một truyện có nhiều chương.
     * Comic::find(1)->chapters()->latest('chapter_number')->get()
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('chapter_number');
    }

    /**
     * Lấy chương mới nhất (dùng trong home/update list).
     * Comic::find(1)->latestChapter — dùng eager load: with('latestChapter')
     */
    public function latestChapter(): HasMany
    {
        return $this->hasMany(Chapter::class)->latestOfMany('chapter_number');
    }

    /**
     * Lịch ra tập theo ngày.
     * Comic::find(1)->schedules
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Đánh giá của người dùng.
     * Comic::find(1)->ratings()->avg('score')
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Lịch sử đọc của người dùng với bộ truyện này.
     */
    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class);
    }

    /**
     * Tất cả bình luận thuộc về bộ truyện (bao gồm bình luận cấp truyện và bình luận ở chapter).
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('created_at', 'desc');
    }

    /**
     * Danh sách user đã thích truyện này.
     * $comic->likes()->count() — tổng lượt thích
     * $comic->hasLikedBy($userId) — user cụ thể đã thích chưa
     */
    public function likes(): HasMany
    {
        return $this->hasMany(ComicLike::class);
    }

    /**
     * Danh sách user trong thư viện của họ.
     */
    public function libraries(): HasMany
    {
        return $this->hasMany(Library::class);
    }

    /** Kiểm tra user cụ thể đã thích truyện này chưa */
    public function hasLikedBy(int $userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    /**
     * Thể loại (nhiều-nhiều).
     * Comic::find(1)->genres — ["Action", "Fantasy"]
     * Pivot có thêm cột is_primary
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'comic_genre')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    /**
     * Tác giả (nhiều-nhiều).
     * Comic::find(1)->authors — ["Chugong", "REDICE Studio"]
     * Pivot có cột role: story | art | both
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'comic_author')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Nhãn (nhiều-nhiều).
     * Comic::find(1)->tags — ["HOT", "ORIGINAL"]
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'comic_tag')
                    ->withTimestamps();
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES — dùng trong Controller như: Comic::trending()->get()
    // ─────────────────────────────────────────────────────────────

    /** Chỉ lấy truyện đang trending (có rank) */
    public function scopeTrending($query)
    {
        return $query->whereNotNull('trending_rank')
                     ->orderBy('trending_rank');
    }

    /** Chỉ lấy WebComics Originals */
    public function scopeOriginals($query)
    {
        return $query->where('is_original', true);
    }

    /** Lấy truyện theo thể loại (slug) */
    public function scopeByGenre($query, string $genreSlug)
    {
        return $query->whereHas('genres', fn($q) => $q->where('slug', $genreSlug));
    }

    /** Lấy truyện theo tag (hot, popular...) */
    public function scopeByTag($query, string $tagSlug)
    {
        return $query->whereHas('tags', fn($q) => $q->where('slug', $tagSlug));
    }

    /** Mới cập nhật nhất (dựa vào chapter mới nhất) */
    public function scopeLatestUpdated($query)
    {
        return $query->withMax('chapters', 'published_at')
                     ->orderByDesc('chapters_max_published_at');
    }

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    /** Trả về views dạng "12.8M", "8.6M", "590K" */
    public function getFormattedViewsAttribute(): string
    {
        $views = $this->views;
        if ($views >= 1_000_000) {
            return round($views / 1_000_000, 1) . 'M';
        }
        if ($views >= 1_000) {
            return round($views / 1_000, 1) . 'K';
        }
        return (string) $views;
    }

    /** Cập nhật avg_rating sau khi có rating mới — gọi từ RatingObserver */
    public function recalculateRating(): void
    {
        $avg = $this->ratings()->avg('score') ?? 0;
        $this->update([
            'avg_rating'    => round($avg, 1),
            'total_ratings' => $this->ratings()->count(),
        ]);
    }
}
