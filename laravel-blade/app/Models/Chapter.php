<?php
// app/Models/Chapter.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Chapter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'comic_id',
        'chapter_number',
        'title',
        'slug',
        'pages',
        'content',
        'views',
        'published_at',
        'is_free',
    ];

    protected $casts = [
        'pages'        => 'array',       // JSON → PHP array tự động
        'published_at' => 'datetime',
        'is_free'      => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /**
     * Chương thuộc về một truyện.
     * Chapter::find(1)->comic->title  →  "Solo Leveling"
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    /**
     * Trả về thời gian đăng dạng "2h ago", "3h ago" như trên giao diện.
     * Blade: {{ $chapter->time_ago }}
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->published_at
            ? Carbon::parse($this->published_at)->diffForHumans()
            : 'Unknown';
    }

    /**
     * Trả về label hiển thị: "Ch.200"
     * Blade: {{ $chapter->label }}
     */
    public function getLabelAttribute(): string
    {
        return 'Ch.' . $this->chapter_number;
    }
}
