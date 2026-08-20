<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    // Không dùng updated_at — log là bất biến
    const UPDATED_AT = null;

    /**
     * Action constants — đảm bảo nhất quán khi gọi ActivityLog::record()
     */
    // Auth
    const ACTION_LOGIN            = 'auth.login';
    const ACTION_LOGIN_FAILED     = 'auth.login_failed';
    const ACTION_REGISTER         = 'auth.register';
    const ACTION_LOGOUT           = 'auth.logout';

    // Comment
    const ACTION_COMMENT_CREATED  = 'comment.created';
    const ACTION_COMMENT_DELETED  = 'comment.deleted';

    // Comic
    const ACTION_COMIC_FOLLOWED   = 'comic.followed';
    const ACTION_COMIC_UNFOLLOWED = 'comic.unfollowed';
    const ACTION_COMIC_LIKED      = 'comic.liked';
    const ACTION_COMIC_UNLIKED    = 'comic.unliked';

    // Admin — Comic
    const ACTION_ADMIN_COMIC_CREATED = 'admin.comic.created';
    const ACTION_ADMIN_COMIC_UPDATED = 'admin.comic.updated';
    const ACTION_ADMIN_COMIC_DELETED = 'admin.comic.deleted';

    // Admin — User
    const ACTION_ADMIN_USER_BANNED       = 'admin.user.banned';
    const ACTION_ADMIN_USER_UNBANNED     = 'admin.user.unbanned';
    const ACTION_ADMIN_USER_ROLE_CHANGED = 'admin.user.role_changed';

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
    // SCOPES — dùng trong admin dashboard
    // ─────────────────────────────────────────────────────────────

    /**
     * Lọc theo action prefix.
     * Ví dụ: ActivityLog::forAction('admin.')->get()
     */
    public function scopeForAction($query, string $prefix)
    {
        return $query->where('action', 'like', $prefix . '%');
    }

    /**
     * Lọc theo user_id.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Lọc theo khoảng thời gian.
     */
    public function scopeInPeriod($query, \Carbon\Carbon $from, \Carbon\Carbon $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ─────────────────────────────────────────────────────────────
    // STATIC HELPER
    // ─────────────────────────────────────────────────────────────

    /**
     * Ghi log một hành động.
     *
     * Được bọc trong try/catch: nếu DB bị lỗi (maintenance, full disk...)
     * sẽ fallback ghi vào activity.log channel thay vì throw exception
     * và crash toàn bộ request.
     *
     * Ví dụ:
     *   ActivityLog::record('comment.created', $comment, ['comic_id' => 5]);
     *   ActivityLog::record(ActivityLog::ACTION_ADMIN_USER_BANNED, $user);
     *
     * @param  string      $action   'comment.created', 'admin.user.banned', ...
     * @param  Model|null  $subject  Model liên quan (polymorphic)
     * @param  array       $payload  Dữ liệu bổ sung (JSON)
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array  $payload = []
    ): ?self {
        try {
            return static::create([
                'user_id'      => auth()->id(),
                'action'       => $action,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'payload'      => !empty($payload) ? $payload : null,
                'ip_address'   => Request::ip(),
            ]);
        } catch (\Throwable $e) {
            // Fallback: ghi vào file log thay vì crash request
            Log::channel('activity')->error('ActivityLog::record() failed', [
                'action'     => $action,
                'subject'    => $subject ? (get_class($subject) . ':' . $subject->getKey()) : null,
                'payload'    => $payload,
                'error'      => $e->getMessage(),
                'user_id'    => auth()->id(),
                'ip'         => Request::ip(),
            ]);

            return null;
        }
    }
}
