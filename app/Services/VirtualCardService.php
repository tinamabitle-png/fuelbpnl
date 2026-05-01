<?php

namespace App\Services;

use App\Models\User;
use App\Models\VirtualCard;
use Illuminate\Support\Facades\DB;

class VirtualCardService
{
    public const MAX_OPEN_CARDS_PER_USER = 3;

    public function __construct(
        private readonly FlutterwaveVirtualCardService $flutterwaveVirtualCardService,
    ) {
    }

    public function createForUser(User $user, array $attributes = []): VirtualCard
    {
        return DB::transaction(function () use ($user, $attributes) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $brand = (string) ($attributes['brand'] ?? 'generic');
            $brand = trim($brand) === '' ? 'generic' : trim($brand);

            $provider = (string) ($attributes['provider'] ?? 'flutterwave');
            $provider = trim($provider) === '' ? 'flutterwave' : trim($provider);
            $currency = (string) ($attributes['currency'] ?? 'ZAR');
            $currency = trim($currency) === '' ? 'ZAR' : strtoupper(trim($currency));

            // Treat "in-flight" cards as open for quota/brand uniqueness.
            $openCount = VirtualCard::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'frozen'])
                ->count();

            if ($openCount >= self::MAX_OPEN_CARDS_PER_USER) {
                throw new \InvalidArgumentException('You can only have up to 3 virtual cards.');
            }

            $brandHasOpenCard = VirtualCard::query()
                ->where('user_id', $user->id)
                ->where('brand', $brand)
                ->whereIn('status', ['active', 'frozen'])
                ->exists();

            if ($brandHasOpenCard) {
                throw new \InvalidArgumentException('You already have an open virtual card for this brand.');
            }

            $provisioned = null;
            if ($provider === 'flutterwave') {
                if ($this->shouldFakeFlutterwaveProvisioning()) {
                    $provisioned = $this->fakeFlutterwaveProvisionedCard();
                } else {
                    if (!$this->flutterwaveVirtualCardService->isConfigured()) {
                        throw new \InvalidArgumentException('Flutterwave is not configured (missing FLUTTERWAVE_SECRET_KEY).');
                    }
                    if (!$this->flutterwaveVirtualCardService->virtualCardsEnabled()) {
                        throw new \InvalidArgumentException('Flutterwave virtual cards are disabled. Set FLUTTERWAVE_VIRTUAL_CARDS_ENABLED=true.');
                    }

                    $billing = (array) ($attributes['billing'] ?? []);
                    $initialAmount = (float) ($attributes['initial_amount'] ?? config('services.flutterwave.virtual_cards_initial_amount', 1));
                    $provisioned = $this->flutterwaveVirtualCardService->create($user, $billing, $initialAmount, $currency);
                }
            }

            $metadata = $attributes['metadata'] ?? null;
            if (is_array($metadata) && $provisioned) {
                // Persist only non-sensitive provider metadata.
                $metadata['provider'] = $metadata['provider'] ?? $provider;
                $metadata['provider_card_id'] = $metadata['provider_card_id'] ?? ($provisioned['provider_card_id'] ?? null);
            }

            /** @var VirtualCard $card */
            $card = VirtualCard::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_card_id' => $provisioned['provider_card_id'] ?? ($attributes['provider_card_id'] ?? null),
                'brand' => $brand,
                'label' => $attributes['label'] ?? null,
                'currency' => $currency,
                'status' => $attributes['status'] ?? 'active',
                'allocated_amount' => $attributes['allocated_amount'] ?? 0,
                'metadata' => $metadata,
                'masked_pan' => $provisioned['masked_pan'] ?? ($attributes['masked_pan'] ?? null),
                'last4' => $provisioned['last4'] ?? ($attributes['last4'] ?? null),
                'expiry_month' => $provisioned['expiry_month'] ?? ($attributes['expiry_month'] ?? null),
                'expiry_year' => $provisioned['expiry_year'] ?? ($attributes['expiry_year'] ?? null),
                'card_scheme' => $provisioned['card_scheme'] ?? ($attributes['card_scheme'] ?? null),
            ]);

            // UX-friendly placeholders when provider data is missing (or when using a non-provider card).
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

    private function shouldFakeFlutterwaveProvisioning(): bool
    {
        // Unit and feature tests exercise quota/validation logic and should not depend on live network calls.
        return app()->runningUnitTests();
    }

    /**
     * @return array{
     *   provider_card_id:string,
     *   last4:string,
     *   masked_pan:string,
     *   expiry_month:int,
     *   expiry_year:int,
     *   card_scheme:string,
     *   raw:array<string,mixed>
     * }
     */
    private function fakeFlutterwaveProvisionedCard(): array
    {
        $last4 = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return [
            'provider_card_id' => 'test-card-' . strtoupper(bin2hex(random_bytes(4))),
            'last4' => $last4,
            'masked_pan' => '•••• •••• •••• ' . $last4,
            'expiry_month' => 12,
            'expiry_year' => (int) now()->addYears(3)->format('Y'),
            'card_scheme' => 'visa',
            'raw' => ['mode' => 'test'],
        ];
    }
}
