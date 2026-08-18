<?php
// app/Models/ComicLike.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComicLike extends Model
{
    protected $fillable = ['user_id', 'comic_id', 'liked_at'];

    protected $casts = [
        'liked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }
}
