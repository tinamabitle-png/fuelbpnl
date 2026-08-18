<?php

namespace App\Http\Controllers;

use App\Models\FinanceTeamSubscription;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceTeamSubscriptionController extends Controller
{
    public function start(Request $request, PaystackService $paystack): RedirectResponse
    {
        $planSlugs = array_keys(FinanceTeamSubscription::plans());
        $validated = $request->validate([
            'plan_slug' => ['required', 'string', Rule::in($planSlugs)],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $plan = FinanceTeamSubscription::plan($validated['plan_slug']);
        if (!$plan) {
            return back()->withInput()->with('error', 'Please choose a valid finance-team plan.');
        }

        try {
            $subscription = DB::transaction(function () use ($validated, $plan, $paystack): FinanceTeamSubscription {
                $subscription = FinanceTeamSubscription::create([
                    'user_id' => Auth::id(),
                    'company_name' => $validated['company_name'],
                    'contact_name' => $validated['contact_name'] ?? null,
                    'email' => strtolower((string) $validated['email']),
                    'phone' => $validated['phone'] ?? null,
                    'plan_slug' => $plan['slug'],
                    'plan_name' => $plan['name'],
                    'amount' => $plan['amount'],
                    'currency' => $plan['currency'],
                    'billing_cycle' => $plan['billing_cycle'],
                    'loan_book_limit' => $plan['loan_book_limit'],
                    'status' => 'pending',
                    'metadata' => [
                        'source' => 'welcome_pricing',
                        'requested_at' => now()->toIso8601String(),
                    ],
                ]);

                $checkout = $paystack->initializeFinanceTeamSubscriptionCheckout(
                    $subscription,
                    route('finance-team-subscriptions.paystack.callback')
                );

                $subscription->forceFill([
                    'paystack_reference' => $checkout['reference'],
                    'paystack_access_code' => $checkout['access_code'],
                    'paystack_authorization_url' => $checkout['authorization_url'],
                ])->save();

                return $subscription;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'We could not start the subscription payment. Please try again or contact support.');
        }

        return redirect()->away((string) $subscription->paystack_authorization_url);
    }

    public function callback(Request $request, PaystackService $paystack): RedirectResponse
    {
        $reference = trim((string) ($request->query('reference') ?: $request->query('trxref')));
        if ($reference === '') {
            return redirect('/')->with('error', 'The payment provider did not return a finance-team subscription reference.');
        }

        $subscription = FinanceTeamSubscription::where('paystack_reference', $reference)->first();
        if (!$subscription) {
            return redirect('/')->with('error', 'We could not match this finance-team subscription payment.');
        }

        try {
            $data = $paystack->verifyTransaction($reference);
            $metadata = (array) ($data['metadata'] ?? []);
            $paidMinor = (int) ($data['amount'] ?? 0);
            $expectedMinor = (int) round(((float) $subscription->amount) * 100);

            if (($metadata['scope'] ?? null) !== 'finance_team_subscription') {
                throw new \RuntimeException('This payment was not created for a finance-team subscription.');
            }

            if ((int) ($metadata['subscription_id'] ?? 0) !== (int) $subscription->id) {
                throw new \RuntimeException('The payment does not match this subscription.');
            }

            if ($paidMinor < $expectedMinor) {
                throw new \RuntimeException('The paid amount is lower than the selected subscription plan.');
            }

            $subscription->forceFill([
                'status' => 'active',
                'paid_at' => now(),
                'expires_at' => now()->addMonth(),
                'metadata' => array_merge((array) $subscription->metadata, [
                    'paystack_verified_at' => now()->toIso8601String(),
                    'paystack_transaction' => $data,
                ]),
            ])->save();
        } catch (\Throwable $e) {
            report($e);

            $subscription->forceFill([
                'status' => 'failed',
                'metadata' => array_merge((array) $subscription->metadata, [
                    'paystack_failed_at' => now()->toIso8601String(),
                    'failure_reason' => $e->getMessage(),
                ]),
            ])->save();

            return redirect('/')->with('error', 'The finance-team subscription payment could not be verified. Please contact support.');
        }

        return redirect('/')->with('success', 'Finance-team subscription activated. Your loan-book access is ready for admin assignment.');
    }
}
