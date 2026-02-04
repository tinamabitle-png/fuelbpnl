<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'device_fingerprint',
        'credit_score',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'credit_score' => 'integer',
    ];

    protected $appends = ['available_credit'];

    // Relationships
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function creditLimit()
    {
        return $this->hasOne(CreditLimit::class);
    }

    public function vouchers()
    {
        return $this->hasMany(FuelVoucher::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }

    public function walletTransactions()
    {
        return $this->hasManyThrough(WalletTransaction::class, Wallet::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Accessors
    public function getAvailableCreditAttribute()
    {
        if (!$this->creditLimit) {
            return 0;
        }
        return max(0, $this->creditLimit->limit - $this->wallet->outstanding_balance);
    }

    public function getIsDriverAttribute()
    {
        return $this->hasRole('driver');
    }

    public function getIsMerchantAttribute()
    {
        return $this->hasRole('merchant');
    }

    public function getIsEmployeeAttribute()
    {
        return $this->hasRole('employee');
    }

    public function getIsSuperAdminAttribute()
    {
        return $this->hasRole('super_admin');
    }

    // Business logic methods
    public function canRequestVoucher($amount)
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->wallet->balance >= $amount) {
            return true;
        }

        return $this->available_credit >= $amount;
    }

    public function getDefaultRisk()
    {
        $defaults = $this->leases()->where('status', 'defaulted')->count();
        $total = $this->leases()->count();
        
        if ($total === 0) return 0;
        
        return ($defaults / $total) * 100;
    }
}