<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComicRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'comic_id',
        'recommended_comic_id',
        'score',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class, 'comic_id');
    }

    public function recommendedComic(): BelongsTo
    {
        return $this->belongsTo(Comic::class, 'recommended_comic_id');
    }
}
