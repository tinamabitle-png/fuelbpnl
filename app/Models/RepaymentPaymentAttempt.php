<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RepaymentPaymentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'method',
        'tx_ref',
        'flw_ref',
        'amount',
        'currency',
        'status',
        'repayment_ids',
        'meta',
        'provider_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'repayment_ids' => 'array',
        'meta' => 'array',
        'provider_response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

