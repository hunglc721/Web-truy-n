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
        'title_normalized',
        'alt_titles',
        'alt_titles_normalized',
        'slug',
        'cover_image',
        'description',
        'status',
        'country',
        'released_year',
        'is_original',
        'is_featured',
        'views',
        'views_count',
        'likes_count',
        'comments_count',
        'avg_rating',
        'rating_avg',
        'total_ratings',
        'rating_count',
        'trending_rank',
        'is_mature',
        'age_rating',
        'content_warning',
        'published_at',
    ];

    protected $casts = [
        'is_mature'      => 'boolean',
        'is_original'    => 'boolean',
        'is_featured'    => 'boolean',
        'released_year'  => 'integer',
        'avg_rating'     => 'float',
        'rating_avg'     => 'float',
        'total_ratings'  => 'integer',
        'rating_count'   => 'integer',
        'likes_count'    => 'integer',
        'comments_count' => 'integer',
        'views_count'    => 'integer',
        'published_at'   => 'date',
    ];

    // ─────────────────────────────────────────────────────────────
    // AUTO SLUG & NORMALIZATION
    // ─────────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::saving(function (Comic $comic) {
            if (!empty($comic->title)) {
                $comic->title_normalized = \App\Helpers\VietnameseHelper::removeAccents($comic->title);
            }
            if (!empty($comic->alt_titles)) {
                $comic->alt_titles_normalized = \App\Helpers\VietnameseHelper::removeAccents($comic->alt_titles);
            }
            if (empty($comic->slug)) {
                $comic->slug = Str::slug($comic->title ?: 'comic-' . ($comic->id ?? time()));
            }
        });
    }

    /**
     * Luôn đảm bảo slug không bao giờ trả về null hoặc rỗng.
     */
    public function getSlugAttribute(?string $value): string
    {
        return !empty($value) ? $value : Str::slug($this->title ?: 'comic-' . ($this->id ?? 1));
    }

    /**
     * Chuẩn hóa status thành chữ thường để tương thích với SQLite CHECK constraints.
     */
    public function setStatusAttribute(?string $value): void
    {
        $this->attributes['status'] = $value ? strtolower($value) : 'ongoing';
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
     * Lấy chương mới nhất đã phát hành (dùng trong home/update list & detail page).
     * Chỉ trả về chương có published_at <= now() — che chương lên lịch tương lai.
     *
     * Comic::find(1)->latestChapter — dùng eager load: with('latestChapter')
     */
    public function latestChapter(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Chapter::class)
                    ->ofMany(['chapter_number' => 'max'], fn ($q) => $q->where('published_at', '<=', now()));
    }

    /**
     * Lấy chương mới nhất kể cả chưa phát hành — dành cho Admin preview.
     * Dùng trong Admin Dashboard để xem danh sách chương đã lên lịch.
     *
     * Comic::find(1)->latestChapterPreview
     */
    public function latestChapterPreview(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Chapter::class)->latestOfMany('chapter_number');
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
     * Bình luận cấp truyện (chapter_id IS NULL).
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)
                    ->whereNull('chapter_id')
                    ->whereNull('parent_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Tất cả bình luận thuộc về bộ truyện (bao gồm cả bình luận cấp truyện và bình luận ở các chapter).
     */
    public function allComments(): HasMany
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
                    ->withPivot('is_primary');
    }

    /**
     * Tác giả (nhiều-nhiều).
     * Comic::find(1)->authors — ["Chugong", "REDICE Studio"]
     * Pivot có cột role: story | art | both
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'comic_author')
                    ->withPivot('role');
    }

    /**
     * Nhãn (nhiều-nhiều).
     * Comic::find(1)->tags — ["HOT", "ORIGINAL"]
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'comic_tag');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'comic_team')
                    ->withTimestamps();
    }

    public function collaborativeRecommendations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ComicRecommendation::class, 'comic_id')->orderBy('score', 'desc');
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

    /** Cập nhật avg_rating và rating_count sau khi có rating mới — gọi từ RatingObserver */
    public function recalculateRating(): void
    {
        $avg = (float) ($this->ratings()->avg('score') ?? 0);
        $total = (int) $this->ratings()->count();
        $roundedAvg = round($avg, 1);

        $this->update([
            'avg_rating'    => $roundedAvg,
            'rating_avg'    => $roundedAvg,
            'total_ratings' => $total,
            'rating_count'  => $total,
        ]);
    }
}
