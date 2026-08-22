<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DmcaReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'company_name',
        'work_title',
        'infringing_url',
        'original_work_proof',
        'details',
        'good_faith_statement',
        'status',
        'admin_notes',
        'resolved_at',
    ];

    protected $casts = [
        'good_faith_statement' => 'boolean',
        'resolved_at'          => 'datetime',
    ];
}
