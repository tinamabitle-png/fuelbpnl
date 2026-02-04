<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\FuelStationController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\LeaseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\ApprovalController as EmployeeApprovalController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Investor\InvestorDashboardController;
use App\Http\Controllers\Admin\InvestorController as AdminInvestorController;

// ========== PUBLIC ROUTES ==========
Route::get('/', function () {
    return view('welcome');
});

// ========== SETUP & FIX ROUTES ==========
Route::get('/setup-roles', function() {
    $roles = ['super_admin', 'admin', 'employee', 'investor', 'merchant', 'driver'];
    foreach ($roles as $role) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role]);
    }
    
    $user = \App\Models\User::first();
    if ($user) {
        $user->assignRole('super_admin');
    }
    
    return "✅ Roles created! First user is super_admin.<br>
           <a href='/test-roles'>Test roles</a>";
});

Route::get('/test-roles', function() {
    $user = auth()->user();
    
    if (!$user) {
        return "Not logged in. <a href='/login'>Login</a>";
    }
    
    return [
        'email' => $user->email,
        'roles' => $user->getRoleNames()->toArray(),
        'can_access_investor' => $user->hasAnyRole(['super_admin', 'admin', 'investor'])
    ];
});

Route::get('/add-investor-role', function() {
    $user = auth()->user();
    
    if (!$user) {
        return redirect('/login');
    }
    
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'investor']);
    $user->assignRole('investor');
    
    return redirect('/test-roles')->with('success', 'Added investor role');
})->middleware('auth');

// ========== AUTH ROUTES ==========
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ========== HELPER FUNCTION FOR ACCESS CONTROL ==========
function checkUserAccess($requiredRoles = [])
{
    $user = auth()->user();
    if (!$user || !$user->hasAnyRole($requiredRoles)) {
        abort(403, 'Access denied. Required roles: ' . implode(', ', $requiredRoles));
    }
    return true;
}

// ========== PROTECTED ROUTES ==========
Route::middleware(['auth'])->group(function () {
    
    // ========== INVESTOR ROUTES ==========
    Route::prefix('investor')->name('investor.')->group(function () {
        Route::get('/dashboard', function() {
            checkUserAccess(['super_admin', 'admin', 'investor']);
            
          
            return app(InvestorDashboardController::class)->index();
        })->name('dashboard');
        
         Route::get('/investments', function() {
            checkUserAccess(['super_admin', 'admin', 'investor']);
            return app(InvestorDashboardController::class)->investments(request());
        })->name('investments');
        
        Route::get('/investments/{investment}', [InvestorDashboardController::class, 'showInvestment'])->name('investments.show');
        
        Route::get('/opportunities', function() {
            checkUserAccess(['super_admin', 'admin', 'investor']);
            return app(InvestorDashboardController::class)->opportunities(request());
        })->name('opportunities');
        
        Route::post('/invest', function() {
            checkUserAccess(['super_admin', 'admin', 'investor']);
            return app(InvestorDashboardController::class)->invest(request());
        })->name('invest');
        
        Route::get('/profile', [InvestorDashboardController::class, 'profile'])->name('profile');
        
        Route::put('/preferences', function() {
            checkUserAccess(['super_admin', 'admin', 'investor']);
            return app(InvestorDashboardController::class)->updatePreferences(request());
        })->name('preferences.update');
        
        Route::get('/statements', function() {
            checkUserAccess(['super_admin', 'admin', 'investor']);
            return app(InvestorDashboardController::class)->statements(request());
        })->name('statements');
    });
    
    // ========== ADMIN ROUTES ==========
    Route::prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', function() {
            checkUserAccess(['super_admin', 'admin', 'employee']);
            return app(AdminDashboardController::class)->index();
        })->name('dashboard');
        
        // ========== USERS ROUTES ==========
        Route::get('/users', function() {
            checkUserAccess(['super_admin', 'admin']);
            return app(AdminUserController::class)->index(request());
        })->name('users.index');
        
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        
        // ========== SETTINGS ROUTES ==========
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->index();
            })->name('index');
            
            Route::post('/general', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->updateGeneral(request());
            })->name('update-general');
            
            Route::post('/mail', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->updateMail(request());
            })->name('update-mail');
            
            Route::post('/payment', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->updatePayment(request());
            })->name('update-payment');
            
            Route::post('/vouchers', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->updateVouchers(request());
            })->name('update-vouchers');
            
            Route::post('/system', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->updateSystem(request());
            })->name('update-system');
            
            Route::post('/clear-cache', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->clearCache(request());
            })->name('clear-cache');
            
            Route::post('/backup', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->backupDatabase(request());
            })->name('backup');
            
            Route::get('/logs', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->viewLogs();
            })->name('logs');
            
            Route::post('/clear-logs', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->clearLogs(request());
            })->name('clear-logs');
            
            Route::get('/system-info', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettingsController::class)->systemInfo();
            })->name('system-info');
            
            Route::post('/test-email', function(Request $request) {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                try {
                    Mail::raw('Test email from Fuel BNPL Admin', function ($message) use ($request) {
                        $message->to($request->email)
                                ->subject('Test Email from Fuel BNPL');
                    });
                    return response()->json(['success' => true]);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()]);
                }
            })->name('test-email');
        });
        
        // ========== FUEL STATIONS ROUTES ==========
        Route::prefix('stations')->name('stations.')->group(function () {
            Route::get('/', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(FuelStationController::class)->index(request());
            })->name('index');
            
            Route::get('/create', [FuelStationController::class, 'create'])->name('create');
            Route::post('/', [FuelStationController::class, 'store'])->name('store');
            Route::get('/{station}', [FuelStationController::class, 'show'])->name('show');
            Route::get('/{station}/edit', [FuelStationController::class, 'edit'])->name('edit');
            Route::put('/{station}', [FuelStationController::class, 'update'])->name('update');
            Route::delete('/{station}', [FuelStationController::class, 'destroy'])->name('destroy');
        });
        
        // ========== SETTLEMENTS ROUTES ==========
        Route::prefix('settlements')->name('settlements.')->group(function () {
            Route::get('/', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettlementController::class)->index(request());
            })->name('index');
            
            Route::get('/create', [SettlementController::class, 'create'])->name('create');
            Route::post('/', [SettlementController::class, 'store'])->name('store');
            Route::get('/{settlement}', [SettlementController::class, 'show'])->name('show');
            Route::get('/{settlement}/edit', [SettlementController::class, 'edit'])->name('edit');
            Route::put('/{settlement}', [SettlementController::class, 'update'])->name('update');
            Route::delete('/{settlement}', [SettlementController::class, 'destroy'])->name('destroy');
            
            Route::post('/{settlement}/process', [SettlementController::class, 'process'])->name('process');
            Route::post('/{settlement}/mark-as-failed', [SettlementController::class, 'markAsFailed'])->name('mark-as-failed');
            
            Route::post('/bulk-process', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettlementController::class)->bulkProcess(request());
            })->name('bulk-process');
            
            Route::get('/export', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettlementController::class)->export(request());
            })->name('export');
            
            Route::get('/api/pending-amount', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettlementController::class)->getPendingAmount(request());
            })->name('api.pending-amount');
            
            Route::get('/api/statistics', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(SettlementController::class)->statistics(request());
            })->name('api.statistics');
        });
        
              // ========== VOUCHERS ROUTES ==========
        Route::prefix('vouchers')->name('vouchers.')->group(function () {
            // List all vouchers
            Route::get('/', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(VoucherController::class)->index(request());
            })->name('index');
            
            // Pending vouchers
            Route::get('/pending', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(VoucherController::class)->pending(request());
            })->name('pending');
            
            // Show single voucher
            Route::get('/{voucher}', [VoucherController::class, 'show'])->name('show');
            
            // Approve voucher
            Route::post('/{voucher}/approve', [VoucherController::class, 'approve'])->name('approve');
            
            // Reject voucher
            Route::post('/{voucher}/reject', [VoucherController::class, 'reject'])->name('reject');
            
            // Bulk actions
            Route::post('/bulk-action', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(VoucherController::class)->bulkAction(request());
            })->name('bulk-action');
            
            // Export vouchers
            Route::get('/export', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(VoucherController::class)->export(request());
            })->name('export');
        });
        
        // ========== LEASES ROUTES ==========
        Route::prefix('leases')->name('leases.')->group(function () {
            Route::get('/', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(LeaseController::class)->index(request());
            })->name('index');
            
            Route::get('/create', [LeaseController::class, 'create'])->name('create');
            Route::post('/', [LeaseController::class, 'store'])->name('store');
            Route::get('/{lease}', [LeaseController::class, 'show'])->name('show');
            Route::get('/{lease}/edit', [LeaseController::class, 'edit'])->name('edit');
            Route::put('/{lease}', [LeaseController::class, 'update'])->name('update');
            Route::delete('/{lease}', [LeaseController::class, 'destroy'])->name('destroy');
            
            Route::post('/{lease}/payments', [LeaseController::class, 'recordPayment'])->name('payments.store');
            Route::post('/{lease}/quick-payment', [LeaseController::class, 'quickPayment'])->name('quick-payment');
            Route::post('/{lease}/extend', [LeaseController::class, 'extend'])->name('extend');
            Route::post('/{lease}/mark-defaulted', [LeaseController::class, 'markAsDefaulted'])->name('mark-defaulted');
            Route::post('/{lease}/toggle-status', [LeaseController::class, 'toggleStatus'])->name('toggle-status');
            
            Route::get('/{lease}/repayment-history', [LeaseController::class, 'repaymentHistory'])->name('repayment-history');
            
            Route::get('/export', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(LeaseController::class)->export(request());
            })->name('export');
            
            Route::get('/reports/overdue', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(LeaseController::class)->overdueReport(request());
            })->name('reports.overdue');
            
            Route::get('/reports/performance', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(LeaseController::class)->performanceReport(request());
            })->name('reports.performance');
            
            Route::get('/api/stats', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(LeaseController::class)->getStats(request());
            })->name('api.stats');
            
            Route::get('/api/recent', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(LeaseController::class)->getRecent(request());
            })->name('api.recent');
            
            Route::post('/calculate', function() {
                checkUserAccess(['super_admin', 'admin', 'employee']);
                return app(LeaseController::class)->calculate(request());
            })->name('calculate');
        });
        
        // ========== REPORTS ROUTES ==========
        Route::get('/reports', function() {
            checkUserAccess(['super_admin', 'admin', 'employee']);
            return app(AdminReportController::class)->index(request());
        })->name('reports.index');
        
        Route::get('/reports/financial', function() {
            checkUserAccess(['super_admin', 'admin', 'employee']);
            return app(AdminReportController::class)->financial(request());
        })->name('reports.financial');
        
        Route::get('/reports/risk', function() {
            checkUserAccess(['super_admin', 'admin', 'employee']);
            return app(AdminReportController::class)->risk(request());
        })->name('reports.risk');
        
        Route::get('/reports/export/{type}', function($type) {
            checkUserAccess(['super_admin', 'admin', 'employee']);
            return app(AdminReportController::class)->export(request(), $type);
        })->name('reports.export');
        
        // ========== INVESTORS MANAGEMENT ROUTES ==========
        Route::prefix('investors')->name('investors.')->group(function () {
            Route::get('/', function() {
                checkUserAccess(['super_admin', 'admin', 'employee', 'investor']);
                return app(AdminInvestorController::class)->index(request());
            })->name('index');
            
            Route::get('/create', [AdminInvestorController::class, 'create'])->name('create');
            Route::post('/', [AdminInvestorController::class, 'store'])->name('store');
            Route::get('/{investor}', [AdminInvestorController::class, 'show'])->name('show');
            Route::get('/{investor}/edit', [AdminInvestorController::class, 'edit'])->name('edit');
            Route::put('/{investor}', [AdminInvestorController::class, 'update'])->name('update');
            Route::delete('/{investor}', [AdminInvestorController::class, 'destroy'])->name('destroy');
            
            Route::post('/{investor}/capital', [AdminInvestorController::class, 'updateCapital'])->name('capital.update');
            Route::post('/{investor}/documents', [AdminInvestorController::class, 'uploadDocument'])->name('documents.upload');
            
            Route::post('/investor-documents/{document}/verify', [AdminInvestorController::class, 'verifyDocument'])->name('investor-documents.verify');
            
            Route::post('/{investor}/toggle-auto-invest', [AdminInvestorController::class, 'toggleAutoInvest'])->name('toggle-auto-invest');
            
            Route::get('/{investor}/opportunities', [AdminInvestorController::class, 'investmentOpportunities'])->name('opportunities');
            
            Route::post('/{investor}/invest', [AdminInvestorController::class, 'makeInvestment'])->name('invest');
        });
    });
    
    // ========== OTHER DASHBOARDS ==========
    
    // Employee
    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('/dashboard', function() {
            checkUserAccess(['super_admin', 'admin', 'employee']);
            return app(EmployeeDashboardController::class)->index();
        })->name('dashboard');
        
        Route::get('/approvals', function() {
            checkUserAccess(['super_admin', 'admin', 'employee']);
            return app(EmployeeApprovalController::class)->index(request());
        })->name('approvals');
    });
    
    // Merchant
    Route::prefix('merchant')->name('merchant.')->group(function () {
        Route::get('/dashboard', function() {
            checkUserAccess(['super_admin', 'admin', 'merchant']);
            return app(MerchantDashboardController::class)->index();
        })->name('dashboard');
    });
    
    // Driver
    Route::prefix('driver')->name('driver.')->group(function () {
        Route::get('/dashboard', function() {
            checkUserAccess(['super_admin', 'admin', 'driver', 'investor']);
            return app(DriverDashboardController::class)->index();
        })->name('dashboard');
    });
});

// ========== UTILITY ROUTES ==========
Route::get('/clear-cache', function() {
    \Artisan::call('cache:clear');
    \Artisan::call('route:clear');
    \Artisan::call('config:clear');
    \Artisan::call('view:clear');
    
    return "✅ Cache cleared! <a href='/'>Home</a>";
});

Route::get('/quick-login/{userId?}', function($userId = 1) {
    $user = \App\Models\User::find($userId);
    if (!$user) $user = \App\Models\User::first();
    
    if ($user) {
        auth()->login($user);
        return redirect('/test-roles')->with('success', "Logged in as {$user->email}");
    }
    
    return "No user found";
});