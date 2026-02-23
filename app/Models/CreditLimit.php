<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'limit',
        'used',
        'review_date',
        'status',
    ];

    protected $casts = [
        'limit' => 'decimal:2',
        'used' => 'decimal:2',
        'review_date' => 'date',
    ];

    protected $appends = ['available', 'credit_limit'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getAvailableAttribute()
    {
        return max(0, $this->limit - $this->used);
    }

    // Backward-compatible alias used by older parts of the app.
    public function getCreditLimitAttribute()
    {
        return $this->limit;
    }

    public function setCreditLimitAttribute($value): void
    {
        $this->attributes['limit'] = $value;
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
        $oldLimit = $this->credit_limit;
        $this->update(['limit' => $newLimit]);

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
