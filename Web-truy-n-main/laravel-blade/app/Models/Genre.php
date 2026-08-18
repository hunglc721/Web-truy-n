<?php
// app/Models/Genre.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'icon', 'description'];

    protected static function booted(): void
    {
        static::creating(function (Genre $genre) {
            if (empty($genre->slug)) {
                $genre->slug = Str::slug($genre->name);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    /**
     * Một thể loại có nhiều truyện (và ngược lại).
     *
     * Dùng trong:
     *   Genre::where('slug', 'action')->first()->comics()->get()
     *   → Lấy tất cả truyện Action
     *
     * Eager load với withCount để hiển thị "200 Chapters" (số truyện):
     *   Genre::withCount('comics')->get()
     */
    public function comics(): BelongsToMany
    {
        return $this->belongsToMany(Comic::class, 'comic_genre')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }
}
