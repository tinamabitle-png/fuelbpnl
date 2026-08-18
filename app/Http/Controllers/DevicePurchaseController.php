<?php

namespace App\Http\Controllers;

use App\Models\DevicePurchase;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DevicePurchaseController extends Controller
{
    public function start(Request $request, PaystackService $paystack): RedirectResponse
    {
        $validated = $request->validate([
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        $product = DevicePurchase::bwiserPro();

        try {
            $purchase = DB::transaction(function () use ($validated, $product, $paystack): DevicePurchase {
                $purchase = DevicePurchase::create([
                    'user_id' => Auth::id(),
                    'product_slug' => $product['slug'],
                    'product_name' => $product['name'],
                    'buyer_name' => $validated['buyer_name'] ?? null,
                    'email' => strtolower((string) $validated['email']),
                    'phone' => $validated['phone'] ?? null,
                    'amount' => $product['amount'],
                    'currency' => $product['currency'],
                    'status' => 'pending',
                    'metadata' => [
                        'source' => 'welcome_device_card',
                        'requested_at' => now()->toIso8601String(),
                    ],
                ]);

                $checkout = $paystack->initializeDevicePurchaseCheckout(
                    $purchase,
                    route('device-purchases.paystack.callback')
                );

                $purchase->forceFill([
                    'paystack_reference' => $checkout['reference'],
                    'paystack_access_code' => $checkout['access_code'],
                    'paystack_authorization_url' => $checkout['authorization_url'],
                ])->save();

                return $purchase;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'We could not start the device payment. Please try again or contact support.');
        }

        return redirect()->away((string) $purchase->paystack_authorization_url);
    }

    public function callback(Request $request, PaystackService $paystack): RedirectResponse
    {
        $reference = trim((string) ($request->query('reference') ?: $request->query('trxref')));
        if ($reference === '') {
            return redirect('/')->with('error', 'The payment provider did not return a device purchase reference.');
        }

        $purchase = DevicePurchase::where('paystack_reference', $reference)->first();
        if (!$purchase) {
            return redirect('/')->with('error', 'We could not match this device purchase payment.');
        }

        try {
            $data = $paystack->verifyTransaction($reference);
            $metadata = (array) ($data['metadata'] ?? []);
            $paidMinor = (int) ($data['amount'] ?? 0);
            $expectedMinor = (int) round(((float) $purchase->amount) * 100);

            if (($metadata['scope'] ?? null) !== 'device_purchase') {
                throw new \RuntimeException('This payment was not created for a device purchase.');
            }

            if ((int) ($metadata['purchase_id'] ?? 0) !== (int) $purchase->id) {
                throw new \RuntimeException('The payment does not match this device purchase.');
            }

            if ($paidMinor < $expectedMinor) {
                throw new \RuntimeException('The paid amount is lower than the selected device price.');
            }

            $purchase->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => array_merge((array) $purchase->metadata, [
                    'payment_verified_at' => now()->toIso8601String(),
                    'payment_transaction' => $data,
                ]),
            ])->save();
        } catch (\Throwable $e) {
            report($e);

            $purchase->forceFill([
                'status' => 'failed',
                'metadata' => array_merge((array) $purchase->metadata, [
                    'payment_failed_at' => now()->toIso8601String(),
                    'failure_reason' => $e->getMessage(),
                ]),
            ])->save();

            return redirect('/')->with('error', 'The device payment could not be verified. Please contact support.');
        }

        return redirect('/')->with('success', 'Device payment received. The Bwiser team will contact you for fulfilment.');
    }
}
