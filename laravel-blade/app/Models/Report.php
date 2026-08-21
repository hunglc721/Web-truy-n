<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $table = 'reports';

    // Trạng thái xử lý báo cáo
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_RESOLVED   = 'resolved';
    const STATUS_DISMISSED  = 'dismissed';

    // Loại báo cáo
    const TYPE_BROKEN_IMAGE   = 'broken_image';
    const TYPE_CONTENT_ERROR  = 'content_error';
    const TYPE_WRONG_ORDER    = 'wrong_order';
    const TYPE_MISSING_PAGE   = 'missing_page';

    protected $fillable = [
        'user_id',
        'comic_id',
        'chapter_id',
        'comment_id',
        'page_number',
        'image_url',
        'type',
        'description',
        'admin_note',
        'status',
        'ip_address',
    ];

    protected $casts = [
        'page_number' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

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

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : '';
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'broken_image'  => '🖼️ Ảnh lỗi / Không tải được',
            'wrong_order'   => '🔄 Sai thứ tự trang',
            'missing_page'  => '📄 Thiếu trang',
            'content_error' => '⚠️ Sai nội dung / Dịch lỗi',
            default         => '⚠️ Báo cáo lỗi',
        };
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeDismissed($query)
    {
        return $query->where('status', self::STATUS_DISMISSED);
    }
}
