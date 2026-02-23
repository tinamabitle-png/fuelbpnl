<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'phone']),
                'wallet' => $user->wallet,
                'credit_limit' => $user->creditLimit,
            ],
        ]);
    }

    public function leases(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => Lease::where('user_id', $request->user()->id)->latest()->paginate(20),
        ]);
    }
}
