<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investor;
use App\Models\Lease;
use App\Models\User;
use App\Models\InvestorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;

class InvestorController extends Controller
{
    /**
     * Display a listing of investors.
     */
    public function index(Request $request)
    {
        $query = Investor::with(['user.wallet', 'leaseInvestments.lease.user'])
            ->withCount(['leaseInvestments']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by risk profile
        if ($request->has('risk_profile')) {
            $query->where('risk_profile', $request->risk_profile);
        }

        // Sort
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $investors = $query->paginate(20);

        // Statistics
        $stats = [
            'total_investors' => Investor::count(),
            'active_investors' => Investor::where('status', 'active')->count(),
            'total_invested_capital' => Investor::sum('invested_capital'),
            'total_available_capital' => Investor::sum('available_capital'),
            'total_interest_earned' => Investor::sum('interest_earned'),
            'total_wallet_balance' => Investor::with('user.wallet')
                ->get()
                ->sum(fn (Investor $investor) => $investor->wallet_balance),
        ];

        return view('admin.investors.index', compact('investors', 'stats'));
    }

    /**
     * Show the form for creating a new investor.
     */
    public function create()
    {
        $users = User::with('roles')
            ->whereDoesntHave('investor')
            ->orderBy('name')
            ->get();

        return view('admin.investors.create', compact('users'));
    }

    /**
     * Store a newly created investor.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id|unique:investors,user_id',
            'registration_number' => 'required|string|unique:investors',
            'tax_id' => 'nullable|string',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_phone' => 'required|string',
            'company_address' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'total_investment_capital' => 'required|numeric|min:0',
            'risk_profile' => 'required|in:conservative,moderate,aggressive',
            'minimum_investment_amount' => 'required|numeric|min:1000',
            'maximum_investment_amount' => 'required|numeric|min:1000',
            'preferred_interest_rate_min' => 'required|numeric|min:1|max:100',
            'preferred_interest_rate_max' => 'required|numeric|min:1|max:100',
            'investment_horizon' => 'required|in:short_term,medium_term,long_term',
            'status' => 'required|in:active,pending_approval,suspended',
        ]);

        DB::transaction(function () use ($validated) {
            // Create investor
            $investor = Investor::create(array_merge($validated, [
                'available_capital' => $validated['total_investment_capital'],
            ]));

            // Assign investor role to user
            $user = User::find($validated['user_id']);
            $investorRole = Role::where('name', 'investor')->first();
            if ($investorRole) {
                $user->assignRole($investorRole);
            }

            // Log activity
            activity()
                ->performedOn($investor)
                ->causedBy(auth()->user())
                ->withProperties($validated)
                ->log('investor_created');
        });

        return redirect()->route('admin.investors.index')
            ->with('success', 'Investor created successfully.');
    }

    /**
     * Display the specified investor.
     */
    public function show(Investor $investor)
    {
        $investor->load([
            'user', 
            'user.wallet',
            'documents',
            'leaseInvestments.lease.user',
            'leaseInvestments.lease.vouchers',
            'leaseInvestments.returns'
        ]);

        $portfolio = $investor->getInvestmentPortfolio();

        // Recent investments
        $recentInvestments = $investor->leaseInvestments()
            ->with('lease.user')
            ->latest()
            ->take(10)
            ->get();

        // Recent returns
        $recentReturns = $investor->leaseInvestments()
            ->with('returns')
            ->whereHas('returns')
            ->latest()
            ->take(10)
            ->get();

        $approvedLeases = $investor->leaseInvestments()
            ->with(['lease.user', 'lease.vouchers'])
            ->whereHas('lease', function ($query) {
                $query->where('status', 'active')
                    ->whereHas('user', fn ($q) => $q->where('credit_score', '<', 650))
                    ->whereHas('vouchers', fn ($q) => $q->whereIn('status', ['approved', 'redeemed']));
            })
            ->latest()
            ->paginate(10, ['*'], 'approved_leases_page');

        $assignableLeases = Lease::with(['user', 'vouchers', 'leaseInvestments'])
            ->where('status', 'active')
            ->whereHas('user', fn ($query) => $query->where('credit_score', '<', 650))
            ->whereHas('vouchers', fn ($query) => $query->whereIn('status', ['approved', 'redeemed']))
            ->whereDoesntHave('leaseInvestments', fn ($query) => $query->where('investor_id', $investor->id))
            ->latest()
            ->take(40)
            ->get()
            ->filter(fn (Lease $lease) => (float) $lease->investor_funding_remaining > 0)
            ->values();

        return view('admin.investors.show', compact(
            'investor',
            'portfolio',
            'recentInvestments',
            'recentReturns',
            'approvedLeases',
            'assignableLeases'
        ));
    }

    /**
     * Show the form for editing the specified investor.
     */
    public function edit(Investor $investor)
    {
        $investor->load('user');
        return view('admin.investors.edit', compact('investor'));
    }

    /**
     * Update the specified investor.
     */
    public function update(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'registration_number' => [
                'required',
                'string',
                Rule::unique('investors')->ignore($investor->id),
            ],
            'tax_id' => 'nullable|string',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_phone' => 'required|string',
            'company_address' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
            'risk_profile' => 'required|in:conservative,moderate,aggressive',
            'minimum_investment_amount' => 'required|numeric|min:1000',
            'maximum_investment_amount' => 'required|numeric|min:1000',
            'preferred_interest_rate_min' => 'required|numeric|min:1|max:100',
            'preferred_interest_rate_max' => 'required|numeric|min:1|max:100',
            'investment_horizon' => 'required|in:short_term,medium_term,long_term',
            'status' => 'required|in:active,pending_approval,suspended',
        ]);

        $investor->update($validated);

        return redirect()->route('admin.investors.show', $investor)
            ->with('success', 'Investor updated successfully.');
    }

    /**
     * Remove the specified investor profile.
     */
    public function destroy(Investor $investor)
    {
        DB::transaction(function () use ($investor) {
            $user = $investor->user;
            $investor->delete();

            if ($user && $user->hasRole('investor')) {
                $user->removeRole('investor');
            }
        });

        return redirect()->route('admin.investors.index')
            ->with('success', 'Investor removed successfully.');
    }

    /**
     * Update investor capital.
     */
    public function updateCapital(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'type' => 'required|in:add,withdraw',
            'destination' => 'nullable|in:capital,wallet,both',
            'amount' => 'required|numeric|min:100',
            'reason' => 'required|string',
        ]);

        DB::transaction(function () use ($investor, $validated) {
            $destination = $validated['destination'] ?? 'capital';
            $amount = (float) $validated['amount'];

            if ($validated['type'] === 'add') {
                if (in_array($destination, ['capital', 'both'], true)) {
                    $investor->increment('total_investment_capital', $amount);
                    $investor->increment('available_capital', $amount);
                }

                if (in_array($destination, ['wallet', 'both'], true)) {
                    if (!$investor->user) {
                        throw new \RuntimeException('This finance company does not have a linked user wallet.');
                    }

                    $wallet = $investor->user->wallet()->firstOrCreate([], [
                        'balance' => 0,
                        'outstanding_balance' => 0,
                        'total_credit_used' => 0,
                        'total_repayments' => 0,
                        'currency' => 'ZAR',
                    ]);

                    $wallet->addFunds($amount, 'Admin finance company funding: ' . $validated['reason'], [
                        'source' => 'admin_investor_funding',
                        'investor_id' => $investor->id,
                        'admin_id' => auth()->id(),
                        'destination' => $destination,
                    ]);
                }
            } else {
                if ($destination !== 'capital') {
                    throw new \RuntimeException('Withdrawals are currently limited to the finance company capital account.');
                }

                if ($investor->available_capital < $amount) {
                    throw new \Exception('Insufficient available capital.');
                }
                $investor->decrement('total_investment_capital', $amount);
                $investor->decrement('available_capital', $amount);
            }

            $investor->refresh();

            // Log the transaction
            activity()
                ->performedOn($investor)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_capital' => $validated['type'] === 'add' ? 
                        $investor->total_investment_capital - (in_array($destination, ['capital', 'both'], true) ? $amount : 0) : 
                        $investor->total_investment_capital + $amount,
                    'new_capital' => $investor->total_investment_capital,
                    'available_capital' => $investor->available_capital,
                    'wallet_balance' => $investor->wallet_balance,
                    'amount' => $amount,
                    'type' => $validated['type'],
                    'destination' => $destination,
                    'reason' => $validated['reason'],
                ])
                ->log('investor_capital_adjusted');
        });

        return back()->with('success', 'Capital updated successfully.');
    }

    /**
     * Upload investor document.
     */
    public function uploadDocument(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'document_type' => 'required|in:registration_certificate,tax_certificate,id_card,proof_of_address',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'expiry_date' => 'nullable|date',
        ]);

        $path = $request->file('document')->store('investor-documents', 'public');

        $document = $investor->documents()->create([
            'document_type' => $validated['document_type'],
            'document_path' => $path,
            'document_name' => $request->file('document')->getClientOriginalName(),
            'expiry_date' => $validated['expiry_date'] ?? null,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Verify investor document.
     */
    public function verifyDocument(Request $request, InvestorDocument $document)
    {
        $document->verify(auth()->id());

        // Check if all required documents are verified
        $investor = $document->investor;
        $requiredDocs = ['registration_certificate', 'tax_certificate', 'id_card', 'proof_of_address'];
        $verifiedDocs = $investor->documents()
            ->whereIn('document_type', $requiredDocs)
            ->where('verified', true)
            ->count();

        if ($verifiedDocs === count($requiredDocs) && $investor->status === 'pending_approval') {
            $investor->update(['status' => 'active']);
        }

        return back()->with('success', 'Document verified successfully.');
    }

    /**
     * Toggle auto-invest.
     */
    public function toggleAutoInvest(Investor $investor)
    {
        $investor->update([
            'auto_invest_enabled' => !$investor->auto_invest_enabled,
        ]);

        $status = $investor->auto_invest_enabled ? 'enabled' : 'disabled';
        return back()->with('success', "Auto-invest {$status} successfully.");
    }

    /**
     * Get investment opportunities for investor.
     */
    public function investmentOpportunities(Request $request, Investor $investor)
    {
        $query = Lease::where('status', 'active')
            ->whereHas('user', function ($q) {
                $q->where('credit_score', '<', 650);
            })
            ->whereHas('vouchers', function ($q) {
                $q->whereIn('status', ['approved', 'redeemed']);
            })
            ->whereDoesntHave('leaseInvestments', function ($q) use ($investor) {
                $q->where('investor_id', $investor->id);
            })
            ->with(['user', 'vouchers', 'leaseInvestments'])
            ->where('total_amount', '<=', $investor->maximum_investment_amount)
            ->where('total_amount', '>=', $investor->minimum_investment_amount);

        // Filter by interest rate
        $query->whereBetween('interest_rate', [
            $investor->preferred_interest_rate_min,
            $investor->preferred_interest_rate_max
        ]);

        // Filter by remaining term
        if ($investor->investment_horizon === 'short_term') {
            $query->where('term_days', '<=', 30);
        } elseif ($investor->investment_horizon === 'medium_term') {
            $query->whereBetween('term_days', [31, 90]);
        } else {
            $query->where('term_days', '>', 90);
        }

        $opportunities = $query->paginate(20);

        return view('admin.investors.opportunities', compact('investor', 'opportunities'));
    }

    /**
     * Make investment.
     */
    public function makeInvestment(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'lease_id' => 'required|exists:leases,id',
            'amount' => 'required|numeric|min:1000|max:' . $investor->available_capital,
            'percentage_ownership' => 'nullable|numeric|min:1|max:100',
        ]);

        try {
            DB::transaction(function () use ($investor, $validated) {
                $lockedInvestor = Investor::whereKey($investor->id)->lockForUpdate()->firstOrFail();
                $lease = Lease::with(['user', 'vouchers', 'leaseInvestments'])
                    ->whereKey($validated['lease_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $amount = (float) $validated['amount'];
                $remaining = (float) $lease->investor_funding_remaining;

                if (!$lease->is_investor_approved) {
                    throw new \RuntimeException('Only approved subprime voucher leases can be funded by investors.');
                }

                if ($amount > $remaining) {
                    throw new \RuntimeException('This investment would overfund the lease. Remaining capacity is R ' . number_format($remaining, 2) . '.');
                }

                if (!$lockedInvestor->canInvest($amount)) {
                    throw new \RuntimeException('Investor capital or investment limits do not allow this amount.');
                }

                $percentage = $validated['percentage_ownership'] ?? ($lease->total_amount > 0 ? ($amount / (float) $lease->total_amount) * 100 : 0);

                $investment = $lockedInvestor->leaseInvestments()->create([
                    'lease_id' => $lease->id,
                    'amount_invested' => $amount,
                    'percentage_ownership' => $percentage,
                    'interest_rate' => $lease->interest_rate,
                    'expected_interest' => $amount * ((float) $lease->interest_rate / 100) * ((int) $lease->term_days / 365),
                    'status' => 'active',
                    'investment_date' => now(),
                    'expected_maturity_date' => $lease->due_date,
                    'maturity_date' => $lease->due_date,
                    'payment_schedule' => 'daily',
                ]);

                $lockedInvestor->invest($amount, $lease);

                activity()
                    ->performedOn($investment)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'investor_id' => $lockedInvestor->id,
                        'lease_id' => $lease->id,
                        'amount' => $amount,
                        'percentage' => $percentage,
                    ])
                    ->log('investment_made');
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.investors.show', $investor)
            ->with('success', 'Investment made successfully.');
    }
}
