<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Investor;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure roles exist first - ADD 'investor' to the array
        $roles = ['super_admin', 'admin', 'investor_manager', 'employee', 'auditor', 'investor']; // Added 'investor'
        
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName], [
                'guard_name' => 'web',
                'description' => $this->getRoleDescription($roleName)
            ]);
        }

        // Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@fuelcredit.com'],
            [
                'name' => 'System Administrator',
                'phone' => '+254700000001',
                'password' => Hash::make('Admin123!'),
                'email_verified_at' => now(),
                'status' => 'active',
                'credit_score' => 850,
            ]
        );
        $superAdmin->assignRole('super_admin');

        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@fuelcredit.com'],
            [
                'name' => 'Administrator',
                'phone' => '+254700000002',
                'password' => Hash::make('Admin123!'),
                'email_verified_at' => now(),
                'status' => 'active',
                'credit_score' => 800,
            ]
        );
        $admin->assignRole('admin');

        // Investor Manager
        $investorManager = User::updateOrCreate(
            ['email' => 'investor.manager@fuelcredit.com'],
            [
                'name' => 'Investor Relations Manager',
                'phone' => '+254700000003',
                'password' => Hash::make('Investor123!'),
                'email_verified_at' => now(),
                'status' => 'active',
                'credit_score' => 750,
            ]
        );
        $investorManager->assignRole('investor_manager');

        // Employee
        $employee = User::updateOrCreate(
            ['email' => 'employee@fuelcredit.com'],
            [
                'name' => 'Support Staff',
                'phone' => '+254700000004',
                'password' => Hash::make('Staff123!'),
                'email_verified_at' => now(),
                'status' => 'active',
                'credit_score' => 700,
            ]
        );
        $employee->assignRole('employee');

        // Auditor
        $auditor = User::updateOrCreate(
            ['email' => 'auditor@fuelcredit.com'],
            [
                'name' => 'System Auditor',
                'phone' => '+254700000005',
                'password' => Hash::make('Audit123!'),
                'email_verified_at' => now(),
                'status' => 'active',
                'credit_score' => 750,
            ]
        );
        $auditor->assignRole('auditor');

        // Investor User - ADD THIS USER
        $investorUser = User::updateOrCreate(
            ['email' => 'investor@fuelcredit.com'],
            [
                'name' => 'Test Investor',
                'phone' => '+254700000006',
                'password' => Hash::make('Investor123!'),
                'email_verified_at' => now(),
                'status' => 'active',
                'credit_score' => 750,
            ]
        );
        $investorUser->assignRole('investor');

        // Also create Investor profile for this user:
        Investor::updateOrCreate(
            ['user_id' => $investorUser->id],
            [
                'company_name' => 'Test Investor Corp',
                'registration_number' => 'INV-001',
                'tax_id' => 'P001234567K',
                'contact_person' => 'Test Investor',
                'contact_email' => 'investor@fuelcredit.com',
                'contact_phone' => '+254700000006',
                'company_address' => 'Nairobi, Kenya',
                'city' => 'Nairobi',
                'country' => 'Kenya',
                'total_investment_capital' => 10000000,
                'available_capital' => 5000000,
                'invested_capital' => 0,
                'interest_earned' => 0,
                'risk_profile' => 'moderate',
                'minimum_investment_amount' => 100000,
                'maximum_investment_amount' => 1000000,
                'preferred_interest_rate_min' => 12.00,
                'preferred_interest_rate_max' => 18.00,
                'investment_horizon' => 'medium_term',
                'status' => 'active',
                'credit_score' => 750,
                'investor_score' => 85,
                'auto_invest_enabled' => true,
            ]
        );

        $this->command->info('Admin users created successfully with different roles.');
    }
    
    private function getRoleDescription(string $role): string
    {
        $descriptions = [
            'super_admin' => 'Full system access with all permissions',
            'admin' => 'Administrative access to manage system',
            'investor_manager' => 'Manages investor relationships and investments',
            'employee' => 'Support staff for processing operations',
            'auditor' => 'Read-only access for auditing purposes',
            'investor' => 'Corporate investor with funding capabilities', // Added description
        ];
        
        return $descriptions[$role] ?? 'System role';
    }
}