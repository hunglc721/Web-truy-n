<?php
// app/Models/Schedule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'comic_id',
        'day_of_week',
        'release_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Map từ day_of_week (0-6) sang tên hiển thị trên giao diện.
     * Schedule::DAY_NAMES[4] → "THU"
     */
    const DAY_NAMES = [
        0 => 'SUN',
        1 => 'MON',
        2 => 'TUE',
        3 => 'WED',
        4 => 'THU',
        5 => 'FRI',
        6 => 'SAT',
    ];

    const DAY_FULL_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /**
     * Lịch thuộc về một truyện.
     * Schedule::find(1)->comic->title  →  "Solo Leveling"
     */
    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

    // ─────────────────────────────────────────────────────────────
    // ACCESSORS
    // ─────────────────────────────────────────────────────────────

    /** Tên ngày rút gọn: "THU" */
    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? '';
    }

    /** Tên ngày đầy đủ: "Thursday" */
    public function getDayFullNameAttribute(): string
    {
        return self::DAY_FULL_NAMES[$this->day_of_week] ?? '';
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────

    /** Lấy lịch của ngày hôm nay (PHP date('w') = day of week) */
    public function scopeToday($query)
    {
        return $query->where('day_of_week', now()->dayOfWeek)
                     ->where('is_active', true);
    }

    /** Lấy lịch theo ngày cụ thể (0=Sun...6=Sat) */
    public function scopeForDay($query, int $day)
    {
        return $query->where('day_of_week', $day)
                     ->where('is_active', true);
    }
}
