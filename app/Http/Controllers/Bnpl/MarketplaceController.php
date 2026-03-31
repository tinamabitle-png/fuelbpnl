<?php

namespace App\Http\Controllers\Bnpl;

use App\Http\Controllers\Controller;
use App\Models\BnplInstallment;
use App\Models\BnplOrder;
use App\Models\User;
use App\Services\MrdCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceController extends Controller
{
    public function mrd(Request $request, MrdCatalogService $catalogService)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $catalog = $catalogService->loadCatalog();

        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $products = collect((array) ($catalog['products'] ?? []));

        if ($category !== '') {
            $products = $products->filter(fn ($p) => (string) ($p['category_id'] ?? '') === $category);
        }

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $products = $products->filter(function ($p) use ($needle) {
                $hay = mb_strtolower((string) (($p['name'] ?? '') . ' ' . ($p['description'] ?? '') . ' ' . ($p['unit'] ?? '')));
                return str_contains($hay, $needle);
            });
        }

        // Keep it predictable for UI (and avoid massive renders until we have pagination).
        $products = $products->values()->take(80);

        $categories = collect((array) ($catalog['categories'] ?? []))
            ->filter(fn ($c) => is_array($c) && (string) ($c['id'] ?? '') !== '')
            ->values();

        return view('bnpl.marketplace.mrd.index', [
            'catalog' => $catalog,
            'categories' => $categories,
            'products' => $products,
            'q' => $q,
            'category' => $category,
        ]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $city = trim((string) $request->query('city', ''));
        $q = User::query();

        // Prefer role('driver') when Spatie roles are present.
        try {
            $q->role('driver');
        } catch (\Throwable $e) {
            // Fallback: show all active users if roles are not available.
        }

        $q->where('status', 'active');
        if ($city !== '') {
            $q->where('city', 'LIKE', '%' . $city . '%');
        }

        $drivers = $q->orderBy('name')
            ->limit(60)
            ->get(['id', 'name', 'city', 'driver_platform', 'driver_platform_other']);

        return view('bnpl.marketplace.index', compact('drivers', 'city'));
    }

    public function create(User $driver)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        abort_unless($driver->exists, 404);
        abort_unless((string) ($driver->status ?? '') === 'active', 404);

        return view('bnpl.marketplace.create', compact('driver'));
    }

    public function store(Request $request, User $driver)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        abort_unless($driver->exists, 404);
        abort_unless((string) ($driver->status ?? '') === 'active', 404);

        if ((int) $driver->id === (int) $user->id) {
            return back()->with('error', 'You cannot place an order with yourself.');
        }

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

        DB::transaction(function () use ($user, $driver, $validated, $amountTotal, $deposit, $installments, &$order) {
            $reference = 'SHOP-' . $user->id . '-' . strtoupper(Str::random(10));
            $financed = max(0, $amountTotal - $deposit);

            $order = BnplOrder::create([
                'driver_id' => $driver->id,
                'shopper_id' => $user->id,
                'reference' => $reference,
                'status' => 'shopper_requested',
                'title' => $validated['title'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount_total' => $amountTotal,
                'deposit_amount' => $deposit,
                'financed_amount' => $financed,
                'installments_count' => $installments,
                'currency' => 'ZAR',
                'expires_at' => now()->addDays(3),
                'metadata' => [
                    'source' => 'shopper_marketplace',
                    'shopper' => [
                        'name' => (string) ($user->name ?? ''),
                        'email' => (string) ($user->email ?? ''),
                        'phone' => (string) ($user->phone ?? ''),
                    ],
                    'driver' => [
                        'name' => (string) ($driver->name ?? ''),
                        'city' => (string) ($driver->city ?? ''),
                    ],
                ],
            ]);

            // Preview schedule (weekly installments). Payment collection will be wired later.
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
            ->route('bnpl.orders.show', $order, false)
            ->with('success', 'Order request sent. The driver will confirm before fulfillment.');
    }

    public function orders()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $orders = BnplOrder::query()
            ->where('shopper_id', $user->id)
            ->with('driver')
            ->latest()
            ->limit(50)
            ->get();

        return view('bnpl.orders.index', compact('orders'));
    }

    public function showOrder(BnplOrder $order)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        abort_unless((int) $order->shopper_id === (int) $user->id, 404);

        $order->loadMissing('installments', 'driver');

        return view('bnpl.orders.show', compact('order'));
    }
}
