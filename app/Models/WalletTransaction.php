<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relationships
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Wallet::class, 'id', 'id', 'wallet_id', 'user_id');
    }

    // Scopes
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Business logic methods
    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
        return $this;
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
        return $this;
    }

    public function reverse($reason = '')
    {
        if ($this->status !== 'completed') {
            throw new \Exception('Only completed transactions can be reversed');
        }

        $reverseTransaction = $this->replicate();
        $reverseTransaction->type = $this->type === 'credit' ? 'debit' : 'credit';
        $reverseTransaction->balance_before = $this->balance_after;
        $reverseTransaction->balance_after = $this->balance_before;
        $reverseTransaction->description = 'Reversal: ' . $this->description . ' - ' . $reason;
        $reverseTransaction->reference = 'REV-' . $this->reference;
        $reverseTransaction->status = 'completed';
        $reverseTransaction->metadata = array_merge($this->metadata ?? [], [
            'reversal_of' => $this->id,
            'reason' => $reason,
        ]);
        $reverseTransaction->save();

        // Update wallet balance
        if ($reverseTransaction->type === 'credit') {
            $this->wallet->increment('balance', $reverseTransaction->amount);
        } else {
            $this->wallet->decrement('balance', $reverseTransaction->amount);
        }

        $this->update(['status' => 'reversed']);

        return $reverseTransaction;
    }
}