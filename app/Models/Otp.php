<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Otp extends Model
{
    use HasFactory;

    protected $table = 'otps';

    protected $fillable = [
        'user_id',
        'phone',
        'code',
        'purpose',
        'used',
        'expires_at',
    ];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('used', false)
                     ->where('expires_at', '>', now());
    }

    // Business logic methods
    public function isValid()
    {
        return !$this->used && $this->expires_at > now();
    }

    public function markAsUsed()
    {
        $this->update(['used' => true]);
        return $this;
    }

    public static function generate($phone, $purpose = 'login', $userId = null)
    {
        // Delete any existing valid OTPs for this phone and purpose
        self::where('phone', $phone)
            ->where('purpose', $purpose)
            ->valid()
            ->delete();

        // Generate new OTP
        return self::create([
            'user_id' => $userId,
            'phone' => $phone,
            'code' => str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public static function verify($phone, $code, $purpose = 'login')
    {
        $otp = self::where('phone', $phone)
                    ->where('code', $code)
                    ->where('purpose', $purpose)
                    ->valid()
                    ->first();

        if ($otp) {
            $otp->markAsUsed();
            return $otp;
        }

        return null;
    }
}