<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    public $timestamps = false; // Sổ cái tài chính chỉ dùng created_at

    protected $fillable = [
        'uuid',
        'user_id',
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'created_at',
    ];

    protected $casts = [
        'amount'         => 'integer',
        'balance_before' => 'integer',
        'balance_after'  => 'integer',
        'created_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $tx) {
            if (empty($tx->uuid)) {
                $tx->uuid = (string) Str::uuid();
            }
            if (empty($tx->created_at)) {
                $tx->created_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
