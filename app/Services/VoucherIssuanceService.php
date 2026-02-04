<?php
namespace App\Services;

use App\Models\FuelVoucher;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class VoucherIssuanceService
{
    public function issue(int $userId, int $stationId, float $amount)
    {
        return DB::transaction(function () use ($userId, $stationId, $amount) {
            $voucher = FuelVoucher::create([
                'code' => Str::upper(Str::random(12)),
                'user_id' => $userId,
                'fuel_station_id' => $stationId,
                'amount' => $amount,
                'status' => 'issued',
                'expires_at' => now()->addDays(7),
            ]);

            return $voucher;
        });
    }
}
