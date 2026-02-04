<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lease;

class CheckInvestorOwnership
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if ($user->hasRole('investor') && $user->investor) {
            $investorId = $user->investor->id;
            
            // Check if accessing lease
            if ($request->route('lease')) {
                $lease = $request->route('lease');
                
                // Check if investor has investment in this lease
                if (!$lease->leaseInvestments()->where('investor_id', $investorId)->exists()) {
                    abort(403, 'Unauthorized access to this lease.');
                }
            }
            
            // Check if accessing investment
            if ($request->route('investment')) {
                $investment = $request->route('investment');
                
                if ($investment->investor_id !== $investorId) {
                    abort(403, 'Unauthorized access to this investment.');
                }
            }
        }
        
        return $next($request);
    }
}
