<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /** @var array<int,string>|null */
    protected ?array $permissionSlugCache = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role_id',
        'banned_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'oauth_provider',
        'oauth_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'is_admin'                => 'boolean',
            'banned_at'               => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }

    public function isAdmin(): bool
    {
        if ((bool) $this->is_admin) {
            return true;
        }

        $this->loadMissing('role');
        return $this->role?->slug === 'admin';
    }

    public function roleSlug(): string
    {
        if ((bool) $this->is_admin) {
            return 'admin';
        }

        $this->loadMissing('role');
        return $this->role?->slug ?? 'member';
    }

    public function isRole(string $role): bool
    {
        return $this->roleSlug() === $role;
    }

    /**
     * Admin cũ/mới luôn có toàn quyền. Staff role load permission một lần/request.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionSlugs(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (empty($permissions)) {
            return false;
        }

        return count(array_intersect($permissions, $this->permissionSlugs())) > 0;
    }

    public function canAccessAdmin(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($this->roleSlug(), ['moderator', 'editor', 'viewer'], true)
            && $this->hasPermission('dashboard.view');
    }

    /** @return array<int,string> */
    protected function permissionSlugs(): array
    {
        if ($this->permissionSlugCache !== null) {
            return $this->permissionSlugCache;
        }

        $this->loadMissing('role.permissions');

        return $this->permissionSlugCache = $this->role
            ? $this->role->permissions->pluck('slug')->all()
            : [];
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

    public function followedAuthors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'author_follows')->withTimestamps();
    }

    public function followedTeams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_follows')->withTimestamps();
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet ?? $this->wallet()->create(['balance' => 0, 'locked_balance' => 0]);
    }

    public function getCoinBalanceAttribute(): int
    {
        return (int) ($this->wallet?->balance ?? 0);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->orderByDesc('created_at');
    }

    public function chapterUnlocks(): HasMany
    {
        return $this->hasMany(ChapterUnlock::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isVip(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }
}
