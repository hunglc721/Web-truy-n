<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChapterUnlock extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'chapter_id',
        'transaction_id',
        'coins_paid',
        'created_at',
    ];

    protected $casts = [
        'coins_paid' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ChapterUnlock $unlock) {
            if (empty($unlock->created_at)) {
                $unlock->created_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
