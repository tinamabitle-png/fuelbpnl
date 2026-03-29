<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\BnplInstallment;
use App\Models\BnplOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class BnplOrderController extends Controller
{
    private function authorizeDriverPortal($user): void
    {
        abort_unless($user && $user->hasAnyRole(['super_admin', 'admin', 'driver']), 403);
    }

    public function index()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $orders = BnplOrder::query()
            ->where('driver_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();

        return view('driver.bnpl.orders.index', compact('orders'));
    }

    public function create()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        return view('driver.bnpl.orders.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:2000',
            'amount_total' => 'required|numeric|min:10|max:50000',
            'deposit_amount' => 'nullable|numeric|min:0|max:50000',
            'installments_count' => 'nullable|integer|min:2|max:6',
        ]);

        $amountTotal = (float) $validated['amount_total'];
        $deposit = (float) ($validated['deposit_amount'] ?? 0);
        $deposit = max(0, min($deposit, $amountTotal));
        $installments = (int) ($validated['installments_count'] ?? 4);

        $order = null;

        DB::transaction(function () use ($user, $validated, $amountTotal, $deposit, $installments, &$order) {
            $reference = 'BNPL-' . $user->id . '-' . strtoupper(Str::random(10));
            $financed = max(0, $amountTotal - $deposit);

            $order = BnplOrder::create([
                'driver_id' => $user->id,
                'reference' => $reference,
                'status' => 'draft',
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount_total' => $amountTotal,
                'deposit_amount' => $deposit,
                'financed_amount' => $financed,
                'installments_count' => $installments,
                'currency' => 'ZAR',
                'expires_at' => now()->addDays(3),
            ]);

            // Create a simple schedule (weekly installments). Payments will be wired in later.
            $per = $installments > 0 ? round($financed / $installments, 2) : 0;
            $running = 0.0;

            for ($i = 1; $i <= $installments; $i++) {
                $amount = $i === $installments ? round($financed - $running, 2) : $per;
                $running += $amount;

                BnplInstallment::create([
                    'bnpl_order_id' => $order->id,
                    'sequence' => $i,
                    'due_at' => now()->addWeeks($i),
                    'amount' => $amount,
                    'status' => 'pending',
                ]);
            }
        });

        return redirect()
            ->route('driver.bnpl.orders.show', $order)
            ->with('success', 'BNPL order created. Share the checkout link with the shopper.');
    }

    public function show(BnplOrder $order)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        abort_unless((int) $order->driver_id === (int) $user->id, 404);

        $order->loadMissing('installments', 'shopper');

        $checkoutUrl = URL::temporarySignedRoute(
            'bnpl.checkout.show',
            now()->addDays(3),
            ['order' => $order->id]
        );

        return view('driver.bnpl.orders.show', compact('order', 'checkoutUrl'));
    }
}

