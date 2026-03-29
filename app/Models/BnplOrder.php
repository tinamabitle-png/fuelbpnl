<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BnplOrder extends Model
{
    protected $table = 'bnpl_orders';

    protected $fillable = [
        'driver_id',
        'shopper_id',
        'reference',
        'status',
        'title',
        'description',
        'amount_total',
        'deposit_amount',
        'financed_amount',
        'installments_count',
        'currency',
        'expires_at',
        'approved_at',
        'fulfilled_at',
        'cancelled_at',
        'handover_otp_hash',
        'handover_completed_at',
        'metadata',
    ];

    protected $casts = [
        'amount_total' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'financed_amount' => 'decimal:2',
        'installments_count' => 'integer',
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'handover_completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function shopper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shopper_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(BnplInstallment::class, 'bnpl_order_id')->orderBy('sequence');
    }
}

