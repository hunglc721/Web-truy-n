<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'target_user_id',
        'title',
        'message',
        'severity',
        'audience',
        'role_slug',
        'link_url',
        'show_banner',
        'send_to_inbox',
        'is_dismissible',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'show_banner' => 'boolean',
        'send_to_inbox' => 'boolean',
        'is_dismissible' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('show_banner', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('audience', 'all');

            if ($user) {
                $q->orWhere('audience', 'authenticated')
                    ->orWhere(function (Builder $roleQuery) use ($user) {
                        $roleQuery->where('audience', 'role')
                            ->where('role_slug', $user->roleSlug());
                    })
                    ->orWhere(function (Builder $userQuery) use ($user) {
                        $userQuery->where('audience', 'user')
                            ->where('target_user_id', $user->id);
                    });
            } else {
                $q->orWhere('audience', 'guests');
            }
        });
    }
}
