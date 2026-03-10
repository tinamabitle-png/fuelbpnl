<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class Otp extends Model
{
    use HasFactory;

    protected $table = 'otps';

    protected $fillable = [
        'user_id',
        'phone',
        'email',
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

    public static function generateForChannel(
        string $identifier,
        string $purpose = 'login',
        ?int $userId = null,
        string $channel = 'phone'
    ): self {
        $channel = strtolower(trim($channel));
        $isEmail = $channel === 'email';
        $supportsEmail = self::supportsEmailChannel();

        $query = self::query()->where('purpose', $purpose)->valid();
        if ($isEmail && $supportsEmail) {
            $query->where('email', $identifier);
        } else {
            $query->where('phone', $identifier);
        }
        $query->delete();

        $data = [
            'user_id' => $userId,
            'phone' => ($isEmail && $supportsEmail) ? '' : $identifier,
            'code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10),
        ];

        if ($supportsEmail) {
            $data['email'] = $isEmail ? $identifier : null;
        }

        return self::create($data);
    }

    public static function verifyForChannel(
        string $identifier,
        string $code,
        string $purpose = 'login',
        ?string $channel = null
    ): ?self {
        $normalizedChannel = strtolower(trim((string) $channel));
        $supportsEmail = self::supportsEmailChannel();
        $query = self::query()
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->valid();

        if ($normalizedChannel === 'email' && $supportsEmail) {
            $query->where('email', $identifier);
        } elseif ($normalizedChannel === 'phone') {
            $query->where('phone', $identifier);
        } else {
            $query->where(function ($q) use ($identifier, $supportsEmail) {
                $q->where('phone', $identifier);
                if ($supportsEmail) {
                    $q->orWhere('email', $identifier);
                }
            });
        }

        $otp = $query->first();
        if ($otp) {
            $otp->markAsUsed();
            return $otp;
        }

        return null;
    }

    protected static function supportsEmailChannel(): bool
    {
        static $supports = null;
        if ($supports === null) {
            $supports = Schema::hasColumn('otps', 'email');
        }

        return (bool) $supports;
    }
}
