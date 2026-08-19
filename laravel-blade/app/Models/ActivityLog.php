<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    // Không dùng updated_at — log là bất biến
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'payload',
        'ip_address',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Subject polymorphic: Comment, Comic, Chapter, User...
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ─────────────────────────────────────────────────────────────
    // STATIC HELPER
    // ─────────────────────────────────────────────────────────────

    /**
     * Ghi log một hành động.
     *
     * Ví dụ:
     *   ActivityLog::record('comment.created', $comment, ['comic_id' => 5]);
     *   ActivityLog::record('admin.user.banned', $user);
     *
     * @param  string      $action   'comment.created', 'comic.liked', ...
     * @param  Model|null  $subject  Model liên quan
     * @param  array       $payload  Dữ liệu bổ sung
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array  $payload = []
    ): self {
        return static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'payload'      => !empty($payload) ? $payload : null,
            'ip_address'   => Request::ip(),
        ]);
    }
}
