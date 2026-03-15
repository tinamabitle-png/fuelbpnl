<?php

namespace App\Services;

use App\Models\User;
use App\Models\VirtualCard;
use Illuminate\Support\Facades\DB;

class VirtualCardService
{
    public const MAX_OPEN_CARDS_PER_USER = 3;

    public function createForUser(User $user, array $attributes = []): VirtualCard
    {
        return DB::transaction(function () use ($user, $attributes) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $brand = (string) ($attributes['brand'] ?? 'generic');
            $brand = trim($brand) === '' ? 'generic' : trim($brand);

            $openCount = VirtualCard::query()
                ->where('user_id', $user->id)
                ->open()
                ->count();

            if ($openCount >= self::MAX_OPEN_CARDS_PER_USER) {
                throw new \InvalidArgumentException('You can only have up to 3 virtual cards.');
            }

            $brandHasOpenCard = VirtualCard::query()
                ->where('user_id', $user->id)
                ->where('brand', $brand)
                ->open()
                ->exists();

            if ($brandHasOpenCard) {
                throw new \InvalidArgumentException('You already have an open virtual card for this brand.');
            }

            /** @var VirtualCard $card */
            $card = VirtualCard::create([
                'user_id' => $user->id,
                'provider' => $attributes['provider'] ?? 'flutterwave',
                'provider_card_id' => $attributes['provider_card_id'] ?? null,
                'brand' => $brand,
                'label' => $attributes['label'] ?? null,
                'currency' => $attributes['currency'] ?? 'ZAR',
                'status' => $attributes['status'] ?? 'active',
                'allocated_amount' => $attributes['allocated_amount'] ?? 0,
                'metadata' => $attributes['metadata'] ?? null,
                'masked_pan' => $attributes['masked_pan'] ?? null,
                'last4' => $attributes['last4'] ?? null,
                'expiry_month' => $attributes['expiry_month'] ?? null,
                'expiry_year' => $attributes['expiry_year'] ?? null,
                'card_scheme' => $attributes['card_scheme'] ?? null,
            ]);

            // UX-friendly placeholders until provider provisioning is wired in.
            if (empty($card->masked_pan) && empty($card->last4)) {
                $last4 = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $expiryMonth = (int) ($card->expiry_month ?: 12);
                $expiryYear = (int) ($card->expiry_year ?: (int) now()->addYears(3)->format('Y'));

                $card->update([
                    'last4' => $last4,
                    'masked_pan' => '•••• •••• •••• ' . $last4,
                    'expiry_month' => $expiryMonth,
                    'expiry_year' => $expiryYear,
                    'card_scheme' => $card->card_scheme ?: 'visa',
                ]);
                $card = $card->fresh();
            }

            return $card;
        });
    }
}
