<?php
// [file name]: database/seeders/PermissionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Creating ALL permissions...');
        
        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Common permissions pattern for your system
        $permissionPatterns = [
            // User related
            'view_user', 'create_user', 'edit_user', 'delete_user', 'suspend_user', 'activate_user',
            'view_user_wallet', 'manage_user_wallet', 'view_user_profile',
            
            // Investor related  
            'view_investor', 'create_investor', 'edit_investor', 'delete_investor', 'approve_investor',
            
            // Lease related
            'view_lease', 'create_lease', 'edit_lease', 'delete_lease', 'approve_lease',
            
            // Investment related
            'view_investment', 'create_investment', 'edit_investment', 'delete_investment',
            
            // Voucher related
            'view_voucher', 'create_voucher', 'edit_voucher', 'delete_voucher', 'approve_voucher',
            
            // Station related
            'view_station', 'create_station', 'edit_station', 'delete_station',
            
            // Settlement related
            'view_settlement', 'create_settlement', 'edit_settlement', 'delete_settlement', 'process_settlement',
            
            // Wallet related
            'view_wallet', 'manage_wallet',
            
            // Credit related
            'view_credit', 'edit_credit',
            
            // Report related
            'view_report', 'generate_report', 'export_report',
            
            // System related
            'manage_settings', 'view_logs', 'manage_permissions',
            
            // Dashboard access
            'access_dashboard', 'access_admin', 'access_employee', 'access_investor', 'access_merchant', 'access_driver',
        ];

        // Create all permissions
        foreach ($permissionPatterns as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
            
            // Also create plural versions
            Permission::firstOrCreate([
                'name' => $permission . 's',
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('✅ Created all permissions successfully!');
    }
}