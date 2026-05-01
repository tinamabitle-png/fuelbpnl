<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditScore extends Model
{
    protected $fillable = [
        'user_id',
        'score',
        'band',
        'version',
        'reasons_json',
        'metadata',
        'scored_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'reasons_json' => 'array',
        'metadata' => 'array',
        'scored_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
