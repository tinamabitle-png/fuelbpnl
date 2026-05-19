<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Services\WelcomePageSettingsService;

class SettingsController extends Controller
{
    /**
     * Display all settings
     */
    public function index()
    {
        $welcomePageService = app(WelcomePageSettingsService::class);

        $settings = [
            'general' => [
                'app_name' => config('app.name', 'Bwiser'),
                'app_url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'maintenance_mode' => app()->isDownForMaintenance(),
            ],
            'mail' => [
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_encryption' => config('mail.mailers.smtp.encryption'),
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
            ],
            'payment' => [
                'currency' => config('app.currency', 'KES'),
                'currency_symbol' => config('app.currency_symbol', 'KSh'),
                'payment_gateway' => config('services.payment.gateway', 'stripe'),
                'enable_mpesa' => config('services.mpesa.enabled', true),
                'enable_bank_transfer' => config('services.bank_transfer.enabled', true),
                'default_payment_method' => config('services.payment.default', 'mpesa'),
            ],
            'vouchers' => [
                'voucher_expiry_days' => config('services.vouchers.expiry_days', 7),
                'auto_approve_vouchers' => config('services.vouchers.auto_approve', false),
                'max_voucher_amount' => config('services.vouchers.max_amount', 50000),
                'min_voucher_amount' => config('services.vouchers.min_amount', 100),
                'min_repayment_amount' => config('credit.min_repayment_amount', 50),
                'merchant_ussd_service_code' => $this->getDbSetting('merchant_ussd_service_code', ''),
            ],
            'system' => [
                'cache_driver' => config('cache.default'),
                'session_driver' => config('session.driver'),
                'queue_driver' => config('queue.default'),
                'debug_mode' => config('app.debug'),
                'maintenance_mode' => app()->isDownForMaintenance(),
            ],
            'merchant_dashboard' => [
                'branding_mode' => $this->getDbSetting('merchant_dashboard_branding_mode', 'brand'),
                'selected_brand' => $this->getDbSetting('merchant_dashboard_selected_brand', ''),
                'logo_path' => $this->getDbSetting('merchant_dashboard_logo_path', ''),
            ],
            'welcome_page' => $welcomePageService->settings(),
        ];

        $popularBrands = collect($this->merchantBrandCatalog())
            ->map(function ($label, $slug) {
                $logoPath = public_path('images/brands/' . $slug . '.png');
                return [
                    'slug' => $slug,
                    'name' => $label,
                    'logo_url' => is_file($logoPath) ? asset('images/brands/' . $slug . '.png') : null,
                ];
            })
            ->values();

        $welcomePageTextSections = $welcomePageService->textSections();
        $welcomePageImageSections = $welcomePageService->imageSections();
        $welcomePageImageUrls = $welcomePageService->imageUrls();

        return view('admin.settings.index', compact(
            'settings',
            'popularBrands',
            'welcomePageTextSections',
            'welcomePageImageSections',
            'welcomePageImageUrls'
        ));
    }

    /**
     * Update general settings
     */
    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|timezone',
            'locale' => 'required|string|size:2',
            'currency' => 'required|string|size:3',
            'currency_symbol' => 'required|string|max:10',
        ]);

        // Update .env or database settings
        $this->updateEnvironmentValue('APP_NAME', $validated['app_name']);
        $this->updateEnvironmentValue('APP_URL', $validated['app_url']);
        $this->updateEnvironmentValue('APP_TIMEZONE', $validated['timezone']);
        $this->updateEnvironmentValue('APP_LOCALE', $validated['locale']);
        $this->updateEnvironmentValue('APP_CURRENCY', $validated['currency']);
        $this->updateEnvironmentValue('APP_CURRENCY_SYMBOL', $validated['currency_symbol']);

        Cache::flush();
        Artisan::call('config:clear');

        return back()->with('success', 'General settings updated successfully!');
    }

    /**
     * Update mail settings
     */
    public function updateMail(Request $request)
    {
        $validated = $request->validate([
            'mail_driver' => 'required|in:smtp,sendmail,mailgun,ses',
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        $this->updateEnvironmentValue('MAIL_MAILER', $validated['mail_driver']);
        $this->updateEnvironmentValue('MAIL_HOST', $validated['mail_host']);
        $this->updateEnvironmentValue('MAIL_PORT', $validated['mail_port']);
        $this->updateEnvironmentValue('MAIL_USERNAME', $validated['mail_username']);
        if ($request->filled('mail_password')) {
            $this->updateEnvironmentValue('MAIL_PASSWORD', $validated['mail_password']);
        }
        $this->updateEnvironmentValue('MAIL_ENCRYPTION', $validated['mail_encryption']);
        $this->updateEnvironmentValue('MAIL_FROM_ADDRESS', $validated['mail_from_address']);
        $this->updateEnvironmentValue('MAIL_FROM_NAME', $validated['mail_from_name']);

        Cache::flush();
        Artisan::call('config:clear');

        return back()->with('success', 'Mail settings updated successfully!');
    }

    /**
     * Update payment settings
     */
    public function updatePayment(Request $request)
    {
        $validated = $request->validate([
            'payment_gateway' => 'required|in:stripe,paypal,flutterwave',
            'enable_mpesa' => 'boolean',
            'enable_bank_transfer' => 'boolean',
            'default_payment_method' => 'required|in:mpesa,bank_transfer,card',
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'mpesa_consumer_key' => 'nullable|string',
            'mpesa_consumer_secret' => 'nullable|string',
        ]);

        $this->updateEnvironmentValue('PAYMENT_GATEWAY', $validated['payment_gateway']);
        $this->updateEnvironmentValue('MPESA_ENABLED', $validated['enable_mpesa'] ? 'true' : 'false');
        $this->updateEnvironmentValue('BANK_TRANSFER_ENABLED', $validated['enable_bank_transfer'] ? 'true' : 'false');
        $this->updateEnvironmentValue('DEFAULT_PAYMENT_METHOD', $validated['default_payment_method']);
        
        if ($request->filled('stripe_key')) {
            $this->updateEnvironmentValue('STRIPE_KEY', $validated['stripe_key']);
        }
        if ($request->filled('stripe_secret')) {
            $this->updateEnvironmentValue('STRIPE_SECRET', $validated['stripe_secret']);
        }
        if ($request->filled('mpesa_consumer_key')) {
            $this->updateEnvironmentValue('MPESA_CONSUMER_KEY', $validated['mpesa_consumer_key']);
        }
        if ($request->filled('mpesa_consumer_secret')) {
            $this->updateEnvironmentValue('MPESA_CONSUMER_SECRET', $validated['mpesa_consumer_secret']);
        }

        Cache::flush();

        return back()->with('success', 'Payment settings updated successfully!');
    }

    /**
     * Update voucher settings
     */
    public function updateVouchers(Request $request)
    {
        $validated = $request->validate([
            'voucher_expiry_days' => 'required|integer|min:1|max:30',
            'auto_approve_vouchers' => 'boolean',
            'max_voucher_amount' => 'required|numeric|min:100',
            'min_voucher_amount' => 'required|numeric|min:10',
            'min_repayment_amount' => 'required|numeric|min:1',
            'require_approval_threshold' => 'nullable|numeric|min:0',
            'merchant_ussd_service_code' => ['nullable', 'string', 'max:32', 'regex:/^[*#0-9]+$/'],
        ]);

        $this->updateEnvironmentValue('VOUCHER_EXPIRY_DAYS', $validated['voucher_expiry_days']);
        $this->updateEnvironmentValue('AUTO_APPROVE_VOUCHERS', $validated['auto_approve_vouchers'] ? 'true' : 'false');
        $this->updateEnvironmentValue('MAX_VOUCHER_AMOUNT', $validated['max_voucher_amount']);
        $this->updateEnvironmentValue('MIN_VOUCHER_AMOUNT', $validated['min_voucher_amount']);
        $this->updateEnvironmentValue('MIN_REPAYMENT_AMOUNT', $validated['min_repayment_amount']);
        $this->upsertDbSetting(
            'merchant_ussd_service_code',
            trim((string) ($validated['merchant_ussd_service_code'] ?? '')),
            'vouchers'
        );
        
        if ($request->filled('require_approval_threshold')) {
            $this->updateEnvironmentValue('REQUIRE_APPROVAL_THRESHOLD', $validated['require_approval_threshold']);
        }

        Cache::flush();

        return back()->with('success', 'Voucher settings updated successfully!');
    }

    /**
     * Update system settings
     */
    public function updateSystem(Request $request)
    {
        $validated = $request->validate([
            'cache_driver' => 'required|in:file,redis,database',
            'session_driver' => 'required|in:file,cookie,database,redis',
            'debug_mode' => 'boolean',
            'maintenance_mode' => 'boolean',
        ]);

        if ($validated['maintenance_mode']) {
            Artisan::call('down');
        } else {
            Artisan::call('up');
        }

        $this->updateEnvironmentValue('CACHE_DRIVER', $validated['cache_driver']);
        $this->updateEnvironmentValue('SESSION_DRIVER', $validated['session_driver']);
        $this->updateEnvironmentValue('APP_DEBUG', $validated['debug_mode'] ? 'true' : 'false');

        Cache::flush();
        Artisan::call('config:clear');

        return back()->with('success', 'System settings updated successfully!');
    }

    public function updateMerchantDashboard(Request $request)
    {
        $validated = $request->validate([
            'branding_mode' => 'required|in:brand,upload',
            'selected_brand' => ['nullable', 'string', Rule::in(array_keys($this->merchantBrandCatalog()))],
            'logo_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:5120',
            'remove_logo' => 'nullable|boolean',
        ]);

        $brandingMode = (string) $validated['branding_mode'];
        $selectedBrand = trim((string) ($validated['selected_brand'] ?? ''));
        $currentLogoPath = (string) $this->getDbSetting('merchant_dashboard_logo_path', '');
        $logoPath = $currentLogoPath;

        if ($request->boolean('remove_logo') && $currentLogoPath !== '') {
            Storage::disk('public')->delete($currentLogoPath);
            $logoPath = '';
        }

        if ($request->hasFile('logo_file')) {
            if ($currentLogoPath !== '') {
                Storage::disk('public')->delete($currentLogoPath);
            }
            $logoPath = $request->file('logo_file')->store('merchant_branding', 'public');
        }

        if ($brandingMode === 'upload' && $logoPath === '') {
            return back()->with('error', 'Upload a logo file or switch to brand mode.');
        }

        if ($brandingMode === 'brand' && $selectedBrand === '') {
            return back()->with('error', 'Select a fuel brand for brand mode.');
        }

        $this->upsertDbSetting('merchant_dashboard_branding_mode', $brandingMode, 'merchant_dashboard');
        $this->upsertDbSetting('merchant_dashboard_selected_brand', $selectedBrand, 'merchant_dashboard');
        $this->upsertDbSetting('merchant_dashboard_logo_path', $logoPath, 'merchant_dashboard');

        return back()->with('success', 'Merchant dashboard branding updated successfully.');
    }

    public function updateWelcomePage(Request $request, WelcomePageSettingsService $welcomePageService)
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        $validated = $request->validate($welcomePageService->validationRules());
        $welcomePageService->update($request, $validated);

        return redirect()
            ->route('admin.settings.index', ['tab' => 'welcomePage'])
            ->with('success', 'Welcome page settings updated successfully.');
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        
        return back()->with('success', 'Application cache cleared successfully!');
    }

    /**
     * Backup database
     */
    public function backupDatabase()
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            
            // Get the latest backup file
            $backupPath = storage_path('app/backups/');
            $files = glob($backupPath . '*.zip');
            
            if (count($files) > 0) {
                $latestBackup = $files[count($files) - 1];
                $filename = basename($latestBackup);
                
                return response()->download($latestBackup, $filename);
            }
            
            return back()->with('error', 'No backup files found.');
        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * View system logs
     */
    public function viewLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $logs = array_slice(explode("\n", $logs), -100); // Get last 100 lines
            $logs = implode("\n", array_reverse($logs));
        } else {
            $logs = 'No log file found.';
        }
        
        return view('admin.settings.logs', compact('logs'));
    }

    /**
     * Clear system logs
     */
    public function clearLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        
        return back()->with('success', 'System logs cleared successfully!');
    }

    /**
     * View system information
     */
    public function systemInfo()
    {
        $info = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'server_os' => php_uname(),
            'database_driver' => config('database.default'),
            'database_name' => config('database.connections.mysql.database'),
            'timezone' => config('app.timezone'),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_driver' => config('queue.default'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
        
        return view('admin.settings.system-info', compact('info'));
    }

    /**
     * Helper method to update environment values
     */
    private function updateEnvironmentValue($key, $value)
    {
        $envFile = app()->environmentFilePath();
        $env = file_get_contents($envFile);
        
        // Escape special characters in value
        $escapedValue = preg_replace('/\s+/', ' ', $value);
        $escapedValue = str_replace('"', '\"', $escapedValue);
        
        // Update or add the key
        if (strpos($env, $key) !== false) {
            // Replace existing key
            $env = preg_replace("/^{$key}=.*/m", "{$key}=\"{$escapedValue}\"", $env);
        } else {
            // Add new key
            $env .= "\n{$key}=\"{$escapedValue}\"";
        }
        
        file_put_contents($envFile, $env);
    }

    private function getDbSetting(string $key, string $default = ''): string
    {
        $value = DB::table('settings')->where('key', $key)->value('value');
        return $value === null ? $default : (string) $value;
    }

    private function upsertDbSetting(string $key, string $value, string $category = 'system'): void
    {
        $payload = ['value' => $value];
        if (Schema::hasColumn('settings', 'category')) {
            $payload['category'] = $category;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            $payload
        );
    }

    private function merchantBrandCatalog(): array
    {
        return [
            'astron-energy' => 'Astron Energy',
            'bp-southern-africa' => 'BP Southern Africa',
            'central-energy-fund' => 'Central Energy Fund',
            'engen' => 'Engen',
            'eskom' => 'Eskom',
            'mulilo' => 'Mulilo',
            'petrosa' => 'PetroSA',
            'puma-energy' => 'Puma Energy',
            'sasol' => 'Sasol',
            'shell-sa' => 'Shell SA',
            'totalenergies' => 'TotalEnergies',
            'vivo-energy' => 'Vivo Energy',
        ];
    }
}
