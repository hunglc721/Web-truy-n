<?php
// app/Models/Rating.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Observer;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comic_id',
        'score',
        'review',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /** Rating thuộc về user */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rating thuộc về truyện */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    // ─────────────────────────────────────────────────────────────
    // AUTO-RECALCULATE avg_rating sau mỗi thay đổi
    // ─────────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        // Khi tạo, sửa, hoặc xóa rating → cập nhật avg_rating trong bảng comics
        $recalculate = function (Rating $rating) {
            $rating->comic?->recalculateRating();
        };

        static::created($recalculate);
        static::updated($recalculate);
        static::deleted($recalculate);
    }
}
