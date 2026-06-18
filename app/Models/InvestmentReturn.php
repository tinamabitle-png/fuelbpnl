<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_investment_id',
        'type',
        'amount',
        'payment_date',
        'reference',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function leaseInvestment()
    {
        return $this->belongsTo(LeaseInvestment::class);
    }
}
