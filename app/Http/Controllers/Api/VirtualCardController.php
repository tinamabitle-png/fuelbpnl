<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VirtualCard;
use App\Models\Wallet;
use App\Services\FlutterwaveVirtualCardService;
use App\Services\VirtualCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VirtualCardController extends Controller
{
    public function reveal(Request $request, int $cardId, FlutterwaveVirtualCardService $flutterwave)
    {
        $card = $request->user()->virtualCards()->whereKey($cardId)->first();
        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Virtual card not found'], 404);
        }

        $providerCardId = trim((string) ($card->provider_card_id ?? ''));
        if ($card->provider === 'flutterwave' && $providerCardId !== '' && $flutterwave->isConfigured()) {
            try {
                $revealed = $flutterwave->reveal($providerCardId);
                return response()->json([
                    'success' => true,
                    'data' => [
                        'pan' => (string) ($revealed['pan'] ?? ''),
                        'cvv' => $revealed['cvv'] ?? null,
                        'cvv_available' => !empty($revealed['cvv']),
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

        if ($card->provider === 'flutterwave' && $providerCardId === '') {
            return response()->json([
                'success' => true,
                'data' => [
                    'pan' => '',
                    'cvv' => null,
                    'cvv_available' => false,
                    'masked_pan' => (string) ($card->masked_pan ?? ''),
                    'last4' => (string) ($card->last4 ?? ''),
                    'expiry_month' => (int) ($card->expiry_month ?? 0) ?: null,
                    'expiry_year' => (int) ($card->expiry_year ?? 0) ?: null,
                    'card_scheme' => (string) ($card->card_scheme ?? 'visa'),
                    'message' => 'This card is not provisioned with Flutterwave yet. Create a new virtual card to get a real CVV.',
                ],
            ]);
        }

        $last4 = trim((string) ($card->last4 ?? ''));
        if ($last4 === '') {
            $last4 = '0000';
        }

        $pan = '4' . $this->digitsFromHash(
            (string) $request->user()->id . ':' . (string) $card->id . ':' . (string) config('app.key'),
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

    public function brands()
    {
        $brands = collect((array) config('retail_brands', []))
            ->values()
            ->map(function ($row) {
                $row = (array) $row;
                $logo = (string) ($row['logo'] ?? '');
                if ($logo !== '') {
                    $row['logo_url'] = asset($logo);
                }
                return $row;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'brands' => $brands,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $cards = $request->user()
            ->virtualCards()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'virtual_cards' => $cards,
            ],
        ]);
    }

    public function store(Request $request, VirtualCardService $virtualCardService)
    {
        $validator = Validator::make($request->all(), [
            'label' => 'nullable|string|max:64',
            'brand' => 'required|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $brand = (string) $request->input('brand');
            $knownBrands = collect((array) config('retail_brands', []))
                ->pluck('slug')
                ->filter()
                ->all();
            if ($knownBrands !== [] && !in_array($brand, $knownBrands, true) && $brand !== 'generic') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unknown retail brand.',
                ], 422);
            }

            $card = $virtualCardService->createForUser($request->user(), [
                'label' => $request->input('label'),
                'currency' => (string) config('services.flutterwave.virtual_cards_currency', 'USD'),
                'provider' => 'flutterwave',
                'brand' => $brand,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Virtual card created.',
                'data' => [
                    'virtual_card' => $card,
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to provision virtual card.',
            ], 502);
        }
    }

    public function freeze(Request $request, int $cardId)
    {
        $card = $request->user()->virtualCards()->whereKey($cardId)->first();
        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Virtual card not found'], 404);
        }

        if ($card->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Only active cards can be frozen.'], 422);
        }

        $card->update(['status' => 'frozen']);

        return response()->json(['success' => true, 'data' => ['virtual_card' => $card->fresh()]]);
    }

    public function unfreeze(Request $request, int $cardId)
    {
        $card = $request->user()->virtualCards()->whereKey($cardId)->first();
        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Virtual card not found'], 404);
        }

        if ($card->status !== 'frozen') {
            return response()->json(['success' => false, 'message' => 'Only frozen cards can be unfrozen.'], 422);
        }

        $card->update(['status' => 'active']);

        return response()->json(['success' => true, 'data' => ['virtual_card' => $card->fresh()]]);
    }

    public function close(Request $request, int $cardId)
    {
        $card = $request->user()->virtualCards()->whereKey($cardId)->first();
        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Virtual card not found'], 404);
        }

        if ($card->status === 'terminated') {
            return response()->json(['success' => false, 'message' => 'Card already closed.'], 422);
        }

        DB::transaction(function () use ($card) {
            $locked = VirtualCard::query()->whereKey($card->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'status' => 'terminated',
                'allocated_amount' => 0,
            ]);
        });

        return response()->json(['success' => true, 'data' => ['virtual_card' => $card->fresh()]]);
    }

    public function allocate(Request $request, int $cardId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $amount = (float) $request->input('amount');
        $user = $request->user();

        try {
            $card = DB::transaction(function () use ($user, $cardId, $amount) {
                $lockedUser = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                $wallet = Wallet::query()
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $card = VirtualCard::query()
                    ->where('user_id', $lockedUser->id)
                    ->whereKey($cardId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($card->status, ['active', 'frozen'], true)) {
                    throw new \InvalidArgumentException('Card is closed.');
                }

                if ($wallet->available_balance < $amount) {
                    throw new \InvalidArgumentException('Insufficient available wallet balance.');
                }

                $card->increment('allocated_amount', $amount);

                return $card->fresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Funds allocated to virtual card.',
                'data' => [
                    'virtual_card' => $card,
                    'wallet_available_balance' => $user->wallet->fresh()->available_balance,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Virtual card not found'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
