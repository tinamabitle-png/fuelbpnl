<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TaplessApiPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'public_key',
        'secret_encrypted',
        'webhook_url',
        'webhook_secret_encrypted',
        'allowed_ips',
        'meta',
        'last_used_at',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'meta' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function stations()
    {
        return $this->belongsToMany(FuelStation::class, 'tapless_api_partner_fuel_station')
            ->withTimestamps();
    }

    public function intents()
    {
        return $this->hasMany(TaplessPaymentIntent::class, 'partner_id');
    }

    public function decryptSecret(): string
    {
        return $this->secret_encrypted
            ? Crypt::decryptString($this->secret_encrypted)
            : '';
    }

    public function decryptWebhookSecret(): string
    {
        return $this->webhook_secret_encrypted
            ? Crypt::decryptString($this->webhook_secret_encrypted)
            : '';
    }

    public function isActive(): bool
    {
        return strtolower((string) $this->status) === 'active';
    }
}
