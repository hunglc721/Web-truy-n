<?php
// app/Models/User.php
// Thêm relationships vào User model có sẵn của Laravel

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'banned_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'banned_at'         => 'datetime',
        ];
    }

    /** Kiểm tra user có bị khóa không */
    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }

    /**
     * Kiểm tra user có quyền admin không.
     * Dùng method này thay vì truy cập $user->is_admin trực tiếp
     * để tập trung logic phân quyền tại một nơi.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS — Thêm mới cho WebComics
    // ─────────────────────────────────────────────────────────────

    /**
     * Thư viện của user.
     * Auth::user()->libraries()->with('comic')->get()
     */
    public function libraries(): HasMany
    {
        return $this->hasMany(Library::class);
    }

    /**
     * Truyện user đã thêm vào Library (shortcut qua hasManyThrough).
     * Auth::user()->savedComics → Collection<Comic>
     */
    public function savedComics()
    {
        return $this->hasManyThrough(Comic::class, Library::class, 'user_id', 'id', 'id', 'comic_id');
    }

    /**
     * Đánh giá của user.
     * Auth::user()->ratings()->where('comic_id', $id)->first()
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Lịch sử đọc truyện của user.
     * Auth::user()->readingHistories()->with(['comic', 'chapter'])->get()
     */
    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class)->orderBy('last_read_at', 'desc');
    }

    /**
     * Tất cả bình luận của user.
     * Auth::user()->comments
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Nhật ký hoạt động của user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Các truyện user đã thích.
     * Auth::user()->likes
     */
    public function likes(): HasMany
    {
        return $this->hasMany(ComicLike::class);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    /** Kiểm tra user đã thêm truyện vào lib chưa */
    public function hasInLibrary(int $comicId): bool
    {
        return $this->libraries()->where('comic_id', $comicId)->exists();
    }

    /** Kiểm tra user đã thích truyện này chưa */
    public function hasLikedComic(int $comicId): bool
    {
        return $this->likes()->where('comic_id', $comicId)->exists();
    }

    /** Lấy rating của user cho một truyện cụ thể */
    public function ratingForComic(int $comicId): ?Rating
    {
        return $this->ratings()->where('comic_id', $comicId)->first();
    }

    /** Lấy chapter đọc gần nhất của user cho 1 bộ truyện */
    public function lastReadChapter(int $comicId): ?Chapter
    {
        $history = $this->readingHistories()->where('comic_id', $comicId)->first();
        return $history ? $history->chapter : null;
    }

    /** Lấy bản ghi Lịch sử đọc gần nhất của user cho 1 bộ truyện (kèm phần trăm vị trí đọc) */
    public function readingHistoryForComic(int $comicId): ?ReadingHistory
    {
        return $this->readingHistories()->with('chapter')->where('comic_id', $comicId)->first();
    }
}
