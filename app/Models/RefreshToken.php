<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class RefreshToken extends Model
{
    protected $table = 'jwt_refresh_tokens';

    protected $fillable = [
        'user_id',
        'token_hash',
        'jti',
        'ip',
        'user_agent',
        'expires_at',
        'revoked',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
