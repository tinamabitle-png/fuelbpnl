<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\FuelVoucher;
use Illuminate\Support\Facades\Cache;

class FraudDetectionService
{
    public function checkVoucherRequest(User $user, float $amount): array
    {
        $riskScore = 0;
        $flags = [];
        
        // 1. Velocity check
        $recentRequests = FuelVoucher::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
            
        if ($recentRequests > 3) {
            $riskScore += 30;
            $flags[] = 'HIGH_VELOCITY';
        }
        
        // 2. Amount anomaly
        $avgAmount = FuelVoucher::where('user_id', $user->id)
            ->avg('amount') ?? 0;
            
        if ($amount > ($avgAmount * 2)) {
            $riskScore += 25;
            $flags[] = 'AMOUNT_ANOMALY';
        }
        
        // 3. Time anomaly (requests at odd hours)
        $hour = now()->hour;
        if ($hour < 5 || $hour > 23) {
            $riskScore += 15;
            $flags[] = 'ODD_HOUR';
        }
        
        // 4. Device fingerprint check (simplified)
        $deviceHash = request()->header('X-Device-Fingerprint');
        if ($deviceHash) {
            $suspiciousDevices = Cache::get('suspicious_devices', []);
            if (in_array($deviceHash, $suspiciousDevices)) {
                $riskScore += 50;
                $flags[] = 'SUSPICIOUS_DEVICE';
            }
        }
        
        // 5. Location check (if available)
        $location = request()->header('X-User-Location');
        if ($location) {
            // Check if location changed drastically recently
            $lastLocation = Cache::get("user_location:{$user->id}");
            if ($lastLocation && $this->calculateDistance($lastLocation, $location) > 100) {
                $riskScore += 20;
                $flags[] = 'LOCATION_JUMP';
            }
            Cache::put("user_location:{$user->id}", $location, now()->addHours(1));
        }
        
        return [
            'risk_score' => $riskScore,
            'flags' => $flags,
            'approved' => $riskScore < 70,
            'requires_approval' => $riskScore >= 30 && $riskScore < 70,
            'blocked' => $riskScore >= 70
        ];
    }
    
    private function calculateDistance(string $loc1, string $loc2): float
    {
        // Simplified distance calculation
        [$lat1, $lon1] = explode(',', $loc1);
        [$lat2, $lon2] = explode(',', $loc2);
        
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
}