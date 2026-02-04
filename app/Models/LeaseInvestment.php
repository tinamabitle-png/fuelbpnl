<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaseInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'investor_id',
        'amount_invested',
        'percentage_ownership',
        'interest_rate',
        'expected_interest',
        'interest_earned',
        'status', // active, completed, defaulted, cancelled
        'investment_date',
        'maturity_date',
        'expected_maturity_date',
        'actual_maturity_date',
        'return_on_investment',
        'payment_schedule', // daily, weekly, monthly
        'auto_reinvest',
    ];

    protected $casts = [
        'amount_invested' => 'decimal:2',
        'percentage_ownership' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'expected_interest' => 'decimal:2',
        'interest_earned' => 'decimal:2',
        'return_on_investment' => 'decimal:2',
        'investment_date' => 'datetime',
        'maturity_date' => 'datetime',
        'expected_maturity_date' => 'datetime',
        'actual_maturity_date' => 'datetime',
        'auto_reinvest' => 'boolean',
    ];

    // Relationships
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    public function returns()
    {
        return $this->hasMany(InvestmentReturn::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDefaulted($query)
    {
        return $query->where('status', 'defaulted');
    }

    // Business logic methods
    public function calculateExpectedReturn()
    {
        $days = $this->investment_date->diffInDays($this->expected_maturity_date);
        return $this->amount_invested * ($this->interest_rate / 100) * ($days / 365);
    }

    public function recordReturn($amount, $type = 'interest')
    {
        $return = $this->returns()->create([
            'amount' => $amount,
            'type' => $type,
            'payment_date' => now(),
            'reference' => 'RET-' . time() . '-' . $this->id,
        ]);

        $this->increment('interest_earned', $amount);
        
        // Update investor
        $this->investor->receiveReturn($this->amount_invested, $amount);

        // Check if investment is completed
        if ($this->interest_earned >= $this->expected_interest * 0.95) {
            $this->markAsCompleted();
        }

        return $return;
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'actual_maturity_date' => now(),
            'return_on_investment' => $this->calculateReturnOnInvestment(),
        ]);

        return $this;
    }

    public function markAsDefaulted()
    {
        $this->update(['status' => 'defaulted']);

        // Update investor metrics
        $this->investor->decrement('invested_capital', $this->amount_invested);

        return $this;
    }

    private function calculateReturnOnInvestment()
    {
        if ($this->amount_invested == 0) return 0;
        return ($this->interest_earned / $this->amount_invested) * 100;
    }
}
