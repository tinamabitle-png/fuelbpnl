<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'merchant_franchise_id',
        'business_address',
        'city',
        'country',
        'latitude',
        'longitude',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
        'metadata',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function franchise()
    {
        return $this->belongsTo(MerchantFranchise::class, 'merchant_franchise_id');
    }
}
