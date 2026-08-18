<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTeamSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'plan_slug',
        'plan_name',
        'amount',
        'currency',
        'billing_cycle',
        'loan_book_limit',
        'status',
        'paystack_reference',
        'paystack_access_code',
        'paystack_authorization_url',
        'paid_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'loan_book_limit' => 'integer',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public static function plans(): array
    {
        return [
            'starter' => [
                'slug' => 'starter',
                'name' => 'Loan Book Starter',
                'amount' => 499.00,
                'currency' => 'ZAR',
                'billing_cycle' => 'monthly',
                'loan_book_limit' => 250,
                'highlight' => true,
                'description' => 'For smaller finance teams that need a live view of their active Bwiser loan book.',
                'features' => [
                    'Up to 250 active leases',
                    'Loan-book dashboard access',
                    'Repayment ageing and arrears view',
                    'CSV exports for reconciliation',
                    'Email onboarding support',
                ],
            ],
            'portfolio' => [
                'slug' => 'portfolio',
                'name' => 'Portfolio Pro',
                'amount' => 1499.00,
                'currency' => 'ZAR',
                'billing_cycle' => 'monthly',
                'loan_book_limit' => 2500,
                'highlight' => false,
                'description' => 'For larger finance teams that need deeper portfolio controls and lease allocation visibility.',
                'features' => [
                    'Up to 2,500 active leases',
                    'Finance-company wallet visibility',
                    'Investor-approved lease tracking',
                    'Risk score notes on loan files',
                    'Priority onboarding support',
                ],
            ],
        ];
    }

    public static function plan(string $slug): ?array
    {
        return self::plans()[$slug] ?? null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
