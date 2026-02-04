<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 Fixing permissions and roles...\n\n";

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Investor;

// 1. Clear existing data
echo "Clearing existing permissions and roles...\n";
Permission::query()->delete();
Role::query()->delete();
DB::table('model_has_permissions')->delete();
DB::table('model_has_roles')->delete();
DB::table('role_has_permissions')->delete();
echo "✅ Cleared existing data\n\n";

// 2. Create ALL permissions from your RoleSeeder
echo "Creating permissions...\n";
$permissions = [
    // User permissions
    'view_users', 'create_users', 'edit_users', 'delete_users', 'suspend_users',
    'activate_users', 'manage_users', 'impersonate_users', 'view_user_profile',
    'view_user_wallet', 'manage_user_wallet',
    
    // Investor permissions
    'view_investors', 'create_investors', 'edit_investors', 'delete_investors',
    'approve_investors', 'suspend_investors', 'manage_investors',
    
    // Lease permissions
    'view_leases', 'create_leases', 'edit_leases', 'delete_leases',
    'approve_leases', 'cancel_leases', 'extend_leases', 'manage_leases',
    
    // Voucher permissions
    'view_vouchers', 'create_vouchers', 'edit_vouchers', 'delete_vouchers',
    'approve_vouchers', 'cancel_vouchers', 'redeem_vouchers',
    
    // Station permissions
    'view_stations', 'create_stations', 'edit_stations', 'delete_stations',
    'approve_stations', 'suspend_stations',
    
    // Settlement permissions
    'view_settlements', 'create_settlements', 'edit_settlements', 'delete_settlements',
    'process_settlements', 'approve_settlements',
    
    // Wallet permissions
    'view_wallets', 'manage_wallets',
    
    // Credit permissions
    'view_credit_limits', 'edit_credit_limits',
    
    // Report permissions
    'view_reports', 'generate_reports', 'export_reports',
    
    // System permissions
    'manage_settings', 'view_logs', 'manage_permissions', 'backup_database',
    
    // Dashboard access
    'access_admin_dashboard', 'access_employee_dashboard', 'access_investor_dashboard',
    'access_merchant_dashboard', 'access_driver_dashboard',
    
    // Investor specific
    'view_investment_opportunities', 'make_investments', 'view_investment_portfolio',
];

foreach ($permissions as $permission) {
    Permission::create([
        'name' => $permission,
        'guard_name' => 'web'
    ]);
}

echo "✅ Created " . count($permissions) . " permissions\n\n";

// 3. Create roles
echo "Creating roles...\n";

// Super Admin - All permissions
$superAdmin = Role::create([
    'name' => 'super_admin',
    'guard_name' => 'web',
    'description' => 'Full system access'
]);
$superAdmin->syncPermissions($permissions);
echo "✅ Created super_admin role\n";

// Admin - Most permissions (remove system level ones)
$adminPermissions = array_filter($permissions, function($perm) {
    return !in_array($perm, ['manage_permissions', 'backup_database']);
});
$admin = Role::create([
    'name' => 'admin',
    'guard_name' => 'web',
    'description' => 'Administrative access'
]);
$admin->syncPermissions($adminPermissions);
echo "✅ Created admin role\n";

// Investor
$investorPermissions = [
    'access_investor_dashboard',
    'view_investment_opportunities', 'make_investments', 'view_investment_portfolio',
];
$investorRole = Role::create([
    'name' => 'investor',
    'guard_name' => 'web',
    'description' => 'Corporate investor'
]);
$investorRole->syncPermissions($investorPermissions);
echo "✅ Created investor role\n";

// Create other roles as needed
$employee = Role::create(['name' => 'employee', 'guard_name' => 'web', 'description' => 'Staff member']);
$merchant = Role::create(['name' => 'merchant', 'guard_name' => 'web', 'description' => 'Fuel station owner']);
$driver = Role::create(['name' => 'driver', 'guard_name' => 'web', 'description' => 'Driver']);

echo "✅ Created all roles\n\n";

// 4. Create super admin user
$superAdminUser = User::firstOrCreate(
    ['email' => 'superadmin@fuelbnpl.com'],
    [
        'name' => 'Super Admin',
        'phone' => '+254700000000',
        'password' => bcrypt('Admin123!'),
        'status' => 'active',
    ]
);
$superAdminUser->assignRole('super_admin');
echo "✅ Created super admin: superadmin@fuelbnpl.com / Admin123!\n\n";

// 5. Assign investor role to all investors
$investors = Investor::with('user')->get();
foreach ($investors as $investor) {
    if ($investor->user) {
        $investor->user->assignRole('investor');
    }
}
echo "✅ Assigned investor role to " . $investors->count() . " investors\n\n";

// 6. Test
$testInvestor = Investor::where('contact_email', 'investments@shell.co.ke')->first();
if ($testInvestor && $testInvestor->user) {
    echo "🎉 TEST INVESTOR READY:\n";
    echo "   Company: " . $testInvestor->company_name . "\n";
    echo "   Email: " . $testInvestor->contact_email . "\n";
    echo "   Password: Password123!\n";
    echo "   Has investor role: " . ($testInvestor->user->hasRole('investor') ? "✅ YES" : "❌ NO") . "\n";
    echo "   Can access_investor_dashboard: " . ($testInvestor->user->can('access_investor_dashboard') ? "✅ YES" : "❌ NO") . "\n";
}

echo "\n✅ Fix completed! You can now run db:seed without errors.\n";
