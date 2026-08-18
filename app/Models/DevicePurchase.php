<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevicePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_slug',
        'product_name',
        'buyer_name',
        'email',
        'phone',
        'amount',
        'currency',
        'status',
        'paystack_reference',
        'paystack_access_code',
        'paystack_authorization_url',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public static function bwiserPro(): array
    {
        return [
            'slug' => 'bwiser_pro_device',
            'name' => 'Bwiser Pro',
            'amount' => 1900.00,
            'currency' => 'ZAR',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
