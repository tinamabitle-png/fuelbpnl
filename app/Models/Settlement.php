<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'fuel_station_id',
        'amount',
        'voucher_count',
        'status',
        'reference',
        'settlement_date',
        'processed_at',
        'payment_method',
        'transaction_reference',
        'notes',
        'approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'settlement_date' => 'date',
        'processed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($settlement) {
            if (!$settlement->reference) {
                $settlement->reference = 'STL-' . date('Ymd') . '-' . Str::upper(Str::random(6));
            }
            if (!$settlement->settlement_date) {
                $settlement->settlement_date = now();
            }
        });
    }

    // Relationships
    public function fuelStation()
    {
        return $this->belongsTo(FuelStation::class);
    }

    public function vouchers()
    {
        return $this->hasMany(FuelVoucher::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Business logic methods
    public function process()
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Only pending settlements can be processed');
        }

        // In production, this would integrate with payment gateway
        // For now, simulate successful processing
        
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'transaction_reference' => 'BANK-' . time() . '-' . Str::random(6),
        ]);

        // Credit the fuel station's wallet
        $this->fuelStation->addToWallet($this->amount, 'Settlement: ' . $this->reference);

        return $this;
    }

    public function markAsFailed($reason = '')
    {
        $this->update([
            'status' => 'failed',
            'notes' => $this->notes . "\nFailed: " . $reason,
        ]);

        return $this;
    }
}
