<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    // Trạng thái hợp lệ cho cột status
    const STATUS_APPROVED = 'approved';
    const STATUS_SPAM     = 'spam';
    const STATUS_PENDING  = 'pending';

    protected $fillable = [
        'user_id',
        'comic_id',
        'chapter_id',
        'parent_id',
        'content',
        'status',       // Fix #1: thêm status vào fillable – trước đây bị silent-fail khi create()
        'likes_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /**
     * Bình luận thuộc về 1 User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bình luận thuộc về 1 Truyện (Comic).
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    /**
     * Bình luận thuộc về 1 Chapter cụ thể (có thể null nếu ở trang detail truyện).
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Bình luận cha (nếu là phản hồi).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Danh sách các bình luận phản hồi (nhiều câu trả lời).
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    /**
     * Trả về thời gian tạo dạng "2 hours ago"
     * Blade: {{ $comment->time_ago }}
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at ? Carbon::parse($this->created_at)->diffForHumans() : '';
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────

    /**
     * Chỉ lấy bình luận đã được duyệt (status = 'approved').
     * Dùng: Comment::approved()->where('comic_id', $id)->get()
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
