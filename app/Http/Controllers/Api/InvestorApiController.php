<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\LeaseInvestment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestorApiController extends Controller
{
    /**
     * Get investor portfolio summary
     */
    public function portfolioSummary()
    {
        $investor = Auth::user()->investor;
        
        return response()->json([
            'success' => true,
            'data' => [
                'portfolio' => $investor->getInvestmentPortfolio(),
                'company_info' => $investor->only([
                    'company_name', 'company_type', 'registration_number',
                    'risk_profile', 'investor_score', 'strategic_partner'
                ]),
                'capital' => [
                    'total_capital' => $investor->total_investment_capital,
                    'available_capital' => $investor->available_capital,
                    'invested_capital' => $investor->invested_capital,
                    'interest_earned' => $investor->interest_earned,
                ],
                'performance' => [
                    'average_return_rate' => $investor->average_return_rate,
                    'default_rate' => $investor->default_rate,
                    'total_investments' => $investor->total_investments,
                    'active_investments' => $investor->active_investments,
                    'completed_investments' => $investor->completed_investments,
                ],
            ]
        ]);
    }

    /**
     * Get investment opportunities matching investor criteria
     */
    public function opportunities(Request $request)
    {
        $investor = Auth::user()->investor;
        
        $opportunities = Lease::where('status', 'active')
            ->whereDoesntHave('leaseInvestments', function ($q) use ($investor) {
                $q->where('investor_id', $investor->id);
            })
            ->whereBetween('total_amount', [
                $investor->minimum_investment_amount,
                $investor->maximum_investment_amount
            ])
            ->whereBetween('interest_rate', [
                $investor->preferred_interest_rate_min,
                $investor->preferred_interest_rate_max
            ])
            ->with(['user', 'vouchers'])
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'opportunities' => $opportunities,
                'investor_criteria' => [
                    'min_amount' => $investor->minimum_investment_amount,
                    'max_amount' => $investor->maximum_investment_amount,
                    'min_interest' => $investor->preferred_interest_rate_min,
                    'max_interest' => $investor->preferred_interest_rate_max,
                    'risk_profile' => $investor->risk_profile,
                    'horizon' => $investor->investment_horizon,
                ]
            ]
        ]);
    }
}
