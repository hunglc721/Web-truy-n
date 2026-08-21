<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_APPROVED = 'approved';
    public const STATUS_SPAM = 'spam';
    public const STATUS_PENDING = 'pending';
    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'user_id',
        'comic_id',
        'chapter_id',
        'parent_id',
        'content',
        'status',
        'likes_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'comment_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function likedBy(?int $userId): bool
    {
        return $userId ? $this->likes()->where('user_id', $userId)->exists() : false;
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at ? Carbon::parse($this->created_at)->diffForHumans() : '';
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeHidden($query)
    {
        return $query->whereIn('status', [self::STATUS_HIDDEN, self::STATUS_SPAM]);
    }

    public function scopeReported($query)
    {
        return $query->where('status', self::STATUS_SPAM)->orWhereHas('reports');
    }
}
