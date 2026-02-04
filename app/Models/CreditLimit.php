<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'credit_limit', // Changed from 'limit'
        'used',
        'review_date',
        'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2', // Changed from 'limit'
        'used' => 'decimal:2',
        'review_date' => 'date',
    ];

    protected $appends = ['available'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getAvailableAttribute()
    {
        return max(0, $this->credit_limit - $this->used); // Changed from 'limit'
    }

    // Business logic methods
    public function canUse($amount)
    {
        return $this->available >= $amount;
    }

    public function useCredit($amount)
    {
        if (!$this->canUse($amount)) {
            throw new \Exception('Credit limit exceeded');
        }

        $this->increment('used', $amount);
        return $this;
    }

    public function releaseCredit($amount)
    {
        $this->decrement('used', min($amount, $this->used));
        return $this;
    }

    public function updateLimit($newLimit, $reason = '')
    {
        $oldLimit = $this->credit_limit; // Changed from 'limit'
        $this->update(['credit_limit' => $newLimit]); // Changed from 'limit'

        // Log this change
        activity()
            ->performedOn($this->user)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_limit' => $oldLimit,
                'new_limit' => $newLimit,
                'reason' => $reason,
            ])
            ->log('credit_limit_updated');

        return $this;
    }
}
