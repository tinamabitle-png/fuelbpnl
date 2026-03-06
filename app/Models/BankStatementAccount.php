<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BankStatementAccount extends Model
{
    protected $fillable = [
        'upload_id',
        'bank_name',
        'account_number_masked',
        'period_start',
        'period_end',
        'opening_balance',
        'closing_balance',
        'currency',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(BankStatementUpload::class, 'upload_id');
    }

    public function feature(): HasOne
    {
        return $this->hasOne(BankStatementFeature::class, 'account_id');
    }
}

