<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHistory extends Model
{
    use HasFactory;

    protected $table = 'reading_histories';

    protected $fillable = [
        'user_id',
        'comic_id',
        'chapter_id',
        'scroll_percent',
        'last_read_at',
    ];

    protected $casts = [
        'scroll_percent' => 'float',
        'last_read_at'   => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /**
     * Lịch sử thuộc về 1 User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lịch sử thuộc về 1 Truyện (Comic).
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    /**
     * Lịch sử ghi nhận Chapter đọc gần nhất.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }
}
