<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\FuelVoucherController;
use App\Http\Controllers\Api\LeaseController;
use App\Http\Controllers\Api\FuelStationController;
use App\Http\Controllers\Api\RepaymentController;
use App\Http\Controllers\Api\CreditAssessmentController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VoucherController;
use App\Http\Controllers\Api\V1\StationController;
use App\Http\Controllers\Api\V1\MerchantDeveloperController;
use App\Http\Controllers\Api\UssdController;

Route::match(['GET', 'POST'], '/ussd/africas-talking', [UssdController::class, 'africasTalking'])
    ->middleware('throttle:ussd');

Route::prefix('v1')->group(function () {
    
    // Public routes
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/auth/login-with-otp', [AuthController::class, 'loginWithOtp']);
        Route::post('/auth/complete-otp-login', [AuthController::class, 'completeOtpLogin']);
        Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    });
    
    // Protected routes
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // Auth & Profile
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/profile', [AuthController::class, 'profile']);
        Route::put('/auth/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        
        // Profile Management
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'getProfile']);
            Route::put('/update', [ProfileController::class, 'updateProfile']);
            Route::post('/change-password', [ProfileController::class, 'changePassword']);
            Route::put('/notifications', [ProfileController::class, 'updateNotifications']);
            Route::put('/preferences', [ProfileController::class, 'updatePreferences']);
            Route::get('/devices', [ProfileController::class, 'devices']);
            Route::delete('/devices/{device}', [ProfileController::class, 'revokeDevice']);
            Route::get('/activity-log', [ProfileController::class, 'activityLog']);
            Route::post('/request-deletion', [ProfileController::class, 'requestDeletion']);
            Route::post('/export-data', [ProfileController::class, 'exportData']);
            Route::get('/support', [ProfileController::class, 'support']);
        });
        
        // Wallet
        Route::prefix('wallet')->group(function () {
            Route::get('/balance', [WalletController::class, 'balance']);
            Route::get('/transactions', [WalletController::class, 'transactions']);
            Route::post('/add-funds', [WalletController::class, 'addFunds']);
            Route::post('/withdraw', [WalletController::class, 'withdraw']);
            Route::get('/payment-methods', [WalletController::class, 'paymentMethods']);
            Route::post('/make-payment', [WalletController::class, 'makePayment']);
        });
        
        // Vouchers
        Route::prefix('vouchers')->group(function () {
            Route::post('/request', [FuelVoucherController::class, 'requestVoucher']);
            Route::get('/{id}/tap-token', [FuelVoucherController::class, 'tapToken'])->whereNumber('id');
            Route::get('/', [FuelVoucherController::class, 'myVouchers']);
            Route::get('/{id}', [FuelVoucherController::class, 'show'])->whereNumber('id');
            Route::post('/{id}/cancel', [FuelVoucherController::class, 'cancel'])->whereNumber('id');
        });
        
        // Leases (BNPL)
        Route::prefix('leases')->group(function () {
            Route::get('/', [LeaseController::class, 'index']);
            Route::get('/{id}', [LeaseController::class, 'show']);
            Route::get('/{id}/repayment-schedule', [LeaseController::class, 'repaymentSchedule']);
            Route::post('/{id}/request-extension', [LeaseController::class, 'requestExtension']);
            Route::get('/statistics', [LeaseController::class, 'statistics']);
        });
        
        // Repayments
        Route::prefix('repayments')->group(function () {
            Route::get('/upcoming', [RepaymentController::class, 'upcoming']);
            Route::get('/overdue', [RepaymentController::class, 'overdue']);
            Route::get('/history', [RepaymentController::class, 'history']);
            Route::post('/make-payment', [RepaymentController::class, 'makePayment']);
            Route::post('/{repayment}/paystack/initialize', [RepaymentController::class, 'initializePaystack'])->whereNumber('repayment');
            Route::post('/paystack/verify', [RepaymentController::class, 'verifyPaystack']);
            Route::post('/autopay/paystack/initialize', [RepaymentController::class, 'initializeAutopayPaystack']);
            Route::post('/autopay/paystack/verify', [RepaymentController::class, 'verifyAutopayPaystack']);
            Route::post('/setup-auto-payment', [RepaymentController::class, 'setupAutoPayment']);
            Route::get('/reminders', [RepaymentController::class, 'reminders']);
            Route::get('/statistics', [RepaymentController::class, 'statistics']);
        });
        
        // Fuel Stations
        Route::prefix('stations')->group(function () {
            Route::get('/nearby', [FuelStationController::class, 'nearby']);
            Route::get('/search', [FuelStationController::class, 'search']);
            Route::get('/{id}', [FuelStationController::class, 'show']);
            Route::get('/prices', [FuelStationController::class, 'prices']);
        });
        
        // Credit Assessment
        Route::prefix('credit')->group(function () {
            Route::get('/eligibility', [CreditAssessmentController::class, 'checkEligibility']);
            Route::post('/apply-increase', [CreditAssessmentController::class, 'applyForIncrease']);
            Route::get('/factors', [CreditAssessmentController::class, 'getCreditFactors']);
            Route::post('/simulate-purchase', [CreditAssessmentController::class, 'simulatePurchase']);
            Route::get('/limit-history', [CreditAssessmentController::class, 'getLimitHistory']);
        });
        
        // Driver routes
        Route::middleware(['role:driver'])->group(function () {
            Route::get('/driver/dashboard', [UserController::class, 'dashboard']);
            Route::get('/driver/leases', [UserController::class, 'leases']);
        });
        
        // Merchant routes
        Route::middleware(['role:merchant'])->group(function () {
            Route::post('/merchant/redeem-voucher', [VoucherController::class, 'redeem']);
            Route::get('/merchant/settlements', [StationController::class, 'settlements']);

            Route::prefix('merchant/developer')->group(function () {
                Route::get('/stations', [MerchantDeveloperController::class, 'stations']);
                Route::get('/summary', [MerchantDeveloperController::class, 'summary']);
                Route::get('/vouchers', [MerchantDeveloperController::class, 'vouchers']);
                Route::get('/vouchers/latest', [MerchantDeveloperController::class, 'latestVouchers']);
                Route::get('/ussd/events', [MerchantDeveloperController::class, 'ussdEvents']);
                Route::post('/vouchers/redeem', [MerchantDeveloperController::class, 'redeem']);
                Route::post('/vouchers/offline-sync', [MerchantDeveloperController::class, 'offlineSync']);
                Route::get('/repayments', [MerchantDeveloperController::class, 'repayments']);

                Route::prefix('sandbox')->group(function () {
                    Route::get('/health', [MerchantDeveloperController::class, 'sandboxHealth']);
                    Route::get('/stations', [MerchantDeveloperController::class, 'sandboxStations']);
                    Route::get('/vouchers', [MerchantDeveloperController::class, 'sandboxVouchers']);
                    Route::post('/vouchers/redeem', [MerchantDeveloperController::class, 'sandboxRedeem']);
                    Route::get('/repayments', [MerchantDeveloperController::class, 'sandboxRepayments']);
                });
            });
        });
    });
});
