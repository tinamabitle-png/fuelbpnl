<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FuelStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'license_number',
        'address',
        'city',
        'country',
        'latitude',
        'longitude',
        'contact_person',
        'contact_phone',
        'contact_email',
        'status',
        'owner_id',
        'wallet_balance',
        'total_settlements',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'wallet_balance' => 'decimal:2',
        'total_settlements' => 'decimal:2',
    ];

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function vouchers()
    {
        return $this->hasMany(FuelVoucher::class);
    }

    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        $earthRadius = 6371; // kilometers

        return $query->selectRaw("
            *,
            ($earthRadius * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance
        ", [$latitude, $longitude, $latitude])
        ->having('distance', '<', $radius)
        ->orderBy('distance');
    }

    // Business logic methods
    public function getPendingSettlementAmount()
    {
        return $this->vouchers()
            ->where('status', 'redeemed')
            ->whereNull('settlement_id')
            ->sum('amount');
    }

    public function addToWallet($amount, $description = 'Settlement')
    {
        $this->increment('wallet_balance', $amount);
        $this->increment('total_settlements', $amount);

        // Log this transaction
        activity()
            ->performedOn($this)
            ->withProperties([
                'amount' => $amount,
                'description' => $description,
                'new_balance' => $this->wallet_balance,
            ])
            ->log('wallet_credited');

        return $this;
    }
}