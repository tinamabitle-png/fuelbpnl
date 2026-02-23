<?php

namespace App\Services;

use App\Models\FuelStation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class FuelPriceService
{
    public function supportedFuelTypes(): array
    {
        return ['petrol', 'diesel', 'super'];
    }

    public function defaultPrices(): array
    {
        return [
            'petrol' => (float) config('fuel_pricing.defaults.petrol', 24.75),
            'diesel' => (float) config('fuel_pricing.defaults.diesel', 23.95),
            'super' => (float) config('fuel_pricing.defaults.super', 25.10),
        ];
    }

    public function resolveStationPrices(int $stationId, bool $allowApiFallback = true): array
    {
        $prices = [];
        foreach ($this->supportedFuelTypes() as $fuelType) {
            $prices[$fuelType] = $this->resolvePriceForStationFuel($stationId, $fuelType, $allowApiFallback);
        }

        return $prices;
    }

    public function resolvePriceForStationFuel(int $stationId, string $fuelType, bool $allowApiFallback = true): array
    {
        $fuelType = strtolower($fuelType);
        if (!in_array($fuelType, $this->supportedFuelTypes(), true)) {
            return [
                'fuel_type' => $fuelType,
                'price' => (float) ($this->defaultPrices()[$fuelType] ?? 25.00),
                'source' => 'default',
                'source_label' => 'System Default',
                'effective_at' => null,
            ];
        }

        $row = $this->latestPriceRow($stationId, $fuelType, ['merchant_custom']);
        if ($row) {
            return $this->formatResolvedRow($fuelType, $row, 'merchant_custom', 'Merchant Custom');
        }

        $row = $this->latestPriceRow($stationId, $fuelType, ['api_fallback', 'api_sync', 'manual', 'seed']);
        if ($row) {
            $source = $row->source ?? 'station';
            $sourceLabel = $this->sourceLabel($source);

            return $this->formatResolvedRow($fuelType, $row, $source, $sourceLabel);
        }

        if ($allowApiFallback) {
            $station = FuelStation::find($stationId);
            if ($station) {
                $apiMap = $this->fetchExternalPriceMap($station);
                $apiPrice = $apiMap[$fuelType] ?? null;

                if ($apiPrice !== null) {
                    $this->insertPriceRow($stationId, $fuelType, $apiPrice, 'api_fallback');

                    return [
                        'fuel_type' => $fuelType,
                        'price' => (float) $apiPrice,
                        'source' => 'api_fallback',
                        'source_label' => 'API Fallback',
                        'effective_at' => now()->toDateTimeString(),
                    ];
                }
            }
        }

        return [
            'fuel_type' => $fuelType,
            'price' => (float) ($this->defaultPrices()[$fuelType] ?? 25.00),
            'source' => 'default',
            'source_label' => 'System Default',
            'effective_at' => null,
        ];
    }

    public function setMerchantCustomPrice(int $stationId, string $fuelType, float $pricePerLiter, ?int $actorUserId = null): void
    {
        $this->insertPriceRow(
            $stationId,
            strtolower($fuelType),
            $pricePerLiter,
            'merchant_custom',
            now(),
            $actorUserId
        );
    }

    public function syncFromApiForStation(int $stationId, ?int $actorUserId = null): array
    {
        $station = FuelStation::findOrFail($stationId);
        $apiMap = $this->fetchExternalPriceMap($station);

        $synced = [];
        foreach ($this->supportedFuelTypes() as $fuelType) {
            if (!isset($apiMap[$fuelType])) {
                continue;
            }

            $price = (float) $apiMap[$fuelType];
            $this->insertPriceRow($stationId, $fuelType, $price, 'api_sync', now(), $actorUserId);
            $synced[$fuelType] = $price;
        }

        return $synced;
    }

    public function fetchExternalPriceMap(FuelStation $station): array
    {
        $enabled = (bool) config('fuel_pricing.api.enabled', false);
        $url = trim((string) config('fuel_pricing.api.url', ''));

        if (!$enabled || $url === '') {
            return [];
        }

        $cacheSeconds = (int) config('fuel_pricing.api.cache_seconds', 300);
        $cacheKey = 'fuel_price_api:' . $station->id;

        return Cache::remember($cacheKey, $cacheSeconds, function () use ($station, $url) {
            try {
                $timeout = (int) config('fuel_pricing.api.timeout', 5);
                $token = (string) config('fuel_pricing.api.token', '');

                $request = Http::timeout($timeout)->acceptJson();
                if ($token !== '') {
                    $request = $request->withToken($token);
                }

                $response = $request->get($url, [
                    'station_id' => $station->id,
                    'city' => $station->city,
                    'country' => $station->country,
                ]);

                if (!$response->ok()) {
                    return [];
                }

                $json = $response->json();
                $rawPrices = Arr::get($json, 'data.prices', Arr::get($json, 'prices', []));
                if (!is_array($rawPrices)) {
                    return [];
                }

                $normalized = [];
                foreach ($this->supportedFuelTypes() as $fuelType) {
                    if (isset($rawPrices[$fuelType]) && is_numeric($rawPrices[$fuelType])) {
                        $normalized[$fuelType] = (float) $rawPrices[$fuelType];
                    }
                }

                return $normalized;
            } catch (\Throwable $e) {
                report($e);
                return [];
            }
        });
    }

    private function latestPriceRow(int $stationId, string $fuelType, array $preferredSources = []): ?object
    {
        if (!$this->hasPricingTable()) {
            return null;
        }

        $query = DB::table('fuel_station_prices')
            ->where('fuel_station_id', $stationId)
            ->where('fuel_type', $fuelType);

        if ($this->hasColumn('is_active')) {
            $query->where('is_active', true);
        }

        if (!empty($preferredSources) && $this->hasColumn('source')) {
            $query->whereIn('source', $preferredSources);
        }

        if ($this->hasColumn('effective_at')) {
            $query->orderByDesc('effective_at');
        }

        return $query->orderByDesc('id')->first();
    }

    private function insertPriceRow(
        int $stationId,
        string $fuelType,
        float $pricePerLiter,
        string $source,
        $effectiveAt = null,
        ?int $actorUserId = null
    ): void {
        if (!$this->hasPricingTable()) {
            return;
        }

        $payload = [
            'fuel_station_id' => $stationId,
            'fuel_type' => $fuelType,
            'price_per_liter' => round($pricePerLiter, 2),
        ];

        if ($this->hasColumn('source')) {
            $payload['source'] = $source;
        }

        if ($this->hasColumn('effective_at')) {
            $payload['effective_at'] = $effectiveAt ?? now();
        }

        if ($this->hasColumn('is_active')) {
            $payload['is_active'] = true;
        }

        if ($this->hasColumn('currency')) {
            $payload['currency'] = (string) config('fuel_pricing.currency', 'ZAR');
        }

        if ($actorUserId && $this->hasColumn('created_by')) {
            $payload['created_by'] = $actorUserId;
        }

        if ($this->hasColumn('created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->hasColumn('updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('fuel_station_prices')->insert($payload);
    }

    private function formatResolvedRow(string $fuelType, object $row, string $source, string $sourceLabel): array
    {
        return [
            'fuel_type' => $fuelType,
            'price' => (float) $row->price_per_liter,
            'source' => $source,
            'source_label' => $sourceLabel,
            'effective_at' => $row->effective_at ?? null,
        ];
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'merchant_custom' => 'Merchant Custom',
            'api_sync' => 'API Synced',
            'api_fallback' => 'API Fallback',
            'manual' => 'Manual',
            default => 'Station Price',
        };
    }

    private function hasPricingTable(): bool
    {
        return Schema::hasTable('fuel_station_prices');
    }

    private function hasColumn(string $column): bool
    {
        return $this->hasPricingTable() && Schema::hasColumn('fuel_station_prices', $column);
    }
}
