<?php

namespace Database\Seeders;

use App\Models\FuelStation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FuelStationSeeder extends Seeder
{
    public function run(): void
    {
        // Create merchant users first
        $shellMerchant = User::create([
            'name' => 'Shell Petrol Station',
            'email' => 'shell@example.com',
            'phone' => '254733333333',
            'password' => Hash::make('Merchant123!'),
            'credit_score' => 750,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        
        $shellMerchant->assignRole('merchant');
        $shellMerchant->wallet()->create([
            'balance' => 500000,
            'outstanding_balance' => 0,
            'currency' => 'ZAR',
        ]);
        
        $totalMerchant = User::create([
            'name' => 'Total Energies',
            'email' => 'total@example.com',
            'phone' => '254744444444',
            'password' => Hash::make('Merchant123!'),
            'credit_score' => 750,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        
        $totalMerchant->assignRole('merchant');
        $totalMerchant->wallet()->create([
            'balance' => 500000,
            'outstanding_balance' => 0,
            'currency' => 'ZAR',
        ]);
        
        $kobilMerchant = User::create([
            'name' => 'Kobil Petrol Station',
            'email' => 'kobil@example.com',
            'phone' => '254755555555',
            'password' => Hash::make('Merchant123!'),
            'credit_score' => 750,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        
        $kobilMerchant->assignRole('merchant');
        $kobilMerchant->wallet()->create([
            'balance' => 500000,
            'outstanding_balance' => 0,
            'currency' => 'ZAR',
        ]);
        
        // Create fuel stations
        $stations = [
            [
                'name' => 'Shell Westlands',
                'company' => 'Shell',
                'license_number' => 'FSS001',
                'address' => 'Westlands Road, Nairobi',
                'city' => 'Nairobi',
                'latitude' => -1.264592,
                'longitude' => 36.804288,
                'contact_person' => 'John Kamau',
                'contact_phone' => '254733333333',
                'contact_email' => 'shell-westlands@example.com',
                'owner_id' => $shellMerchant->id,
                'wallet_balance' => 500000,
            ],
            [
                'name' => 'Total Thika Road',
                'company' => 'Total',
                'license_number' => 'FSS002',
                'address' => 'Thika Road, Nairobi',
                'city' => 'Nairobi',
                'latitude' => -1.238023,
                'longitude' => 36.870396,
                'contact_person' => 'Mary Wanjiru',
                'contact_phone' => '254744444444',
                'contact_email' => 'total-thika@example.com',
                'owner_id' => $totalMerchant->id,
                'wallet_balance' => 450000,
            ],
            [
                'name' => 'Kobil Mombasa Road',
                'company' => 'Kobil',
                'license_number' => 'FSS003',
                'address' => 'Mombasa Road, Nairobi',
                'city' => 'Nairobi',
                'latitude' => -1.319092,
                'longitude' => 36.831282,
                'contact_person' => 'Peter Omondi',
                'contact_phone' => '254755555555',
                'contact_email' => 'kobil-mombasa@example.com',
                'owner_id' => $kobilMerchant->id,
                'wallet_balance' => 400000,
            ],
            [
                'name' => 'Shell Upper Hill',
                'company' => 'Shell',
                'license_number' => 'FSS004',
                'address' => 'Upper Hill, Nairobi',
                'city' => 'Nairobi',
                'latitude' => -1.292066,
                'longitude' => 36.821945,
                'contact_person' => 'Sarah Akinyi',
                'contact_phone' => '254766666666',
                'contact_email' => 'shell-upperhill@example.com',
                'owner_id' => $shellMerchant->id,
                'wallet_balance' => 350000,
            ],
            [
                'name' => 'Total Karen',
                'company' => 'Total',
                'license_number' => 'FSS005',
                'address' => 'Karen Shopping Centre, Nairobi',
                'city' => 'Nairobi',
                'latitude' => -1.319123,
                'longitude' => 36.708254,
                'contact_person' => 'James Mutiso',
                'contact_phone' => '254777777777',
                'contact_email' => 'total-karen@example.com',
                'owner_id' => $totalMerchant->id,
                'wallet_balance' => 300000,
            ],
        ];
        
        foreach ($stations as $station) {
            FuelStation::create($station);
        }
        
        $this->command->info('Fuel stations seeded successfully.');
    }
}
