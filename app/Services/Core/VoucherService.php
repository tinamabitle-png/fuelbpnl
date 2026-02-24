<?php

namespace App\Services\Core;

use App\Models\FuelVoucher;

class VoucherService
{
    public function createVoucher(array $attributes): FuelVoucher
    {
        return FuelVoucher::create([
            'user_id' => $attributes['user_id'],
            'fuel_station_id' => $attributes['fuel_station_id'],
            'lease_id' => $attributes['lease_id'] ?? null,
            'amount' => (float) $attributes['amount'],
            'liters' => (float) ($attributes['liters'] ?? 0),
            'fuel_type' => $attributes['fuel_type'],
            'status' => $attributes['status'] ?? 'issued',
            'expires_at' => $attributes['expires_at'] ?? now()->addHours(24),
        ]);
    }
}

