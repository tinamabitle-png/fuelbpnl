<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UssdRedemptionEvent extends Model
{
    protected $fillable = [
        'session_id',
        'service_code',
        'network_code',
        'phone_raw',
        'phone_normalized',
        'ussd_text',
        'pump_number',
        'user_id',
        'merchant_user_id',
        'fuel_station_id',
        'fuel_voucher_id',
        'voucher_code',
        'status',
        'dispatch_token',
        'dispatched_at',
        'completed_at',
        'completed_by_user_id',
        'error_message',
        'receipt_payload',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'completed_at' => 'datetime',
        'receipt_payload' => 'array',
    ];
}
