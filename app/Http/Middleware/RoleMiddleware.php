<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Convert pipe-separated roles to array
        if (count($roles) === 1 && str_contains($roles[0], '|')) {
            $roles = explode('|', $roles[0]);
        }
        
        // Check if user has any of the required roles
        foreach ($roles as $role) {
            $role = trim($role);
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // If user doesn't have any required role
        return abort(403, 'Unauthorized access.');
    }
}