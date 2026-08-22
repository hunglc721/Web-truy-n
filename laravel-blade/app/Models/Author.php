<?php
// app/Models/Author.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Author extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'bio', 'avatar'];

    protected static function booted(): void
    {
        static::creating(function (Author $author) {
            if (empty($author->slug)) {
                $author->slug = Str::slug($author->name);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /**
     * Một tác giả tham gia nhiều truyện.
     * Pivot có cột 'role': story | art | both
     *
     * Ví dụ:
     *   Author::where('name', 'Chugong')->first()->comics
     *   → [Solo Leveling] với pivot->role = 'story'
     */
    public function comics(): BelongsToMany
    {
        return $this->belongsToMany(Comic::class, 'comic_author')
                    ->withPivot('role');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'author_follows')
                    ->withTimestamps();
    }

    public function isFollowedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->followers()->where('users.id', $user->id)->exists();
    }
}
