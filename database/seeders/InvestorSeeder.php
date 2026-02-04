<?php
// [file name]: InvestorSeeder.php (updated)
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Investor;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class InvestorSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Oil & Gas Corporate Investors...');

        // Get or create investor role
        $investorRole = Role::firstOrCreate(
            ['name' => 'investor'],
            [
                'guard_name' => 'web',
                'description' => 'Corporate investor with funding capabilities'
            ]
        );

        $oilGasCompanies = [
            [
                'company_name' => 'Shell Kenya PLC',
                'registration_number' => 'C.123456',
                'tax_id' => 'P051234567K',
                'contact_person' => 'John Kamau',
                'contact_email' => 'investments@shell.co.ke',
                'contact_phone' => '+254722123456',
                'company_address' => 'Shell House, Harambee Avenue, Nairobi',
                'city' => 'Nairobi',
                'country' => 'Kenya',
                'total_investment_capital' => 500000000,
                'available_capital' => 200000000,
                'risk_profile' => 'moderate',
                'minimum_investment_amount' => 1000000,
                'maximum_investment_amount' => 50000000,
                'preferred_interest_rate_min' => 12.00,
                'preferred_interest_rate_max' => 18.00,
                'investment_horizon' => 'medium_term',
            ],
            // ... (keep the rest of your companies array)
        ];

        foreach ($oilGasCompanies as $companyData) {
            // Create or get user
            $user = User::firstOrCreate(
                ['email' => $companyData['contact_email']],
                [
                    'name' => $companyData['contact_person'],
                    'phone' => $companyData['contact_phone'],
                    'password' => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'credit_score' => rand(650, 850),
                ]
            );
            if (!$user->hasRole('investor')) {
    $user->assignRole($investorRole);
    $this->command->info("Assigned investor role to: {$user->email}");
}



            // Assign investor role
            if (!$user->hasRole('investor')) {
                $user->assignRole($investorRole);
                $this->command->info("Assigned investor role to: {$user->email}");
            }
$investorRole = Role::firstOrCreate(
    ['name' => 'investor_manager'],
    [
        'guard_name' => 'web',
        'description' => 'Corporate investor with funding capabilities'
    ]
);
            // Calculate additional fields
            $investedCapital = $companyData['total_investment_capital'] - $companyData['available_capital'];
            $interestEarned = $investedCapital * (rand(10, 20) / 100);

            // Create investor profile
            $investorData = [
                'user_id' => $user->id,
                'company_name' => $companyData['company_name'],
                'registration_number' => $companyData['registration_number'],
                'tax_id' => $companyData['tax_id'],
                'contact_person' => $companyData['contact_person'],
                'contact_email' => $companyData['contact_email'],
                'contact_phone' => $companyData['contact_phone'],
                'company_address' => $companyData['company_address'],
                'city' => $companyData['city'],
                'country' => $companyData['country'],
                'total_investment_capital' => $companyData['total_investment_capital'],
                'available_capital' => $companyData['available_capital'],
                'invested_capital' => $investedCapital,
                'interest_earned' => $interestEarned,
                'risk_profile' => $companyData['risk_profile'],
                'minimum_investment_amount' => $companyData['minimum_investment_amount'],
                'maximum_investment_amount' => $companyData['maximum_investment_amount'],
                'preferred_interest_rate_min' => $companyData['preferred_interest_rate_min'],
                'preferred_interest_rate_max' => $companyData['preferred_interest_rate_max'],
                'investment_horizon' => $companyData['investment_horizon'],
                'status' => 'active',
                'credit_score' => rand(700, 850),
                'investor_score' => rand(80, 95),
                'auto_invest_enabled' => true,
            ];

            $investor = Investor::updateOrCreate(
                ['user_id' => $user->id],
                $investorData
            );

            $this->command->info("Created investor: {$companyData['company_name']}");
        }

        $this->command->info('Successfully seeded ' . count($oilGasCompanies) . ' investors.');
    }
}