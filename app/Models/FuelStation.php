<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\AuditTrailService;

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
        'payout_method',
        'payout_bank_name',
        'payout_bank_code',
        'payout_account_name',
        'payout_account_number',
        'payout_branch_code',
        'payout_reference',
        'payout_email',
        'payout_recipient_code',
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

    public function merchant()
    {
        return $this->owner();
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
        $before = (float) $this->wallet_balance;
        $this->increment('wallet_balance', $amount);
        $this->increment('total_settlements', $amount);
        $after = (float) $this->fresh()->wallet_balance;

        // Activity package might not be installed in all environments.
        if (function_exists('activity')) {
            activity()
                ->performedOn($this)
                ->withProperties([
                    'amount' => $amount,
                    'description' => $description,
                    'new_balance' => $this->wallet_balance,
                ])
                ->log('wallet_credited');
        } else {
            \Log::info('station_wallet_credited', [
                'station_id' => $this->id,
                'amount' => (float) $amount,
                'description' => $description,
                'new_balance' => $after,
            ]);
        }

        AuditTrailService::record(
            'station_wallet_credit',
            $this,
            ['wallet_balance' => $before],
            ['wallet_balance' => $after, 'amount' => (float) $amount, 'description' => (string) $description],
            'Station wallet credited'
        );

        return $this;
    }

    public function deductFromWallet($amount, $description = 'Voucher redemption')
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            throw new \Exception('Invalid debit amount');
        }

        $before = (float) $this->wallet_balance;
        $currentBalance = $before;
        if ($currentBalance < $amount) {
            throw new \Exception('Insufficient station wallet balance');
        }

        $this->decrement('wallet_balance', $amount);
        $after = (float) $this->fresh()->wallet_balance;

        if (function_exists('activity')) {
            activity()
                ->performedOn($this)
                ->withProperties([
                    'amount' => $amount,
                    'description' => $description,
                    'new_balance' => (float) $this->fresh()->wallet_balance,
                ])
                ->log('wallet_debited');
        } else {
            \Log::info('station_wallet_debited', [
                'station_id' => $this->id,
                'amount' => $amount,
                'description' => $description,
                'new_balance' => $after,
            ]);
        }

        AuditTrailService::record(
            'station_wallet_debit',
            $this,
            ['wallet_balance' => $before],
            ['wallet_balance' => $after, 'amount' => $amount, 'description' => (string) $description],
            'Station wallet debited'
        );

        return $this;
    }
}
