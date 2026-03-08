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
        'google_sub',
        'phone',
        'id_number',
        'id_document_path',
        'driver_license_path',
        'bank_statement_path',
        'payment_method_preference',
        'payment_account_name',
        'payment_account_number',
        'payment_bank_name',
        'payment_branch_code',
        'id_verification_status',
        'id_verified_at',
        'id_verification_provider',
        'merchant_franchise_id',
        'home_address',
        'city',
        'country',
        'latitude',
        'longitude',
        'driver_platform',
        'driver_platform_other',
        'password',
        'device_fingerprint',
        'credit_score',
        'status',
        'autopay_enabled',
        'autopay_gateway',
        'autopay_token',
        'autopay_email',
        'autopay_customer_code',
        'autopay_details',
        'autopay_status',
        'autopay_failures',
        'autopay_last_attempt_at',
        'autopay_next_attempt_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'autopay_token',
        'autopay_email',
        'autopay_customer_code',
        'autopay_details',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'credit_score' => 'integer',
        'autopay_enabled' => 'boolean',
        'autopay_token' => 'encrypted',
        'autopay_email' => 'encrypted',
        'autopay_customer_code' => 'encrypted',
        'autopay_details' => 'encrypted:array',
        'autopay_failures' => 'integer',
        'autopay_last_attempt_at' => 'datetime',
        'autopay_next_attempt_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
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

    public function ownedStations()
    {
        return $this->hasMany(FuelStation::class, 'owner_id');
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class)->visibleInSystem();
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

    public function driverDocuments()
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function bankStatementUploads()
    {
        return $this->hasMany(BankStatementUpload::class);
    }

    public function creditDecisions()
    {
        return $this->hasMany(CreditDecision::class);
    }

    public function merchantFranchise()
    {
        return $this->belongsTo(MerchantFranchise::class, 'merchant_franchise_id');
    }

    public function accountApprovals()
    {
        return $this->hasMany(AccountApproval::class);
    }

    public function latestAccountApproval()
    {
        return $this->hasOne(AccountApproval::class)->latestOfMany();
    }

    public function latestCreditDecision()
    {
        return $this->hasOne(CreditDecision::class)->latestOfMany('decided_at');
    }

    // Accessors
    public function getAvailableCreditAttribute()
    {
        if (!$this->creditLimit) {
            return 0;
        }
        $outstanding = (float) optional($this->wallet)->outstanding_balance;
        return max(0, (float) $this->creditLimit->limit - $outstanding);
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

    public function isAutopayReady(): bool
    {
        if (!$this->autopay_enabled) {
            return false;
        }

        if (strtolower((string) $this->autopay_gateway) !== 'paystack') {
            return false;
        }

        if (trim((string) $this->autopay_token) === '') {
            return false;
        }

        $status = strtolower((string) ($this->autopay_status ?? 'inactive'));
        $blocked = ['disabled', 'failed', 'max_retries_exceeded', 'inactive'];
        return !in_array($status, $blocked, true);
    }
}
