<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementFeature extends Model
{
    protected $fillable = [
        'account_id',
        'salary_inflows',
        'avg_monthly_income',
        'avg_monthly_expenses',
        'spend_volatility',
        'overdraft_count',
        'nsf_count',
        'avg_daily_balance',
        'cash_buffer_days',
        'risk_score',
        'risk_band',
        'computed_at',
    ];

    protected $casts = [
        'salary_inflows' => 'decimal:2',
        'avg_monthly_income' => 'decimal:2',
        'avg_monthly_expenses' => 'decimal:2',
        'spend_volatility' => 'decimal:4',
        'overdraft_count' => 'integer',
        'nsf_count' => 'integer',
        'avg_daily_balance' => 'decimal:2',
        'cash_buffer_days' => 'decimal:2',
        'risk_score' => 'integer',
        'computed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankStatementAccount::class, 'account_id');
    }
}

