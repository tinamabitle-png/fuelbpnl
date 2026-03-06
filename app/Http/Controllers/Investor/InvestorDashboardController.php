<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\Lease;
use App\Models\LeaseInvestment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvestorDashboardController extends Controller
{
    

    /**
     * Display investor dashboard.
     */
    public function index()
    {
        // Check if user has investor role
        if (!Auth::user()->hasRole('investor')) {
            return redirect('/')
                ->with('error', 'You are not authorized to access the investor dashboard.');
        }
        
        $investor = Auth::user()->investor;
        
        if (!$investor) {
            return redirect('/')
                ->with('error', 'You are not registered as an investor. Please contact support.');
        }

        // If you want to use stats, make sure the methods exist
        // Temporarily comment out or fix any non-existent methods
        
        $stats = [
            'total_invested' => $investor->invested_capital ?? 0,
            'available_capital' => $investor->available_capital ?? 0,
            'interest_earned' => $investor->interest_earned ?? 0,
            'active_investments' => $investor->leaseInvestments()->where('status', 'active')->count() ?? 0,
            'completed_investments' => $investor->leaseInvestments()->where('status', 'completed')->count() ?? 0,
            'defaulted_investments' => $investor->leaseInvestments()->where('status', 'defaulted')->count() ?? 0,
            'total_returns' => $investor->leaseInvestments()->sum('interest_earned') ?? 0,
            // 'average_return' => $investor->getInvestmentPortfolio()['average_return'] ?? 0, // Comment out if method doesn't exist
            'average_return' => 0, // Temporary placeholder
        ];

        // Recent investments
        $recentInvestments = $investor->leaseInvestments()
            ->with('lease.user')
            ->latest()
            ->take(5)
            ->get();

        // Recent returns
        $recentReturns = $investor->leaseInvestments()
            ->with('returns')
            ->whereHas('returns')
            ->latest()
            ->take(5)
            ->get();

        // Upcoming returns
        $upcomingReturns = $investor->leaseInvestments()
            ->where('status', 'active')
            ->with('lease')
            ->whereHas('lease', function ($query) {
                $query->where('due_date', '<=', now()->addDays(7));
            })
            ->get();

        return view('investor.dashboard.index', compact(
            'investor',
            'stats',
            'recentInvestments',
            'recentReturns',
            'upcomingReturns'
        ));
    }

    /**
     * Display investor's investments.
     */
    public function investments(Request $request)
    {
        $investor = Auth::user()->investor;
        
        if (!$investor) {
            return redirect('/')
                ->with('error', 'You are not registered as an investor.');
        }
        
        $query = $investor->leaseInvestments()
            ->with(['lease.user', 'returns']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('lease', function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                      });
                });
            });
        }

        $investments = $query->latest()->paginate(20);

        return view('investor.investments.index', compact('investments'));
    }

    /**
     * Show investment details.
     */
    public function showInvestment(LeaseInvestment $investment)
    {
        // Check if investor owns this investment
        $investor = Auth::user()->investor;
        
        if (!$investor || $investment->investor_id !== $investor->id) {
            abort(403, 'Unauthorized access to this investment.');
        }

        $investment->load(['lease.user', 'lease.vouchers', 'lease.repayments', 'returns']);

        return view('investor.investments.show', compact('investment'));
    }

    /**
     * Display investment opportunities.
     */
    public function opportunities(Request $request)
    {
        $investor = Auth::user()->investor;
        
        if (!$investor) {
            return redirect('/')
                ->with('error', 'You are not registered as an investor.');
        }
        
        $query = Lease::where('status', 'active')
            ->whereDoesntHave('leaseInvestments', function ($q) use ($investor) {
                $q->where('investor_id', $investor->id);
            })
            ->with(['user']);

        // Check if these fields exist before using them
        if (isset($investor->maximum_investment_amount) && isset($investor->minimum_investment_amount)) {
            $query->where('total_amount', '<=', $investor->maximum_investment_amount)
                  ->where('total_amount', '>=', $investor->minimum_investment_amount);
        }

        // Filter by interest rate if fields exist
        if (isset($investor->preferred_interest_rate_min) && isset($investor->preferred_interest_rate_max)) {
            $query->whereBetween('interest_rate', [
                $investor->preferred_interest_rate_min,
                $investor->preferred_interest_rate_max
            ]);
        }

        // Filter by remaining term if field exists
        if (isset($investor->investment_horizon)) {
            if ($investor->investment_horizon === 'short_term') {
                $query->where('term_days', '<=', 30);
            } elseif ($investor->investment_horizon === 'medium_term') {
                $query->whereBetween('term_days', [31, 90]);
            } else {
                $query->where('term_days', '>', 90);
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $opportunities = $query->paginate(20);

        return view('investor.opportunities.index', compact('opportunities', 'investor'));
    }

    /**
     * Make an investment.
     */
    public function invest(Request $request)
    {
        $investor = Auth::user()->investor;
        
        if (!$investor) {
            return redirect('/')
                ->with('error', 'You are not registered as an investor.');
        }
        
        $request->validate([
            'lease_id' => 'required|exists:leases,id',
            'amount' => 'required|numeric|min:1000',
        ]);

        // Check available capital if field exists
        if (isset($investor->available_capital) && $request->amount > $investor->available_capital) {
            return back()->with('error', 'Insufficient available capital.');
        }

        $lease = Lease::findOrFail($request->lease_id);

        // Calculate percentage ownership
        $percentage = ($request->amount / $lease->total_amount) * 100;

        // Create investment
        $investment = $investor->leaseInvestments()->create([
            'lease_id' => $lease->id,
            'amount_invested' => $request->amount,
            'percentage_ownership' => $percentage,
            'interest_rate' => $lease->interest_rate,
            'expected_interest' => $request->amount * ($lease->interest_rate / 100) * ($lease->term_days / 365),
            'status' => 'active',
            'investment_date' => now(),
            'expected_maturity_date' => $lease->due_date,
            'maturity_date' => $lease->due_date,
        ]);

        // Update investor capital if method exists
        if (method_exists($investor, 'invest')) {
            $investor->invest($request->amount, $lease);
        } else {
            // Fallback: manually update
            $investor->invested_capital += $request->amount;
            $investor->available_capital -= $request->amount;
            $investor->save();
        }

        return redirect()->route('investor.investments.show', $investment)
            ->with('success', 'Investment made successfully.');
    }

    /**
     * Get investor profile.
     */
    public function profile()
    {
        $investor = Auth::user()->investor;
        
        if (!$investor) {
            return redirect('/')
                ->with('error', 'You are not registered as an investor.');
        }
        
        $investor->load('documents');
        
        return view('investor.profile', compact('investor'));
    }

    /**
     * Update investor preferences.
     */
    public function updatePreferences(Request $request)
    {
        $investor = Auth::user()->investor;
        
        if (!$investor) {
            return redirect('/')
                ->with('error', 'You are not registered as an investor.');
        }
        
        $validated = $request->validate([
            'risk_profile' => 'required|in:conservative,moderate,aggressive',
            'minimum_investment_amount' => 'required|numeric|min:1000',
            'maximum_investment_amount' => 'required|numeric|min:1000',
            'preferred_interest_rate_min' => 'required|numeric|min:1|max:100',
            'preferred_interest_rate_max' => 'required|numeric|min:1|max:100',
            'investment_horizon' => 'required|in:short_term,medium_term,long_term',
            'auto_invest_enabled' => 'boolean',
        ]);

        $investor->update($validated);

        return back()->with('success', 'Preferences updated successfully.');
    }

    /**
     * Get investor statements.
     */
    public function statements(Request $request)
    {
        $investor = Auth::user()->investor;
        
        if (!$investor) {
            return redirect('/')
                ->with('error', 'You are not registered as an investor.');
        }
        
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Get investments within date range
        $investments = $investor->leaseInvestments()
            ->whereBetween('investment_date', [$startDate, $endDate])
            ->with(['lease', 'returns'])
            ->get();

        // Get returns within date range
        $returns = $investor->leaseInvestments()
            ->whereHas('returns', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('payment_date', [$startDate, $endDate]);
            })
            ->with(['returns'])
            ->get();

        $totalInvested = $investments->sum('amount_invested') ?? 0;
        $totalReturns = $returns->sum('interest_earned') ?? 0;

        return view('investor.statements', compact(
            'investments',
            'returns',
            'totalInvested',
            'totalReturns',
            'startDate',
            'endDate'
        ));
    }
}
