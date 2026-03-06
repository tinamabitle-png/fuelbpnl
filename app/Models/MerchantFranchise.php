<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantFranchise extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'logo_path',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'merchant_franchise_id');
    }
}

