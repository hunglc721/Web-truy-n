<?php
// app/Models/Library.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Library extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'comic_id',
        'last_read_chapter_id',
        'status',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /** Thư viện thuộc về user */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Truyện được lưu trong thư viện */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    /** Chương đang đọc dở */
    public function lastReadChapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'last_read_chapter_id');
    }
}
