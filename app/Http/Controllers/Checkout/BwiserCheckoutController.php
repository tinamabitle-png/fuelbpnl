<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BwiserCheckoutController extends Controller
{
    public function embed(Request $request): View
    {
        $parentOrigin = (string) ($request->headers->get('referer') ?: $request->headers->get('origin', ''));
        $parentHost = strtolower((string) (parse_url($parentOrigin, PHP_URL_HOST) ?: $parentOrigin));
        $publicKey = trim((string) $request->query('public_key'));
        $stationId = trim((string) $request->query('station_id'));

        return view('checkout.bwiser-embed', [
            'publicKey' => $publicKey,
            'stationId' => $stationId,
            'amount' => trim((string) $request->query('amount')),
            'reference' => trim((string) $request->query('reference')),
            'pump' => trim((string) $request->query('pump')),
            'checkoutToken' => Crypt::encryptString(json_encode([
                'public_key' => $publicKey,
                'station_id' => $stationId,
                'parent_origin' => $parentHost,
                'expires_at' => now()->addMinutes(15)->timestamp,
            ])),
        ]);
    }
}
