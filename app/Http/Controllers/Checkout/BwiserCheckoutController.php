<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BwiserCheckoutController extends Controller
{
    public function embed(Request $request): View
    {
        return view('checkout.bwiser-embed', [
            'publicKey' => trim((string) $request->query('public_key')),
            'stationId' => trim((string) $request->query('station_id')),
            'amount' => trim((string) $request->query('amount')),
            'reference' => trim((string) $request->query('reference')),
            'pump' => trim((string) $request->query('pump')),
        ]);
    }
}
