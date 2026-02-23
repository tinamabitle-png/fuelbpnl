<?php
namespace App\Services;

use App\Models\FuelVoucher;
use App\Models\FuelStation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class VoucherIssuanceService
{
    public function issue(int $userId, int $stationId, float $amount)
    {
        return DB::transaction(function () use ($userId, $stationId, $amount) {
            $station = FuelStation::whereKey($stationId)->lockForUpdate()->firstOrFail();
            $openExposure = FuelVoucher::where('fuel_station_id', $station->id)
                ->whereIn('status', ['issued', 'approved'])
                ->lockForUpdate()
                ->sum('amount');
            $availableCapacity = max(0, (float) $station->wallet_balance - (float) $openExposure);
            if ($availableCapacity < $amount) {
                throw new \RuntimeException(sprintf(
                    'Insufficient station pre-funded balance. Available capacity: R%.2f.',
                    $availableCapacity
                ));
            }

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
