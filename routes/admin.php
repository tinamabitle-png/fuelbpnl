<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\StationController;
use App\Http\Controllers\Admin\LeaseController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\LiveFeedController;

Route::middleware(['auth', 'role:super_admin|employee'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Live feed (polling)
    Route::get('/live/form-interactions', [LiveFeedController::class, 'formInteractions'])->name('live.form-interactions');
    
    // Users
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/credit-limit', [UserController::class, 'updateCreditLimit'])->name('users.update-credit-limit');
    Route::post('/users/{user}/wallet', [UserController::class, 'updateWallet'])->name('users.update-wallet');
    
    // Fuel Stations
    Route::resource('stations', StationController::class);
    
    // Vouchers
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/pending', [VoucherController::class, 'pending'])->name('vouchers.pending');
    Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::post('/vouchers/{voucher}/approve', [VoucherController::class, 'approve'])->name('vouchers.approve');
    Route::post('/vouchers/{voucher}/reject', [VoucherController::class, 'reject'])->name('vouchers.reject');
    Route::post('/vouchers/bulk-action', [VoucherController::class, 'bulkAction'])->name('vouchers.bulk-action');
    Route::get('/vouchers/export', [VoucherController::class, 'export'])->name('vouchers.export');
    
    // Leases
    Route::get('/leases', [LeaseController::class, 'index'])->name('leases.index');
    Route::get('/leases/{lease}', [LeaseController::class, 'show'])->name('leases.show');
    Route::post('/leases/{lease}/mark-defaulted', [LeaseController::class, 'markDefaulted'])->name('leases.mark-defaulted');
    Route::post('/leases/{lease}/extend', [LeaseController::class, 'extend'])->name('leases.extend');
    
    // Settlements
    Route::get('/settlements', [SettlementController::class, 'index'])->name('settlements.index');
    Route::post('/settlements/process', [SettlementController::class, 'process'])->name('settlements.process');
    Route::post('/settlements/{settlement}/mark-paid', [SettlementController::class, 'markPaid'])->name('settlements.mark-paid');
    
    // Reports
    Route::get('/reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
    Route::get('/reports/risk', [ReportController::class, 'risk'])->name('reports.risk');
    Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
});
