<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaplessPaymentIntent extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'partner_id',
        'fuel_station_id',
        'fuel_voucher_id',
        'external_reference',
        'scan_input',
        'amount',
        'currency',
        'status',
        'device_latitude',
        'device_longitude',
        'pump_number',
        'transaction_reference',
        'metadata',
        'request_payload',
        'response_payload',
        'failure_reason',
        'authorized_at',
        'redeemed_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'device_latitude' => 'decimal:8',
        'device_longitude' => 'decimal:8',
        'metadata' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'authorized_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $intent) {
            if (!$intent->public_id) {
                $intent->public_id = (string) Str::ulid();
            }

            if (!$intent->status) {
                $intent->status = 'created';
            }

            if (!$intent->currency) {
                $intent->currency = 'ZAR';
            }

            if (!$intent->expires_at) {
                $intent->expires_at = now()->addMinutes(15);
            }
        });
    }

    public function partner()
    {
        return $this->belongsTo(TaplessApiPartner::class, 'partner_id');
    }

    public function station()
    {
        return $this->belongsTo(FuelStation::class, 'fuel_station_id');
    }

    public function voucher()
    {
        return $this->belongsTo(FuelVoucher::class, 'fuel_voucher_id');
    }
}
