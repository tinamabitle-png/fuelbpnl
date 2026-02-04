<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
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
        'amount',
        'liters',
        'fuel_type',
        'status',
        'issued_at',
        'redeemed_at',
        'expires_at',
        'settlement_id',
        'transaction_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'liters' => 'decimal:3',
        'issued_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
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

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->where('status', 'issued')
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
        return $this->status === 'issued' && $this->expires_at > now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->status === 'expired' || ($this->status === 'issued' && $this->expires_at < now());
    }

    public function getIsRedeemableAttribute()
    {
        return $this->status === 'issued' && 
               $this->expires_at > now() && 
               $this->issued_at <= now();
    }

    // Business logic methods
    public function redeem()
    {
        if (!$this->is_redeemable) {
            throw new \Exception('Voucher cannot be redeemed');
        }

        $this->update([
            'status' => 'redeemed',
            'redeemed_at' => now(),
        ]);

        return $this;
    }

    public function cancel()
    {
        if ($this->status !== 'issued') {
            throw new \Exception('Only issued vouchers can be cancelled');
        }

        $this->update(['status' => 'cancelled']);

        // If this was a BNPL voucher, release the credit
        if ($this->lease_id) {
            $this->user->wallet->decrement('outstanding_balance', $this->amount);
            $this->user->creditLimit->releaseCredit($this->amount);
        }

        return $this;
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