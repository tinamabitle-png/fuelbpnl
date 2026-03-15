<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Services\FlutterwaveVirtualCardService;
use App\Services\VirtualCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VirtualCardController extends Controller
{
    private function authorizeDriverPortal($user): void
    {
        abort_unless($user && $user->hasAnyRole(['super_admin', 'admin', 'driver']), 403);
    }

    public function index()
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $cards = $user->virtualCards()->latest()->get();
        $wallet = $user->wallet;
        $brands = collect((array) config('retail_brands', []))
            ->values()
            ->map(function ($row, int $i) {
                $row = (array) $row;
                $themes = ['one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight'];
                $row['theme'] = $themes[$i % count($themes)];
                return $row;
            });

        return view('driver.virtual-cards.index', compact('cards', 'wallet', 'brands'));
    }

    public function reveal(VirtualCard $card, FlutterwaveVirtualCardService $flutterwave)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        abort_unless($card->user_id === $user->id, 404);

        $providerCardId = trim((string) ($card->provider_card_id ?? ''));
        if ($card->provider === 'flutterwave' && $providerCardId !== '' && $flutterwave->isConfigured()) {
            try {
                $revealed = $flutterwave->reveal($providerCardId);
                return response()->json([
                    'success' => true,
                    'data' => [
                        'pan' => (string) ($revealed['pan'] ?? ''),
                        'cvv' => $revealed['cvv'] ?? null,
                        'masked_pan' => (string) ($card->masked_pan ?? ''),
                        'last4' => (string) ($card->last4 ?? ''),
                        'expiry_month' => $revealed['expiry_month'] ?? ($card->expiry_month ?: null),
                        'expiry_year' => $revealed['expiry_year'] ?? ($card->expiry_year ?: null),
                        'card_scheme' => (string) (($revealed['card_scheme'] ?? null) ?: ($card->card_scheme ?? 'visa')),
                    ],
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Failed to reveal virtual card details.',
                ], 502);
            }
        }

        $last4 = trim((string) ($card->last4 ?? '')) ?: '0000';
        $pan = '4' . $this->digitsFromHash(
            (string) $user->id . ':' . (string) $card->id . ':' . (string) config('app.key'),
            11
        ) . $last4;

        $expMonth = (int) ($card->expiry_month ?? 0);
        $expYear = (int) ($card->expiry_year ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'pan' => trim(implode(' ', str_split(preg_replace('/\D+/', '', $pan) ?: $pan, 4))),
                'masked_pan' => (string) ($card->masked_pan ?? ''),
                'last4' => $last4,
                'expiry_month' => $expMonth > 0 ? $expMonth : null,
                'expiry_year' => $expYear > 0 ? $expYear : null,
                'card_scheme' => (string) ($card->card_scheme ?? 'visa'),
                'cvv' => null,
            ],
        ]);
    }

    public function store(Request $request, VirtualCardService $virtualCardService)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);

        $validated = $request->validate([
            'label' => 'nullable|string|max:64',
            'brand' => 'required|string|max:64',
        ]);

        $brandSlug = (string) $validated['brand'];
        $knownBrands = collect((array) config('retail_brands', []))
            ->pluck('slug')
            ->filter()
            ->all();
        if ($knownBrands !== [] && !in_array($brandSlug, $knownBrands, true) && $brandSlug !== 'generic') {
            return back()->with('error', 'Unknown retail brand.');
        }

        try {
            $virtualCardService->createForUser($user, [
                'label' => $validated['label'] ?? null,
                'currency' => 'ZAR',
                'provider' => 'flutterwave',
                'brand' => $brandSlug,
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Virtual card created.');
    }

    private function digitsFromHash(string $seed, int $length): string
    {
        $hash = hash('sha256', $seed);
        $digits = preg_replace('/\D+/', '', $hash) ?: '';
        while (strlen($digits) < $length) {
            $hash = hash('sha256', $hash . $seed);
            $digits .= preg_replace('/\D+/', '', $hash) ?: '';
        }
        return substr($digits, 0, $length);
    }

    public function freeze(VirtualCard $card)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        abort_unless($card->user_id === $user->id, 404);

        if ($card->status !== 'active') {
            return back()->with('error', 'Only active cards can be frozen.');
        }

        $card->update(['status' => 'frozen']);

        return back()->with('success', 'Card frozen.');
    }

    public function unfreeze(VirtualCard $card)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        abort_unless($card->user_id === $user->id, 404);

        if ($card->status !== 'frozen') {
            return back()->with('error', 'Only frozen cards can be unfrozen.');
        }

        $card->update(['status' => 'active']);

        return back()->with('success', 'Card unfrozen.');
    }

    public function close(VirtualCard $card)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        abort_unless($card->user_id === $user->id, 404);

        if ($card->status === 'terminated') {
            return back()->with('error', 'Card already closed.');
        }

        DB::transaction(function () use ($card) {
            $locked = VirtualCard::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'status' => 'terminated',
                'allocated_amount' => 0,
            ]);
        });

        return back()->with('success', 'Card closed.');
    }

    public function allocate(Request $request, VirtualCard $card)
    {
        $user = Auth::user();
        $this->authorizeDriverPortal($user);
        abort_unless($card->user_id === $user->id, 404);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        try {
            DB::transaction(function () use ($user, $card, $validated) {
                $amount = (float) $validated['amount'];

                $lockedUser = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $wallet = Wallet::query()
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedCard = VirtualCard::query()
                    ->where('user_id', $lockedUser->id)
                    ->whereKey($card->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($lockedCard->status, ['active', 'frozen'], true)) {
                    throw new \InvalidArgumentException('Card is closed.');
                }

                if ($wallet->available_balance < $amount) {
                    throw new \InvalidArgumentException('Insufficient available wallet balance.');
                }

                $lockedCard->increment('allocated_amount', $amount);
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Funds allocated to virtual card.');
    }
}
