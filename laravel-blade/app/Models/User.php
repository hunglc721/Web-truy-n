<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role_id',
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

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }

    /**
     * Giữ tương thích với cột is_admin cũ, đồng thời hỗ trợ role admin mới.
     */
    public function isAdmin(): bool
    {
        if ((bool) $this->is_admin) {
            return true;
        }

        if ($this->relationLoaded('role')) {
            return $this->role?->slug === 'admin';
        }

        return $this->role()->where('slug', 'admin')->exists();
    }

    public function roleSlug(): string
    {
        if ($this->isAdmin()) {
            return 'admin';
        }

        return $this->relationLoaded('role')
            ? ($this->role?->slug ?? 'member')
            : ($this->role()->value('slug') ?? 'member');
    }

    public function isRole(string $role): bool
    {
        return $this->roleSlug() === $role;
    }

    /**
     * Admin cũ/mới luôn có toàn quyền. Các role khác lấy quyền từ DB.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->role()
            ->whereHas('permissions', fn ($q) => $q->where('permissions.slug', $permission))
            ->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (empty($permissions)) {
            return false;
        }

        return $this->role()
            ->whereHas('permissions', fn ($q) => $q->whereIn('permissions.slug', $permissions))
            ->exists();
    }

    public function canAccessAdmin(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($this->roleSlug(), ['moderator', 'editor', 'viewer'], true)
            && $this->hasPermission('dashboard.view');
    }

    public function libraries(): HasMany
    {
        return $this->hasMany(Library::class);
    }

    public function savedComics()
    {
        return $this->hasManyThrough(Comic::class, Library::class, 'user_id', 'id', 'id', 'comic_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class)->orderBy('last_read_at', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ComicLike::class);
    }

    public function hasInLibrary(int $comicId): bool
    {
        return $this->libraries()->where('comic_id', $comicId)->exists();
    }

    public function hasLikedComic(int $comicId): bool
    {
        return $this->likes()->where('comic_id', $comicId)->exists();
    }

    public function ratingForComic(int $comicId): ?Rating
    {
        return $this->ratings()->where('comic_id', $comicId)->first();
    }

    public function lastReadChapter(int $comicId): ?Chapter
    {
        $history = $this->readingHistories()->where('comic_id', $comicId)->first();
        return $history ? $history->chapter : null;
    }

    public function readingHistoryForComic(int $comicId): ?ReadingHistory
    {
        return $this->readingHistories()->with('chapter')->where('comic_id', $comicId)->first();
    }
}
