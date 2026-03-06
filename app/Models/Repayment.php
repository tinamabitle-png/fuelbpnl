<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\AuditTrailService;

class Repayment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'lease_id',
        'user_id',
        'amount',
        'repayment_type',
        'base_repayment_id',
        'due_date',
        'charged_for_date',
        'paid_at',
        'status',
        'payment_method',
        'transaction_reference',
        'metadata',
        'autopay_attempts',
        'autopay_last_attempt_at',
        'autopay_next_attempt_at',
        'autopay_status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'charged_for_date' => 'date',
        'paid_at' => 'datetime',
        'metadata' => 'array',
        'autopay_last_attempt_at' => 'datetime',
        'autopay_next_attempt_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function lease()
    {
        return $this->belongsTo(Lease::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function baseRepayment()
    {
        return $this->belongsTo(self::class, 'base_repayment_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                  ->where('due_date', '<', now());
            });
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeVisibleInSystem($query)
    {
        return $query->where(function ($q) {
            $q->whereNotIn('status', ['pending', 'overdue'])
                ->orWhereHas('lease.vouchers', function ($voucherQuery) {
                    $voucherQuery->where('status', 'redeemed');
                });
        });
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->status === 'overdue' || 
               ($this->status === 'pending' && $this->due_date < now());
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    // Business logic methods
    public function markAsPaid($paymentMethod = 'manual', $reference = null)
    {
        $old = [
            'status' => (string) $this->status,
            'paid_at' => $this->paid_at,
            'payment_method' => (string) ($this->payment_method ?? ''),
            'transaction_reference' => (string) ($this->transaction_reference ?? ''),
        ];

        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'transaction_reference' => $reference,
        ]);

        AuditTrailService::record(
            'repayment_marked_paid',
            $this,
            $old,
            [
                'status' => 'paid',
                'paid_at' => $this->paid_at,
                'payment_method' => (string) $paymentMethod,
                'transaction_reference' => (string) ($reference ?? ''),
                'amount' => (float) $this->amount,
            ],
            'Repayment marked as paid'
        );

        return $this;
    }

    public function markAsOverdue()
    {
        if ($this->status === 'pending' && $this->due_date < now()) {
            $this->update(['status' => 'overdue']);
        }

        return $this;
    }
}
