<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadTask extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['worker_id'];
    protected $casts = [
        'manifest' => 'array', 'error_context' => 'array',
        'started_at' => 'datetime', 'last_heartbeat_at' => 'datetime',
        'completed_at' => 'datetime', 'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (UploadTask $task) {
            $task->version = (int) $task->version + 1;
        });
    }

    public function comic() { return $this->belongsTo(Comic::class); }
    public function user() { return $this->belongsTo(User::class); }
}
