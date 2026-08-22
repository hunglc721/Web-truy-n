<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'avatar',
        'website',
        'facebook',
        'discord',
        'members_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    public function comics(): BelongsToMany
    {
        return $this->belongsToMany(Comic::class, 'comic_team')
                    ->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_follows')
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
