<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditConsent extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'scope',
        'granted_at',
        'expires_at',
        'revoked_at',
        'evidence_ref',
        'metadata',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
