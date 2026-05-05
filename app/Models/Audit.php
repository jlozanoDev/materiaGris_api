<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'audits';

    public $timestamps = false; // only created_at is tracked explicitly

    protected $fillable = [
        'type',
        'module',
        'actor_id',
        'actor_type',
        'user_id',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
        'payload',
        'meta',
        'trace_id',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}
