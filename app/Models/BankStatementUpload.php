<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementUpload extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'source_reference',
        'original_filename',
        'mime_type',
        'file_size',
        'temporary_path',
        'status',
        'ocr_provider',
        'ocr_processor_type',
        'ocr_region',
        'ocr_confidence',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'ocr_confidence' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(BankStatementAccount::class, 'upload_id');
    }

    public function creditDecisions(): HasMany
    {
        return $this->hasMany(CreditDecision::class, 'upload_id');
    }
}

