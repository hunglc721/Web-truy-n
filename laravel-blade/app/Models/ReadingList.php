<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ReadingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'is_public',
        'views_count',
        'likes_count',
    ];

    protected $casts = [
        'is_public'   => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReadingList $list) {
            if (empty($list->slug)) {
                $list->slug = Str::slug($list->title) . '-' . Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comics(): BelongsToMany
    {
        return $this->belongsToMany(Comic::class, 'comic_reading_list')
                    ->withPivot('order_position')
                    ->orderByPivot('order_position')
                    ->withTimestamps();
    }

    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reading_list_likes')
                    ->withTimestamps();
    }

    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('users.id', $user->id)->exists();
    }
}
