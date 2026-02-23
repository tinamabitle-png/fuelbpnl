<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'outstanding_balance',
        'total_credit_used',
        'total_repayments',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'total_credit_used' => 'decimal:2',
        'total_repayments' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // Business logic methods
    public function canAfford($amount)
    {
        return $this->balance >= $amount;
    }

    public function hasAvailableCredit($amount)
    {
        $limit = (float) optional($this->user->creditLimit)->limit;
        $availableCredit = $limit - (float) $this->outstanding_balance;
        return $availableCredit >= $amount;
    }

    public function addFunds($amount, $description = 'Wallet topup', $metadata = [])
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type' => 'credit',
            'amount' => $amount,
            'balance_before' => $this->balance - $amount,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference' => 'TOPUP-' . time() . '-' . strtoupper(uniqid()),
            'status' => 'completed',
            'metadata' => $metadata,
        ]);
    }

    public function deductFunds($amount, $description = 'Payment', $metadata = [])
    {
        if (!$this->canAfford($amount)) {
            throw new \Exception('Insufficient funds');
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $this->balance + $amount,
            'balance_after' => $this->balance,
            'description' => $description,
            'reference' => 'PAY-' . time() . '-' . strtoupper(uniqid()),
            'status' => 'completed',
            'metadata' => $metadata,
        ]);
    }
}
