<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Check if necessary tables exist
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions')) {
            $this->command->warn('Roles or Permissions table does not exist. Please run migrations first.');
            return;
        }
        
        // Create roles (without description since column doesn't exist)
        $roles = [
            'super_admin',
            'admin', 
            'employee',
            'merchant',
            'driver',
            'investor',
            'investor_manager',
            'auditor',
        ];
        
        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web']
            );
        }
        
        $this->command->info('Roles created/updated successfully.');
        
        // First, ensure ALL permissions mentioned below exist
        $this->ensurePermissionsExist();
        
        // ===== SUPER ADMIN ROLE =====
        $superAdmin = Role::where('name', 'super_admin')->first();
        // Give super admin all permissions that exist
        $allPermissions = Permission::pluck('name')->toArray();
        $superAdmin->syncPermissions($allPermissions);
        
        // ===== ADMIN ROLE =====
        $admin = Role::where('name', 'admin')->first();
        $adminPermissions = $this->filterExistingPermissions([
            // User permissions
            'view_users', 'create_users', 'edit_users', 'suspend_users', 'view_user_wallet', 'manage_user_credit',
            
            // Wallet permissions
            'view_wallet', 'manage_wallet', 'process_refunds',
            
            // Voucher permissions
            'view_vouchers', 'create_vouchers', 'approve_vouchers', 'reject_vouchers', 'cancel_vouchers', 'export_vouchers',
            
            // Station permissions
            'view_stations', 'create_stations', 'edit_stations', 'delete_stations', 'manage_station_wallet',
            
            // Lease permissions
            'view_leases', 'create_leases', 'edit_leases', 'mark_default', 'extend_lease', 'export_leases', 
            'process_investor_returns', 'view_lease_investors',
            
            // Repayment permissions
            'view_repayments', 'process_repayments', 'waive_repayments',
            
            // Settlement permissions
            'view_settlements', 'process_settlements', 'approve_settlements', 'export_settlements',
            
            // Report permissions
            'view_reports', 'generate_reports', 'export_reports',
            
            // System permissions
            'view_audit_logs', 'manage_settings', 'backup_database', 'view_system_logs',
            
            // Investor Management
            'view_investors', 'create_investors', 'edit_investors', 'verify_investors', 'suspend_investors',
            'manage_investor_capital', 'view_investor_documents', 'verify_investor_documents', 'upload_investor_documents',
            
            // Investment Portfolio
            'view_all_investments', 'view_investments', 'create_investments', 'edit_investments', 'cancel_investments',
            'process_investment_returns', 'export_investments',
            
            // Investment Opportunities
            'view_investment_opportunities', 'manage_investment_opportunities',
            
            // Investor Reports
            'view_investor_reports', 'generate_investor_reports', 'export_investor_reports',
        ]);
        $admin->syncPermissions($adminPermissions);
        
        // ===== EMPLOYEE ROLE =====
        $employee = Role::where('name', 'employee')->first();
        $employeePermissions = $this->filterExistingPermissions([
            'view_users',
            'view_vouchers',
            'approve_vouchers',
            'reject_vouchers',
            'view_stations',
            'view_leases',
            'view_repayments',
            'process_repayments',
            'view_settlements',
            'view_reports',
        ]);
        $employee->syncPermissions($employeePermissions);
        
        // ===== MERCHANT ROLE =====
        $merchant = Role::where('name', 'merchant')->first();
        $merchantPermissions = $this->filterExistingPermissions([
            'view_stations',
            'edit_stations',
            'view_vouchers',
            'redeem_vouchers',
            'view_settlements',
            'view_wallet',
        ]);
        $merchant->syncPermissions($merchantPermissions);
        
        // ===== DRIVER ROLE =====
        $driver = Role::where('name', 'driver')->first();
        $driverPermissions = $this->filterExistingPermissions([
            'view_vouchers',
            'create_vouchers',
            'cancel_vouchers',
            'view_leases',
            'view_repayments',
            'view_wallet',
        ]);
        $driver->syncPermissions($driverPermissions);
        
        // ===== INVESTOR ROLE =====
        $investor = Role::where('name', 'investor')->first();
        $investorPermissions = $this->filterExistingPermissions([
            // Investor Dashboard
            'view_investor_dashboard',
            'view_my_investments',
            'view_my_investment_returns',
            'make_investments',
            'cancel_my_investments',
            'view_my_statements',
            'export_my_investments',
            
            // Investor Profile
            'view_investor_profile',
            'edit_investor_profile',
            'update_investor_preferences',
            'view_investor_analytics',
            
            // Investment Opportunities
            'view_investment_opportunities',
            
            // Leases (view only funded ones)
            'view_leases',
            'export_leases',
            
            // Reports (investor-specific)
            'view_investor_reports',
            'export_investor_reports',
        ]);
        $investor->syncPermissions($investorPermissions);

        // ===== INVESTOR MANAGER ROLE =====
        $investorManager = Role::where('name', 'investor_manager')->first();
        $investorManagerPermissions = $this->filterExistingPermissions([
            // Investor Management
            'view_investors',
            'create_investors',
            'edit_investors',
            'verify_investors',
            'view_investor_documents',
            'verify_investor_documents',
            'upload_investor_documents',
            
            // Investment Portfolio
            'view_all_investments',
            'view_investments',
            'create_investments',
            'edit_investments',
            'cancel_investments',
            'process_investment_returns',
            'export_investments',
            
            // Investment Opportunities
            'view_investment_opportunities',
            'manage_investment_opportunities',
            
            // Reports
            'view_investor_reports',
            'generate_investor_reports',
            'export_investor_reports',
            
            // View other data
            'view_leases',
            'view_repayments',
            'view_settlements',
            'view_reports',
        ]);
        $investorManager->syncPermissions($investorManagerPermissions);
        
        // ===== AUDITOR ROLE =====
        $auditor = Role::where('name', 'auditor')->first();
        $auditorPermissions = $this->filterExistingPermissions([
            'view_audit_logs',
            'view_reports',
            'view_investor_reports',
            'view_leases',
            'view_repayments',
            'view_settlements',
            'view_investments',
            'view_investors',
            'view_users',
        ]);
        $auditor->syncPermissions($auditorPermissions);
        
        $this->command->info('Permissions assigned to roles successfully.');
        
        // Display summary
        $this->displayRoleSummary();
    }
    
    /**
     * Ensure all permissions mentioned in the seeder exist
     */
    private function ensurePermissionsExist(): void
    {
        $allPermissions = [
            // User permissions
            'view_users', 'create_users', 'edit_users', 'suspend_users', 'view_user_wallet', 'manage_user_credit',
            
            // Wallet permissions
            'view_wallet', 'manage_wallet', 'process_refunds',
            
            // Voucher permissions
            'view_vouchers', 'create_vouchers', 'approve_vouchers', 'reject_vouchers', 'cancel_vouchers', 'export_vouchers',
            
            // Station permissions
            'view_stations', 'create_stations', 'edit_stations', 'delete_stations', 'manage_station_wallet',
            
            // Lease permissions
            'view_leases', 'create_leases', 'edit_leases', 'mark_default', 'extend_lease', 'export_leases', 
            'process_investor_returns', 'view_lease_investors',
            
            // Repayment permissions
            'view_repayments', 'process_repayments', 'waive_repayments',
            
            // Settlement permissions
            'view_settlements', 'process_settlements', 'approve_settlements', 'export_settlements',
            
            // Report permissions
            'view_reports', 'generate_reports', 'export_reports',
            
            // System permissions
            'view_audit_logs', 'manage_settings', 'backup_database', 'view_system_logs',
            
            // Investor Management
            'view_investors', 'create_investors', 'edit_investors', 'verify_investors', 'suspend_investors',
            'manage_investor_capital', 'view_investor_documents', 'verify_investor_documents', 'upload_investor_documents',
            
            // Investment Portfolio
            'view_all_investments', 'view_investments', 'create_investments', 'edit_investments', 'cancel_investments',
            'process_investment_returns', 'export_investments',
            
            // Investment Opportunities
            'view_investment_opportunities', 'manage_investment_opportunities',
            
            // Investor Reports
            'view_investor_reports', 'generate_investor_reports', 'export_investor_reports',
            
            // Investor-specific permissions
            'view_investor_dashboard', 'view_my_investments', 'view_my_investment_returns', 'make_investments',
            'cancel_my_investments', 'view_my_statements', 'export_my_investments',
            'view_investor_profile', 'edit_investor_profile', 'update_investor_preferences', 'view_investor_analytics',
            'redeem_vouchers', 'view_my_statements',
        ];
        
        $created = 0;
        foreach ($allPermissions as $permission) {
            $existing = Permission::where('name', $permission)->first();
            if (!$existing) {
                Permission::create([
                    'name' => $permission,
                    'guard_name' => 'web'
                ]);
                $created++;
                $this->command->info("Created permission: {$permission}");
            }
        }
        
        if ($created > 0) {
            $this->command->info("✅ Created {$created} new permissions.");
        } else {
            $this->command->info("ℹ️ All permissions already exist.");
        }
    }
    
    /**
     * Filter permissions array to only include ones that exist
     */
    private function filterExistingPermissions(array $permissions): array
    {
        $existingPermissions = Permission::whereIn('name', $permissions)
            ->pluck('name')
            ->toArray();
            
        $missing = array_diff($permissions, $existingPermissions);
        
        if (!empty($missing)) {
            $this->command->warn("Missing permissions: " . implode(', ', $missing));
        }
        
        return $existingPermissions;
    }
    
    /**
     * Display summary of roles and their permissions
     */
    private function displayRoleSummary(): void
    {
        $this->command->info("\n=== ROLE PERMISSION SUMMARY ===");
        
        $roles = Role::with('permissions')->get();
        
        foreach ($roles as $role) {
            $this->command->info("\n{$role->name}:");
            $this->command->info("  Permissions: " . $role->permissions->count());
            
            // Show key permissions for investor role
            if ($role->name === 'investor') {
                $investorPerms = $role->permissions->pluck('name')->filter(function($perm) {
                    return str_contains($perm, 'investor') || str_contains($perm, 'investment');
                })->toArray();
                
                if (!empty($investorPerms)) {
                    $this->command->info("  Key Investor Permissions:");
                    foreach ($investorPerms as $perm) {
                        $this->command->info("    - {$perm}");
                    }
                }
                
                // Check if investor has access_investor_dashboard
                $hasDashboard = $role->permissions->contains('name', 'view_investor_dashboard');
                $this->command->info("  Has view_investor_dashboard: " . ($hasDashboard ? '✅ YES' : '❌ NO'));
            }
        }
        
        // Test investor user
        try {
            $investor = \App\Models\Investor::with('user')->first();
            if ($investor && $investor->user) {
                $this->command->info("\n=== TEST INVESTOR ===");
                $this->command->info("Company: {$investor->company_name}");
                $this->command->info("Email: {$investor->contact_email}");
                $this->command->info("Has investor role: " . ($investor->user->hasRole('investor') ? '✅ YES' : '❌ NO'));
                $this->command->info("Can view_investor_dashboard: " . ($investor->user->can('view_investor_dashboard') ? '✅ YES' : '❌ NO'));
                $this->command->info("Login with: {$investor->contact_email} / Password123!");
            }
        } catch (\Exception $e) {
            // Ignore if Investor model doesn't exist yet
        }
    }
}
