<?php

namespace App\Http\Controllers\Bnpl;

use App\Http\Controllers\Controller;
use App\Models\BnplOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function show(Request $request, BnplOrder $order)
    {
        // Route should be signed; this is an extra safety check.
        abort_unless($request->hasValidSignature(), 403);

        $order->loadMissing('driver', 'installments');

        $user = Auth::user();
        $canClaim = $user && $user->hasRole('shopper') && empty($order->shopper_id);
        $isShopper = $user && $user->hasRole('shopper') && (int) $order->shopper_id === (int) $user->id;

        return view('bnpl.checkout.show', compact('order', 'canClaim', 'isShopper'));
    }
}

