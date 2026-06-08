<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Lease extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'principal_amount',
        'interest_rate',
        'interest_amount',
        'total_amount',
        'term_days',
        'daily_repayment',
        'repayment_frequency',
        'status',
        'issued_at',
        'due_date',
        'completed_at',
        'defaulted_at',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'daily_repayment' => 'decimal:2',
        'issued_at' => 'datetime',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'defaulted_at' => 'datetime',
    ];

    protected $appends = ['remaining_balance', 'days_overdue', 'progress_percentage'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vouchers()
    {
        return $this->hasMany(FuelVoucher::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }

    // Accessors
    public function getRemainingBalanceAttribute()
    {
        $paid = $this->repayments()->where('status', 'paid')->sum('amount');
        return max(0, $this->total_amount - $paid);
    }

    public function getDaysOverdueAttribute()
    {
        if ($this->status === 'active' && $this->due_date < now()) {
            return now()->diffInDays($this->due_date);
        }
        return 0;
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->total_amount == 0) return 0;
        $paid = $this->total_amount - $this->remaining_balance;
        return min(100, ($paid / $this->total_amount) * 100);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }
   
    public function leaseInvestments()
    {
        return $this->hasMany(LeaseInvestment::class);
    }

    public function investors()
    {
        return $this->hasManyThrough(Investor::class, LeaseInvestment::class, 'lease_id', 'id', 'id', 'investor_id');
    }

    public function getIsInvestorFundedAttribute()
    {
        return $this->leaseInvestments()->exists();
    }

    public function getTotalInvestorFundingAttribute()
    {
        return $this->leaseInvestments()->sum('amount_invested');
    }

    public function getInvestorOwnershipPercentageAttribute()
    {
        $totalInvestment = $this->leaseInvestments()->sum('amount_invested');
        return $totalInvestment > 0 ? ($totalInvestment / $this->total_amount) * 100 : 0;
    }

    public function getInvestorFundedAmountAttribute(): float
    {
        return (float) $this->leaseInvestments()
            ->whereIn('status', ['active', 'completed'])
            ->sum('amount_invested');
    }

    public function getInvestorFundingRemainingAttribute(): float
    {
        return max(0.0, (float) $this->total_amount - (float) $this->investor_funded_amount);
    }

    public function getRiskBandAttribute(): string
    {
        $score = (int) ($this->user?->credit_score ?? 0);

        if ($score > 0 && $score < 650) {
            return 'subprime';
        }

        if ($score >= 650 && $score < 720) {
            return 'near-prime';
        }

        return 'prime';
    }

    public function getIsSubprimeAttribute(): bool
    {
        return $this->risk_band === 'subprime';
    }

    public function getIsInvestorApprovedAttribute(): bool
    {
        return $this->status === 'active'
            && $this->is_subprime
            && $this->vouchers()->whereIn('status', ['approved', 'redeemed'])->exists();
    }

    public function getIsDefaultedAttribute()
    {
        return $this->status === 'defaulted';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    // Business logic methods
    public function markAsPaid($amount, $paymentMethod = 'manual', $reference = null)
    {
        $remaining = $this->remaining_balance;
        $amountToApply = min($amount, $remaining);

        // Create repayment record
        $repayment = $this->repayments()->create([
            'user_id' => $this->user_id,
            'amount' => $amountToApply,
            'due_date' => now(),
            'paid_at' => now(),
            'status' => 'paid',
            'payment_method' => $paymentMethod,
            'transaction_reference' => $reference,
        ]);

        // Update user's wallet
        if ($this->user->wallet) {
            $this->user->wallet->decrement('outstanding_balance', $amountToApply);
            $this->user->wallet->increment('total_repayments', $amountToApply);
        }
        if ($this->user->creditLimit) {
            $this->user->creditLimit->releaseCredit($amountToApply);
        }

        // Check if lease is fully paid
        if ($this->remaining_balance <= 0) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return $repayment;
    }

    public function markAsDefaulted()
    {
        if ($this->status !== 'active') {
            throw new \Exception('Only active leases can be marked as defaulted');
        }

        $this->update([
            'status' => 'defaulted',
            'defaulted_at' => now(),
        ]);

        // Update user status
        $this->user->update(['status' => 'flagged']);

        return $this;
    }

    public function extend($extraDays)
    {
        $this->update([
            'due_date' => $this->due_date->addDays($extraDays),
            'term_days' => $this->term_days + $extraDays,
        ]);

        // Create new repayment entries for extended period
        for ($i = 1; $i <= $extraDays; $i++) {
            $this->repayments()->create([
                'user_id' => $this->user_id,
                'amount' => $this->daily_repayment,
                'due_date' => $this->due_date->copy()->subDays($extraDays - $i),
                'status' => 'pending',
            ]);
        }

        return $this;
    }

    /**
     * Build repayment terms from lease defaults when no schedule exists yet.
     * Used when voucher is redeemed and admin has not created custom repayments.
     */
    public function ensureRepaymentSchedule(?Carbon $startDate = null): void
    {
        if ($this->repayments()->exists()) {
            return;
        }

        $start = ($startDate ?? now())->copy()->startOfDay();
        $frequency = strtolower((string) ($this->repayment_frequency ?? 'daily'));

        if ($frequency === 'weekly') {
            $installmentCount = (int) ceil(max(1, (int) $this->term_days) / 7);
            $remaining = round((float) $this->total_amount, 2);
            $baseInstallment = round($remaining / max($installmentCount, 1), 2);

            for ($i = 1; $i <= $installmentCount; $i++) {
                $amount = $i === $installmentCount ? $remaining : min($remaining, $baseInstallment);
                $remaining = round($remaining - $amount, 2);
                $dayOffset = min($i * 7, (int) $this->term_days);

                $this->repayments()->create([
                    'user_id' => $this->user_id,
                    'amount' => $amount,
                    'due_date' => $start->copy()->addDays($dayOffset)->toDateString(),
                    'status' => 'pending',
                ]);
            }
        } else {
            for ($i = 1; $i <= $this->term_days; $i++) {
                $this->repayments()->create([
                    'user_id' => $this->user_id,
                    'amount' => $this->daily_repayment,
                    'due_date' => $start->copy()->addDays($i)->toDateString(),
                    'status' => 'pending',
                ]);
            }
        }

        $expectedDueDate = $start->copy()->addDays($this->term_days)->toDateString();
        if (!$this->due_date || Carbon::parse($this->due_date)->lt($start)) {
            $this->update(['due_date' => $expectedDueDate]);
        }
    }
}
