<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BnplInstallment extends Model
{
    protected $table = 'bnpl_installments';

    protected $fillable = [
        'bnpl_order_id',
        'sequence',
        'due_at',
        'amount',
        'status',
        'paid_at',
        'payment_gateway',
        'payment_reference',
        'metadata',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'due_at' => 'datetime',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BnplOrder::class, 'bnpl_order_id');
    }
}

