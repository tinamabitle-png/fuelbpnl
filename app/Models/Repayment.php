<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'user_id',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'transaction_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
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
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'transaction_reference' => $reference,
        ]);

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