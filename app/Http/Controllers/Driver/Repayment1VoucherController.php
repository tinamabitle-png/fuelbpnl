<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Repayment1VoucherController as ApiRepayment1VoucherController;
use App\Services\Flutterwave1VoucherService;
use App\Services\RepaymentSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Repayment1VoucherController extends Controller
{
    /**
     * Web driver portal: submit 1Voucher PIN to pay the next 7 days of repayments.
     */
    public function payWeek(Request $request, Flutterwave1VoucherService $flutterwave, RepaymentSettlementService $settler)
    {
        $user = Auth::user();
        abort_unless($user, 401);

        // Reuse the API controller logic to avoid duplicating the payment flow.
        /** @var ApiRepayment1VoucherController $api */
        $api = app(ApiRepayment1VoucherController::class);
        $request->setUserResolver(fn () => $user);

        $response = $api->payWeek($request, $flutterwave, $settler);
        $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];

        if (($payload['success'] ?? false) === true) {
            $change = (array) (($payload['data']['change_voucher'] ?? null) ?: []);
            $msg = (string) ($payload['message'] ?? '1Voucher payment successful.');
            if (!empty($change['pin'])) {
                $msg .= ' New PIN: ' . $change['pin'];
            }
            return back()->with('success', $msg);
        }

        $status = (int) method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 400;
        $msg = (string) ($payload['message'] ?? '1Voucher payment failed.');
        if ($status === 202) {
            return back()->with('error', $msg . ' (Pending confirmation)');
        }

        return back()->with('error', $msg);
    }
}

