<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_card_id',
        'masked_pan',
        'last4',
        'expiry_month',
        'expiry_year',
        'card_scheme',
        'brand',
        'label',
        'currency',
        'status',
        'allocated_amount',
        'metadata',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'metadata' => 'array',
        'expiry_month' => 'integer',
        'expiry_year' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['active', 'frozen']);
    }
}
