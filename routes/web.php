<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RegistrationDocumentsController;
use App\Http\Controllers\HereMapsController;
use App\Http\Controllers\GoogleMapsController;
use App\Http\Controllers\AccountDeletionController;
use App\Http\Controllers\Admin\FuelStationController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\LeaseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\RepaymentOpsController as AdminRepaymentOpsController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\ApprovalController as EmployeeApprovalController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\VirtualCardController as DriverVirtualCardController;
use App\Http\Controllers\Driver\Repayment1VoucherController as DriverRepayment1VoucherController;
use App\Http\Controllers\Driver\RepaymentPayShapController as DriverRepaymentPayShapController;
use App\Http\Controllers\Driver\RepaymentCryptoController as DriverRepaymentCryptoController;
use App\Http\Controllers\Driver\BankStatementController as DriverBankStatementController;
use App\Http\Controllers\Investor\InvestorDashboardController;
use App\Http\Controllers\Admin\InvestorController as AdminInvestorController;
use App\Http\Controllers\FeedbackController;
use App\Support\Seo\SitemapBuilder;

// ========== PUBLIC ROUTES ==========
Route::get('/', function () {
    $payload = Cache::remember('public:welcome:stats:v1', now()->addMinutes(10), function () {
        $now = now();

        $totals = [
            'drivers' => 0,
            'stations' => 0,
            'vouchers' => 0,
            'repayments' => 0,
        ];

        if (Schema::hasTable('users')) {
            $totals['drivers'] = (int) DB::table('users')->count();
        }

        if (Schema::hasTable('fuel_stations')) {
            $totals['stations'] = (int) DB::table('fuel_stations')->count();
        }

        if (Schema::hasTable('fuel_vouchers')) {
            $totals['vouchers'] = (int) DB::table('fuel_vouchers')->count();
        }

        if (Schema::hasTable('repayments')) {
            $totals['repayments'] = (int) DB::table('repayments')->count();
        }

        // Driver count using roles if available (Spatie Permission).
        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles') && Schema::hasTable('users')) {
            try {
                $totals['drivers'] = (int) DB::table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('roles.name', 'driver')
                    ->where('model_has_roles.model_type', \App\Models\User::class)
                    ->count();
            } catch (\Throwable $e) {
                // Fallback to total users count (already set above).
            }
        }

        $voucherGrowth = [
            'current' => 0,
            'previous' => 0,
            'pct' => 0,
        ];
        if (Schema::hasTable('fuel_vouchers')) {
            $startCurrent = $now->copy()->subDays(30);
            $startPrev = $now->copy()->subDays(60);
            $endPrev = $now->copy()->subDays(30);

            $voucherGrowth['current'] = (int) DB::table('fuel_vouchers')
                ->where('created_at', '>=', $startCurrent)
                ->count();
            $voucherGrowth['previous'] = (int) DB::table('fuel_vouchers')
                ->where('created_at', '>=', $startPrev)
                ->where('created_at', '<', $endPrev)
                ->count();

            $prev = max(1, $voucherGrowth['previous']);
            $voucherGrowth['pct'] = (int) round((($voucherGrowth['current'] - $voucherGrowth['previous']) / $prev) * 100);
        }

        $labels = [];
        $monthKeys = [];
        $cursor = $now->copy()->startOfMonth()->subMonths(11);
        for ($i = 0; $i < 12; $i++) {
            $labels[] = $cursor->format('M');
            $monthKeys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $series = [
            'labels' => $labels,
            'months' => $monthKeys,
            'vouchers' => array_fill(0, 12, 0),
            'drivers' => array_fill(0, 12, 0),
        ];

        $driverNameExpr = null;
        $connectionDriver = DB::connection()->getDriverName();
        if ($connectionDriver === 'mysql') {
            $driverNameExpr = "DATE_FORMAT(u.created_at, '%Y-%m')";
        } elseif ($connectionDriver === 'sqlite') {
            $driverNameExpr = "strftime('%Y-%m', u.created_at)";
        } elseif ($connectionDriver === 'pgsql') {
            $driverNameExpr = "to_char(u.created_at, 'YYYY-MM')";
        }

        $voucherExpr = null;
        if ($connectionDriver === 'mysql') {
            $voucherExpr = "DATE_FORMAT(created_at, '%Y-%m')";
        } elseif ($connectionDriver === 'sqlite') {
            $voucherExpr = "strftime('%Y-%m', created_at)";
        } elseif ($connectionDriver === 'pgsql') {
            $voucherExpr = "to_char(created_at, 'YYYY-MM')";
        }

        $rangeStart = Carbon::parse($monthKeys[0] . '-01')->startOfMonth();
        $rangeEnd = $now->copy()->endOfMonth();

        if ($voucherExpr && Schema::hasTable('fuel_vouchers')) {
            $rows = DB::table('fuel_vouchers')
                ->selectRaw($voucherExpr . " as ym, COUNT(*) as c")
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->groupBy('ym')
                ->pluck('c', 'ym')
                ->toArray();
            foreach ($monthKeys as $idx => $ym) {
                $series['vouchers'][$idx] = (int) ($rows[$ym] ?? 0);
            }
        }

        if ($driverNameExpr && Schema::hasTable('model_has_roles') && Schema::hasTable('roles') && Schema::hasTable('users')) {
            try {
                $rows = DB::table('model_has_roles as mhr')
                    ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                    ->join('users as u', 'u.id', '=', 'mhr.model_id')
                    ->where('r.name', 'driver')
                    ->where('mhr.model_type', \App\Models\User::class)
                    ->whereBetween('u.created_at', [$rangeStart, $rangeEnd])
                    ->selectRaw($driverNameExpr . " as ym, COUNT(*) as c")
                    ->groupBy('ym')
                    ->pluck('c', 'ym')
                    ->toArray();
                foreach ($monthKeys as $idx => $ym) {
                    $series['drivers'][$idx] = (int) ($rows[$ym] ?? 0);
                }
            } catch (\Throwable $e) {
                // leave zeros
            }
        }

        $recentDrivers = [];
        if (Schema::hasTable('users')) {
            try {
                $recentDriverQuery = DB::table('users as u')
                    ->select('u.name', 'u.created_at');

                if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
                    $recentDriverQuery
                        ->join('model_has_roles as mhr', function ($join) {
                            $join->on('mhr.model_id', '=', 'u.id')
                                ->where('mhr.model_type', \App\Models\User::class);
                        })
                        ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                        ->where('r.name', 'driver');
                }

                $recentDrivers = $recentDriverQuery
                    ->whereNotNull('u.name')
                    ->orderByDesc('u.created_at')
                    ->limit(4)
                    ->get()
                    ->map(function ($user) {
                        $name = trim((string) ($user->name ?? ''));
                        $displayName = strtolower($name) === 'john doe'
                            ? 'Bwiser Driver'
                            : ($name !== '' ? $name : 'New driver');
                        $initials = collect(preg_split('/\s+/', $displayName))
                            ->filter()
                            ->take(2)
                            ->map(fn ($part) => strtoupper(mb_substr($part, 0, 1)))
                            ->implode('');

                        return [
                            'name' => $displayName,
                            'initials' => $initials !== '' ? $initials : 'BW',
                        ];
                    })
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                $recentDrivers = [];
            }
        }

        return [
            'totals' => $totals,
            'voucher_growth' => $voucherGrowth,
            'series' => $series,
            'recent_drivers' => $recentDrivers,
            'generated_at' => $now->toIso8601String(),
        ];
    });

    return view('welcome', [
        'welcomeStats' => $payload,
    ]);
});

Route::get('/sitemap.xml', function () {
    $pages = app(SitemapBuilder::class)->build();

    return response()
        ->view('sitemap', ['pages' => $pages, 'lastmod' => now()->toAtomString()])
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/cookies', 'legal.cookies')->name('legal.cookies');
Route::view('/legal/aml-kyc', 'legal.aml')->name('legal.aml');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/legal/poppia', 'legal.poppia')->name('legal.poppia');
Route::view('/legal/paia-manual', 'legal.paia')->name('legal.paia');
Route::view('/legal/security-compliance', 'legal.security')->name('legal.security');

Route::get('/repayments/request/{repayment}', [DriverDashboardController::class, 'publicRepaymentRequest'])
    ->middleware('signed')
    ->name('driver.repayments.request.show');
Route::post('/repayments/request/{repayment}/pay', [DriverDashboardController::class, 'publicRepaymentRequestPay'])
    ->middleware('signed')
    ->name('driver.repayments.request.pay');
Route::get('/repayments/request/paystack/callback', [DriverDashboardController::class, 'publicRepaymentRequestCallback'])
    ->name('driver.repayments.request.callback');

// ========== SETUP & FIX ROUTES (LOCAL ONLY) ==========
if (app()->environment(['local', 'development', 'testing'])) {
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
}

// ========== AUTH ROUTES ==========
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/auth/google/complete', [GoogleAuthController::class, 'showCompleteForm'])->name('auth.google.complete.form');
Route::post('/auth/google/complete', [GoogleAuthController::class, 'completeRegistration'])->name('auth.google.complete.store');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/register', fn () => redirect()->route('register.driver'))->name('register');
Route::get('/register/driver', [RegisterController::class, 'showDriver'])->name('register.driver');
Route::post('/register/driver', [RegisterController::class, 'storeDriver'])->name('register.driver.store');
Route::get('/register/merchant', [RegisterController::class, 'showMerchant'])->name('register.merchant');
Route::post('/register/merchant', [RegisterController::class, 'storeMerchant'])->name('register.merchant.store');
Route::get('/geo/here/geocode', [HereMapsController::class, 'geocode'])
    ->middleware('throttle:120,1')
    ->name('here.geocode');
Route::get('/geo/here/reverse', [HereMapsController::class, 'reverse'])
    ->middleware('throttle:120,1')
    ->name('here.reverse');
Route::get('/geo/google/autocomplete', [GoogleMapsController::class, 'autocomplete'])
    ->middleware('throttle:120,1')
    ->name('google.autocomplete');
Route::get('/geo/google/place', [GoogleMapsController::class, 'place'])
    ->middleware('throttle:120,1')
    ->name('google.place');
Route::get('/geo/google/reverse', [GoogleMapsController::class, 'reverse'])
    ->middleware('throttle:120,1')
    ->name('google.reverse');

Route::middleware('auth')->group(function () {
    Route::get('/registration/complete/{role?}', [RegistrationDocumentsController::class, 'show'])
        ->name('registration.complete');
    Route::post('/registration/documents', [RegistrationDocumentsController::class, 'store'])
        ->name('registration.documents.store');
});

// ========== PROTECTED ROUTES ==========
Route::middleware(['auth'])->group(function () {
    Route::get('/account/delete', [AccountDeletionController::class, 'show'])->name('account.delete.show');
    Route::delete('/account/delete', [AccountDeletionController::class, 'destroy'])->name('account.delete');
    
    // ========== INVESTOR ROUTES ==========
    Route::prefix('investor')->name('investor.')->group(function () {
        Route::get('/dashboard', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'investor']), 403);
            
          
            return app(InvestorDashboardController::class)->index();
        })->name('dashboard');
        
         Route::get('/investments', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'investor']), 403);
            return app(InvestorDashboardController::class)->investments(request());
        })->name('investments');
        
        Route::get('/investments/{investment}', [InvestorDashboardController::class, 'showInvestment'])->name('investments.show');
        
        Route::get('/opportunities', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'investor']), 403);
            return app(InvestorDashboardController::class)->opportunities(request());
        })->name('opportunities');
        
        Route::post('/invest', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'investor']), 403);
            return app(InvestorDashboardController::class)->invest(request());
        })->name('invest');
        
        Route::get('/profile', [InvestorDashboardController::class, 'profile'])->name('profile');
        
        Route::put('/preferences', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'investor']), 403);
            return app(InvestorDashboardController::class)->updatePreferences(request());
        })->name('preferences.update');
        
        Route::get('/statements', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'investor']), 403);
            return app(InvestorDashboardController::class)->statements(request());
        })->name('statements');
    });

    // ========== ADMIN ROUTES ==========
    Route::prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminDashboardController::class)->index();
        })->name('dashboard');

        Route::get('/feedback', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminFeedbackController::class)->index(request());
        })->name('feedback.index');

        Route::get('/repayments/ops', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminRepaymentOpsController::class)->index(app(\App\Services\RepaymentPolicyService::class));
        })->name('repayments.ops');
        Route::post('/repayments/ops/policy', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminRepaymentOpsController::class)->updatePolicy(request(), app(\App\Services\RepaymentPolicyService::class));
        })->name('repayments.ops.policy.update');
        Route::post('/repayments/ops/run-now', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminRepaymentOpsController::class)->runNow();
        })->name('repayments.ops.run-now');
        Route::post('/repayments/ops/default-charges/run-now', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminRepaymentOpsController::class)->runDefaultChargesNow();
        })->name('repayments.ops.default-charges.run-now');

        // ========== SUPPORT INBOX ==========
        Route::get('/support/tickets', function () {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminSupportTicketController::class)->index(request());
        })->name('support.tickets.index');
        Route::get('/support/tickets/{ticket}', function (\App\Models\SupportTicket $ticket) {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminSupportTicketController::class)->show($ticket);
        })->name('support.tickets.show');
        Route::post('/support/tickets/{ticket}/reply', function (\App\Models\SupportTicket $ticket) {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminSupportTicketController::class)->reply(request(), $ticket);
        })->name('support.tickets.reply');
        Route::post('/support/tickets/{ticket}/assign', function (\App\Models\SupportTicket $ticket) {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminSupportTicketController::class)->assign(request(), $ticket);
        })->name('support.tickets.assign');
        
        // ========== USERS ROUTES ==========
        Route::get('/users', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin']), 403);
            return app(AdminUserController::class)->index(request());
        })->name('users.index');
        
        Route::get('/users/create', [AdminUserController::class, 'create'])->middleware('role:super_admin|admin')->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->middleware('role:super_admin|admin')->name('users.store');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->middleware('role:super_admin|admin')->whereNumber('user')->name('users.show');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->middleware('role:super_admin|admin')->whereNumber('user')->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('role:super_admin|admin')->whereNumber('user')->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->middleware('role:super_admin|admin')->whereNumber('user')->name('users.destroy');
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->middleware('role:super_admin|admin')->whereNumber('user')->name('users.toggle-status');
        Route::get('/users/account-approvals', [AdminUserController::class, 'accountApprovals'])
            ->middleware('role:super_admin|admin')
            ->name('users.account-approvals');
        Route::post('/users/account-approvals/{approval}/approve', [AdminUserController::class, 'approveAccount'])
            ->middleware('role:super_admin|admin')
            ->whereNumber('approval')
            ->name('users.account-approvals.approve');
        Route::post('/users/account-approvals/{approval}/reject', [AdminUserController::class, 'rejectAccount'])
            ->middleware('role:super_admin|admin')
            ->whereNumber('approval')
            ->name('users.account-approvals.reject');
        Route::post('/users/{user}/driver-documents/{documentType}/verify', [AdminUserController::class, 'verifyDriverDocument'])
            ->middleware('role:super_admin|admin')
            ->whereNumber('user')
            ->name('users.driver-documents.verify');
        Route::get('/users/registration-documents', [AdminUserController::class, 'registrationDocuments'])
            ->middleware('role:super_admin|admin')
            ->name('users.registration-documents');
        Route::post('/users/{user}/bank-statement/review', [AdminUserController::class, 'reviewBankStatement'])
            ->middleware('role:super_admin|admin')
            ->whereNumber('user')
            ->name('users.bank-statement.review');
        Route::post('/users/{user}/credit-limit', [AdminUserController::class, 'updateCreditLimit'])
            ->middleware('role:super_admin|admin')
            ->whereNumber('user')
            ->name('users.credit-limit.update');
        Route::post('/users/{user}/wallet', [AdminUserController::class, 'updateWallet'])
            ->middleware('role:super_admin|admin')
            ->whereNumber('user')
            ->name('users.wallet.update');
        Route::post('/users/{user}/force-password-reset', [AdminUserController::class, 'forcePasswordReset'])
            ->middleware('role:super_admin|admin')
            ->whereNumber('user')
            ->name('users.force-password-reset');
        
        // ========== SETTINGS ROUTES ==========
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->index();
            })->name('index');
            
            Route::post('/general', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->updateGeneral(request());
            })->name('update-general');
            
            Route::post('/mail', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->updateMail(request());
            })->name('update-mail');
            
            Route::post('/payment', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->updatePayment(request());
            })->name('update-payment');
            
            Route::post('/vouchers', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->updateVouchers(request());
            })->name('update-vouchers');
            
            Route::post('/system', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->updateSystem(request());
            })->name('update-system');

            Route::post('/merchant-dashboard', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->updateMerchantDashboard(request());
            })->name('update-merchant-dashboard');
            
            Route::post('/clear-cache', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->clearCache(request());
            })->name('clear-cache');
            
            Route::post('/backup', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->backupDatabase(request());
            })->name('backup');
            
            Route::get('/logs', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->viewLogs();
            })->name('logs');
            
            Route::post('/clear-logs', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->clearLogs(request());
            })->name('clear-logs');
            
            Route::get('/system-info', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettingsController::class)->systemInfo();
            })->name('system-info');
            
            Route::post('/test-email', function(Request $request) {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                try {
                    Mail::raw('Test email from Bwiser Admin', function ($message) use ($request) {
                        $message->to($request->email)
                                ->subject('Test Email from Bwiser');
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
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
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
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->index(request());
            })->name('index');
            
            Route::get('/create', [SettlementController::class, 'create'])->name('create');
            Route::post('/', [SettlementController::class, 'store'])->name('store');
            Route::post('/quick-topup', [SettlementController::class, 'quickTopup'])->name('quick-topup');
            Route::post('/quick-topup-immediate', [SettlementController::class, 'quickTopupImmediate'])->name('quick-topup-immediate');
            Route::get('/{settlement}', [SettlementController::class, 'show'])->whereNumber('settlement')->name('show');
            Route::get('/{settlement}/edit', [SettlementController::class, 'edit'])->whereNumber('settlement')->name('edit');
            Route::put('/{settlement}', [SettlementController::class, 'update'])->whereNumber('settlement')->name('update');
            Route::delete('/{settlement}', [SettlementController::class, 'destroy'])->whereNumber('settlement')->name('destroy');
            
            Route::post('/{settlement}/process', [SettlementController::class, 'process'])->whereNumber('settlement')->name('process');
            Route::post('/{settlement}/mark-as-failed', [SettlementController::class, 'markAsFailed'])->whereNumber('settlement')->name('mark-as-failed');
            Route::post('/{settlement}/verify-paystack', [SettlementController::class, 'verifyPaystackTransfer'])->whereNumber('settlement')->name('verify-paystack');
            Route::post('/{settlement}/finalize-paystack-otp', [SettlementController::class, 'finalizePaystackOtp'])->whereNumber('settlement')->name('finalize-paystack-otp');
            
            Route::post('/bulk-process', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->bulkProcess(request());
            })->name('bulk-process');

            Route::post('/process-brand', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->processBrand(request());
            })->name('process-brand');

            Route::post('/cycles/brand', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->saveBrandCycle(request());
            })->name('cycles.brand');

            Route::post('/cycles/station', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->saveStationCycle(request());
            })->name('cycles.station');

            Route::post('/cycles/run-due', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->runDueWeeklyCycles();
            })->name('cycles.run-due');

            Route::post('/cycles/toggle', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->toggleWeeklyCycles(request());
            })->name('cycles.toggle');

            Route::post('/stations/{station}/partner', function(\App\Models\FuelStation $station) {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->setPartnerStation(request(), $station);
            })->name('stations.partner');
            
            Route::get('/export', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->export(request());
            })->name('export');
            
            Route::get('/api/pending-amount', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->getPendingAmount(request());
            })->name('api.pending-amount');

            Route::get('/api/stations-search', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->stationSearch(request());
            })->name('api.stations-search');

            Route::get('/api/paystack-health', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->paystackHealth(request());
            })->name('api.paystack-health');

            Route::get('/api/paystack-banks', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->paystackBanks(request());
            })->name('api.paystack-banks');
            
            Route::get('/api/statistics', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(SettlementController::class)->statistics(request());
            })->name('api.statistics');
        });
        
              // ========== VOUCHERS ROUTES ==========
        Route::prefix('vouchers')->name('vouchers.')->group(function () {
            // List all vouchers
            Route::get('/', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(VoucherController::class)->index(request());
            })->name('index');

            // Create voucher
            Route::get('/create', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(VoucherController::class)->create();
            })->name('create');

            Route::post('/', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(VoucherController::class)->store(request());
            })->name('store');
            
            // Pending vouchers
            Route::get('/pending', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
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
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(VoucherController::class)->bulkAction(request());
            })->name('bulk-action');
            
            // Export vouchers
            Route::get('/export', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(VoucherController::class)->export(request());
            })->name('export');
        });
        
        // ========== LEASES ROUTES ==========
        Route::prefix('leases')->name('leases.')->group(function () {
            Route::get('/', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
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
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(LeaseController::class)->export(request());
            })->name('export');
            
            Route::get('/reports/overdue', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(LeaseController::class)->overdueReport(request());
            })->name('reports.overdue');
            
            Route::get('/reports/performance', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(LeaseController::class)->performanceReport(request());
            })->name('reports.performance');
            
            Route::get('/api/stats', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(LeaseController::class)->getStats(request());
            })->name('api.stats');
            
            Route::get('/api/recent', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(LeaseController::class)->getRecent(request());
            })->name('api.recent');
            
            Route::post('/calculate', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
                return app(LeaseController::class)->calculate(request());
            })->name('calculate');
        });
        
        // ========== REPORTS ROUTES ==========
        Route::get('/reports', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminReportController::class)->index(request());
        })->name('reports.index');
        
        Route::get('/reports/financial', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminReportController::class)->financial(request());
        })->name('reports.financial');
        
        Route::get('/reports/risk', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminReportController::class)->risk(request());
        })->name('reports.risk');
        
        Route::get('/reports/export/{type}', function($type) {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(AdminReportController::class)->export(request(), $type);
        })->name('reports.export');
        
        // ========== INVESTORS MANAGEMENT ROUTES ==========
        Route::prefix('investors')->name('investors.')->group(function () {
            Route::get('/', function() {
                abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee', 'investor']), 403);
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
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(EmployeeDashboardController::class)->index();
        })->name('dashboard');
        
        Route::get('/approvals', function() {
            abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee']), 403);
            return app(EmployeeApprovalController::class)->index(request());
        })->name('approvals');
    });
    
    // Merchant
    Route::prefix('merchant')->name('merchant.')->group(function () {
        Route::get('/dashboard', [MerchantDashboardController::class, 'index'])->name('dashboard');
        Route::get('/settings', [MerchantDashboardController::class, 'settings'])->name('settings');
        Route::post('/settings', [MerchantDashboardController::class, 'updateStationSettings'])->name('settings.update');
        Route::post('/settings/fuel-prices', [MerchantDashboardController::class, 'updateFuelPrices'])->name('settings.fuel-prices.update');
        Route::get('/vouchers', [MerchantDashboardController::class, 'vouchers'])->name('vouchers.index');
        Route::get('/vouchers/stream', [MerchantDashboardController::class, 'stream'])->name('vouchers.stream');
        Route::post('/vouchers/redeem', [MerchantDashboardController::class, 'redeem'])->name('vouchers.redeem');
        Route::get('/developer/credentials', [MerchantDashboardController::class, 'developerCredentials'])->name('developer.credentials');
        Route::post('/developer/tokens', [MerchantDashboardController::class, 'storeDeveloperToken'])->name('developer.tokens.store');
        Route::delete('/developer/tokens/{token}', [MerchantDashboardController::class, 'revokeDeveloperToken'])->name('developer.tokens.destroy');
        Route::get('/developer/docs', [MerchantDashboardController::class, 'developerDocs'])->name('developer.docs');
        Route::get('/developer/sandbox', [MerchantDashboardController::class, 'developerSandbox'])->name('developer.sandbox');
    });
    
    // Driver
    Route::prefix('driver')->name('driver.')->group(function () {
        Route::get('/dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');
        Route::get('/vouchers', [DriverDashboardController::class, 'vouchers'])->name('vouchers.index');
        Route::get('/vouchers/create', [DriverDashboardController::class, 'createVoucher'])->name('vouchers.create');
        Route::post('/vouchers', [DriverDashboardController::class, 'storeVoucher'])->name('vouchers.store');
        Route::post('/vouchers/{voucher}/cancel', [DriverDashboardController::class, 'cancelVoucher'])->name('vouchers.cancel');
        Route::get('/virtual-cards', [DriverVirtualCardController::class, 'index'])->name('virtual-cards.index');
        Route::post('/virtual-cards', [DriverVirtualCardController::class, 'store'])->name('virtual-cards.store');
        Route::post('/virtual-cards/{card}/freeze', [DriverVirtualCardController::class, 'freeze'])->name('virtual-cards.freeze');
        Route::post('/virtual-cards/{card}/unfreeze', [DriverVirtualCardController::class, 'unfreeze'])->name('virtual-cards.unfreeze');
        Route::post('/virtual-cards/{card}/close', [DriverVirtualCardController::class, 'close'])->name('virtual-cards.close');
        Route::post('/virtual-cards/{card}/allocate', [DriverVirtualCardController::class, 'allocate'])->name('virtual-cards.allocate');
        Route::post('/virtual-cards/{card}/reveal', [DriverVirtualCardController::class, 'reveal'])->name('virtual-cards.reveal');
        Route::post('/virtual-cards/{card}/convert-to-voucher', [DriverVirtualCardController::class, 'convertToVoucher'])->name('virtual-cards.convert-to-voucher');
        Route::get('/repayments', [DriverDashboardController::class, 'repayments'])->name('repayments.index');
        Route::get('/repayments/upcoming/export-pdf', [DriverDashboardController::class, 'exportUpcomingRepaymentsPdf'])->name('repayments.upcoming.export-pdf');
        Route::post('/repayments/1voucher/week', [DriverRepayment1VoucherController::class, 'payWeek'])->name('repayments.1voucher.week');
        Route::get('/profile', [DriverDashboardController::class, 'profile'])->name('profile');
        Route::get('/bank-statements/upload', [DriverBankStatementController::class, 'create'])->name('bank-statements.create');
        Route::post('/bank-statements', [DriverBankStatementController::class, 'store'])->name('bank-statements.store');
    });

    Route::post('/payments/paystack/repayments/{repayment}', [DriverDashboardController::class, 'payRepayment'])
        ->name('payments.paystack.repayment');
    Route::get('/driver/repayments/{repayment}/pay-now', [DriverDashboardController::class, 'payRepaymentNow'])
        ->name('driver.repayments.pay-now');
    Route::get('/driver/repayments/paystack/callback', [DriverDashboardController::class, 'payRepaymentCallback'])
        ->name('driver.repayments.paystack.callback');
    Route::get('/driver/repayments/{repayment}/payshap', [DriverRepaymentPayShapController::class, 'show'])
        ->name('driver.repayments.payshap.show');
    Route::post('/driver/repayments/{repayment}/payshap/init', [DriverRepaymentPayShapController::class, 'init'])
        ->name('driver.repayments.payshap.init');
    Route::get('/driver/repayments/payshap/return', [DriverRepaymentPayShapController::class, 'handleReturn'])
        ->name('driver.repayments.payshap.return');
    Route::get('/driver/repayments/{repayment}/crypto', [DriverRepaymentCryptoController::class, 'show'])
        ->name('driver.repayments.crypto.show');
    Route::post('/driver/repayments/{repayment}/crypto/confirm', [DriverRepaymentCryptoController::class, 'confirm'])
        ->name('driver.repayments.crypto.confirm');
    Route::post('/driver/repayments/autopay/toggle', [DriverDashboardController::class, 'toggleAutopayDaily'])
        ->name('driver.repayments.autopay.toggle');

    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');
});

// ========== UTILITY ROUTES ==========
Route::get('/clear-cache', function() {
    \Artisan::call('cache:clear');
    \Artisan::call('route:clear');
    \Artisan::call('config:clear');
    \Artisan::call('view:clear');
    
    return "✅ Cache cleared! <a href='/'>Home</a>";
});
