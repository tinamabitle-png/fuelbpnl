<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "�� Updating permissions and roles...\n\n";

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Investor;

// 1. Check what permissions exist
$existingPermissions = Permission::pluck('name')->toArray();
echo "📋 Existing permissions (" . count($existingPermissions) . "):\n";
print_r($existingPermissions);

// 2. Create only missing permissions
$requiredPermissions = [
    'view_user_wallet',
    'suspend_users',
    'access_investor_dashboard',
    'view_investment_opportunities',
    'make_investments',
];

$created = 0;
foreach ($requiredPermissions as $permission) {
    if (!in_array($permission, $existingPermissions)) {
        Permission::create([
            'name' => $permission,
            'guard_name' => 'web'
        ]);
        echo "✅ Created permission: $permission\n";
        $created++;
    } else {
        echo "ℹ️ Permission already exists: $permission\n";
    }
}

echo "\n✅ Created $created new permissions\n\n";

// 3. Ensure investor role exists
$investorRole = Role::firstOrCreate(
    ['name' => 'investor'],
    [
        'guard_name' => 'web',
        'description' => 'Corporate investor'
    ]
);
echo "✅ Investor role: " . ($investorRole->wasRecentlyCreated ? 'CREATED' : 'EXISTS') . "\n";

// 4. Assign permissions to investor role
$investorPermissions = [
    'access_investor_dashboard',
    'view_investment_opportunities',
    'make_investments',
];

// Get permission IDs
$permissionIds = Permission::whereIn('name', $investorPermissions)->pluck('id')->toArray();

// Sync permissions (replace existing ones)
$investorRole->syncPermissions($permissionIds);
echo "✅ Assigned " . count($permissionIds) . " permissions to investor role\n";

// 5. Assign investor role to all investor users
$investors = Investor::with('user')->get();
$assigned = 0;

foreach ($investors as $investor) {
    if ($investor->user) {
        // Ensure user has investor role
        if (!$investor->user->hasRole('investor')) {
            $investor->user->assignRole('investor');
            $assigned++;
        }
    }
}

echo "✅ Updated roles for $assigned investors\n\n";

// 6. Test
$testInvestor = Investor::where('contact_email', 'investments@shell.co.ke')->first();
if ($testInvestor && $testInvestor->user) {
    echo "🎉 TEST INVESTOR STATUS:\n";
    echo "   Company: " . $testInvestor->company_name . "\n";
    echo "   Email: " . $testInvestor->contact_email . "\n";
    echo "   Has investor role: " . ($testInvestor->user->hasRole('investor') ? "✅ YES" : "❌ NO") . "\n";
    echo "   Can access_investor_dashboard: " . ($testInvestor->user->can('access_investor_dashboard') ? "✅ YES" : "❌ NO") . "\n";
    echo "\n   Login with: " . $testInvestor->contact_email . " / Password123!\n";
}

echo "\n✅ Update complete!\n";
