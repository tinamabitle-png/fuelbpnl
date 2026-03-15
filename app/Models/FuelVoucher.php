<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FuelVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'qr_code',
        'user_id',
        'fuel_station_id',
        'lease_id',
        'funding_source',
        'amount',
        'redeemed_fuel_amount',
        'redeemed_airtime_amount',
        'liters',
        'fuel_type',
        'status',
        'issued_at',
        'redeemed_at',
        'expires_at',
        'settlement_id',
        'settled_at',
        'transaction_reference',
        'airtime_phone',
        'airtime_reference',
        'airtime_status',
        'pump_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'redeemed_fuel_amount' => 'decimal:2',
        'redeemed_airtime_amount' => 'decimal:2',
        'liters' => 'decimal:3',
        'issued_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (!$voucher->code) {
                $voucher->code = Str::upper(Str::random(12));
            }
            if (!$voucher->qr_code) {
                $voucher->qr_code = 'VOUCHER-' . time() . '-' . Str::random(8);
            }
            if (!$voucher->issued_at) {
                $voucher->issued_at = now();
            }
            if (!$voucher->expires_at) {
                $voucher->expires_at = now()->addHours(24);
            }
        });

        static::updated(function (self $voucher) {
            if (
                $voucher->wasChanged('status') &&
                $voucher->status === 'expired' &&
                is_null($voucher->redeemed_at)
            ) {
                $voucher->softDeleteRepaymentsForExpiredVoucher();
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fuelStation()
    {
        return $this->belongsTo(FuelStation::class);
    }

    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }

    // Scopes
    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopeRedeemed($query)
    {
        return $query->where('status', 'redeemed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['issued', 'approved'])
                  ->where('expires_at', '<', now());
            });
    }

    public function scopePendingSettlement($query)
    {
        return $query->where('status', 'redeemed')
            ->whereNull('settlement_id');
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        return in_array($this->status, ['issued', 'approved'], true) && $this->expires_at > now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->status === 'expired'
            || (in_array($this->status, ['issued', 'approved'], true)
                && $this->expires_at
                && $this->expires_at->lt(now()));
    }

    public function getIsRedeemableAttribute()
    {
        return $this->status === 'approved'
            && $this->expires_at
            && $this->expires_at->gt(now())
            && $this->issued_at
            && $this->issued_at->lte(now());
    }

    // Business logic methods
    public function redeem()
    {
        return DB::transaction(function () {
            $voucher = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($voucher->expires_at && $voucher->expires_at->lte(now())) {
                if ($voucher->status !== 'expired') {
                    $voucher->update(['status' => 'expired']);
                }
                throw new \Exception('Voucher expired and cannot be redeemed');
            }

            if (!$voucher->is_redeemable) {
                throw new \Exception('Voucher cannot be redeemed');
            }

            $isWalletFunded = ($voucher->funding_source === 'wallet')
                || (empty($voucher->funding_source) && empty($voucher->lease_id));

            if ($isWalletFunded) {
                $wallet = Wallet::query()
                    ->where('user_id', $voucher->user_id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    throw new \Exception('User wallet not found');
                }

                $wallet->deductFunds(
                    (float) $voucher->amount,
                    'Voucher redemption: ' . $voucher->code,
                    ['fuel_voucher_id' => (int) $voucher->id, 'code' => (string) $voucher->code]
                );
            }

            $station = FuelStation::whereKey($voucher->fuel_station_id)->lockForUpdate()->firstOrFail();
            $station->deductFromWallet(
                (float) $voucher->amount,
                'Voucher redemption: ' . $voucher->code
            );

            $voucher->update([
                'status' => 'redeemed',
                'redeemed_at' => now(),
                'redeemed_fuel_amount' => (float) $voucher->amount,
                'redeemed_airtime_amount' => 0,
                'airtime_status' => 'not_requested',
            ]);

            return $voucher->fresh();
        });
    }

    public static function stationOpenExposure(int $stationId, ?int $excludeVoucherId = null): float
    {
        $query = self::query()
            ->where('fuel_station_id', $stationId)
            ->whereIn('status', ['issued', 'approved']);

        if ($excludeVoucherId) {
            $query->where('id', '!=', $excludeVoucherId);
        }

        return (float) $query->sum('amount');
    }

    public function cancel(bool $voidFutureRepayments = true): array
    {
        if ($this->status !== 'issued') {
            throw new \Exception('Only issued vouchers can be cancelled');
        }

        return DB::transaction(function () use ($voidFutureRepayments) {
            /** @var self $voucher */
            $voucher = self::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            $voucher->update(['status' => 'cancelled']);

            $cancelledRepayments = 0;

            // If this was a BNPL voucher, release the credit and void future unpaid repayments.
            if ($voucher->lease_id) {
                $user = $voucher->user;
                if ($user && $user->wallet) {
                    $user->wallet->decrement('outstanding_balance', (float) $voucher->amount);
                }
                if ($user && $user->creditLimit) {
                    $user->creditLimit->releaseCredit((float) $voucher->amount);
                }

                if ($voidFutureRepayments) {
                    $cancelledRepayments = Repayment::query()
                        ->where('lease_id', $voucher->lease_id)
                        ->whereNull('paid_at')
                        ->whereIn('status', ['pending', 'overdue'])
                        ->whereDate('due_date', '>=', now()->toDateString())
                        ->delete();
                }

                // If the lease has no redeemable/active vouchers left, mark it cancelled.
                $activeVouchersRemaining = self::query()
                    ->where('lease_id', $voucher->lease_id)
                    ->where('id', '!=', $voucher->id)
                    ->whereIn('status', ['issued', 'approved', 'redeemed'])
                    ->exists();

                if (!$activeVouchersRemaining && $voucher->lease && $voucher->lease->status === 'active') {
                    $voucher->lease->update(['status' => 'cancelled']);
                }
            }

            return [
                'voucher' => $voucher->fresh(),
                'cancelled_repayments' => (int) $cancelledRepayments,
            ];
        });
    }

    public function softDeleteRepaymentsForExpiredVoucher(): void
    {
        if (empty($this->lease_id)) {
            return;
        }

        Repayment::query()
            ->where('lease_id', $this->lease_id)
            ->whereNull('paid_at')
            ->delete();
    }

    public function generateQRData()
    {
        return json_encode([
            'voucher_id' => $this->id,
            'code' => $this->code,
            'amount' => $this->amount,
            'fuel_type' => $this->fuel_type,
            'expires_at' => $this->expires_at->toIso8601String(),
            'signature' => hash_hmac('sha256', $this->id . $this->code, config('app.key'))
        ]);
    }
}
