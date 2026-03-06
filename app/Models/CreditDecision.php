<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditDecision extends Model
{
    protected $fillable = [
        'user_id',
        'upload_id',
        'score_id',
        'score',
        'decision',
        'application_type',
        'reasons',
        'explanation_json',
        'model_version',
        'policy_version',
        'source',
        'decided_at',
    ];

    protected $casts = [
        'reasons' => 'array',
        'explanation_json' => 'array',
        'score' => 'integer',
        'decided_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function upload(): BelongsTo
    {
        return $this->belongsTo(BankStatementUpload::class, 'upload_id');
    }
}

