<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class RecommendationConversation extends Model
{
    protected $fillable = ['user_id', 'session_token', 'preferences_json'];

    protected $casts = ['preferences_json' => 'array'];

    // The caller can read the token to store in a session; generic serialization must not expose it.
    protected $hidden = ['session_token'];

    protected static function booted(): void
    {
        static::saving(function (self $conversation) {
            if (($conversation->user_id !== null) === ($conversation->session_token !== null)) {
                throw new InvalidArgumentException('A conversation must have exactly one owner identity.');
            }
            if ($conversation->session_token !== null && ! preg_match('/\A[a-f0-9]{64}\z/', $conversation->session_token)) {
                throw new InvalidArgumentException('Invalid guest token.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
