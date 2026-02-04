<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Investment;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'user_id',
        'registration_number',
        'tax_id',
        'contact_person',
        'contact_email',
        'contact_phone',
        'company_address',
        'city',
        'country',
        'total_investment_capital',
        'available_capital',
        'invested_capital',
        'interest_earned',
        'risk_profile', // conservative, moderate, aggressive
        'minimum_investment_amount',
        'maximum_investment_amount',
        'preferred_interest_rate_min',
        'preferred_interest_rate_max',
        'investment_horizon', // short_term, medium_term, long_term
        'status', // active, suspended, pending_approval
        'credit_score',
        'investor_score',
        'auto_invest_enabled',
    ];

    protected $casts = [
        'total_investment_capital' => 'decimal:2',
        'available_capital' => 'decimal:2',
        'invested_capital' => 'decimal:2',
        'interest_earned' => 'decimal:2',
        'minimum_investment_amount' => 'decimal:2',
        'maximum_investment_amount' => 'decimal:2',
        'preferred_interest_rate_min' => 'decimal:2',
        'preferred_interest_rate_max' => 'decimal:2',
        'auto_invest_enabled' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function leaseInvestments()
    {
        return $this->hasMany(LeaseInvestment::class);
    }

    public function documents()
    {
        return $this->hasMany(InvestorDocument::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithAvailableCapital($query, $amount = 0)
    {
        return $query->where('available_capital', '>=', $amount);
    }

    public function scopeByRiskProfile($query, $profile)
    {
        return $query->where('risk_profile', $profile);
    }

    // Business logic methods
    public function canInvest($amount)
    {
        return $this->status === 'active' && 
               $this->available_capital >= $amount &&
               $amount >= $this->minimum_investment_amount &&
               $amount <= $this->maximum_investment_amount;
    }

    public function invest($amount, $lease)
    {
        if (!$this->canInvest($amount)) {
            throw new \Exception('Cannot invest this amount');
        }

        $this->decrement('available_capital', $amount);
        $this->increment('invested_capital', $amount);

        return $this;
    }

    public function receiveReturn($amount, $interest)
    {
        $this->increment('available_capital', $amount);
        $this->increment('interest_earned', $interest);
        $this->decrement('invested_capital', $amount);

        return $this;
    }

    public function getInvestmentPortfolio()
    {
        return [
            'total_invested' => $this->invested_capital,
            'available_capital' => $this->available_capital,
            'total_earned' => $this->interest_earned,
            'active_investments' => $this->leaseInvestments()->where('status', 'active')->count(),
            'completed_investments' => $this->leaseInvestments()->where('status', 'completed')->count(),
            'defaulted_investments' => $this->leaseInvestments()->where('status', 'defaulted')->count(),
            'average_return' => $this->calculateAverageReturn(),
        ];
    }

    private function calculateAverageReturn()
    {
        $completedInvestments = $this->leaseInvestments()->where('status', 'completed')->get();
        
        if ($completedInvestments->isEmpty()) {
            return 0;
        }

        $totalInvested = $completedInvestments->sum('amount_invested');
        $totalEarned = $completedInvestments->sum('interest_earned');

        return $totalInvested > 0 ? ($totalEarned / $totalInvested) * 100 : 0;
    }
}
