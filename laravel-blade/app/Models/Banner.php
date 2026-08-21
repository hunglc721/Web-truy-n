<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = [
        'title',
        'image_url',
        'link_url',
        'order',
        'is_active',
        'start_at',
        'end_at',
        'clicks_count',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'order'        => 'integer',
        'clicks_count' => 'integer',
        'start_at'     => 'datetime',
        'end_at'       => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    /**
     * Kiểm tra banner đã hết hạn hiển thị chưa.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->end_at ? $this->end_at->isPast() : false;
    }

    /**
     * Kiểm tra banner đang chờ tới giờ bắt đầu hiệu lực.
     */
    public function getIsScheduledAttribute(): bool
    {
        return $this->start_at ? $this->start_at->isFuture() : false;
    }

    /**
     * Trả về URL ảnh chuẩn (hỗ trợ cả upload local storage và link tuyệt đối).
     */
    public function getDisplayImageAttribute(): string
    {
        if (str_starts_with($this->image_url, 'http://') || str_starts_with($this->image_url, 'https://')) {
            return $this->image_url;
        }

        return asset('storage/' . ltrim($this->image_url, '/'));
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────

    /**
     * Lấy các banner đang bật và còn trong thời gian hiệu lực.
     */
    public function scopeActive($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('order', 'asc')
            ->orderBy('id', 'desc');
    }
}
