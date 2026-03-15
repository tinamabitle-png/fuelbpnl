<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\FuelVoucher;
use App\Models\VirtualCard;

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
        return $this->available_balance >= $amount;
    }

    public function reservedVoucherBalance(): float
    {
        // Wallet-funded vouchers reserve funds until redeemed/cancelled/expired.
        return (float) FuelVoucher::query()
            ->where('user_id', $this->user_id)
            ->where('funding_source', 'wallet')
            ->whereIn('status', ['issued', 'approved'])
            ->where('expires_at', '>', now())
            ->sum('amount');
    }

    public function allocatedCardBalance(): float
    {
        return (float) VirtualCard::query()
            ->where('user_id', $this->user_id)
            ->whereIn('status', ['active', 'frozen'])
            ->sum('allocated_amount');
    }

    public function getReservedVoucherBalanceAttribute(): float
    {
        return $this->reservedVoucherBalance();
    }

    public function getAllocatedCardBalanceAttribute(): float
    {
        return $this->allocatedCardBalance();
    }

    public function getAvailableBalanceAttribute(): float
    {
        $available = (float) $this->balance
            - (float) $this->reserved_voucher_balance
            - (float) $this->allocated_card_balance;
        return max(0.0, $available);
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
