<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SouthAfricanDriverSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = [
            ['first_name' => 'Aphiwe', 'last_name' => 'Dlamini', 'email' => 'aphiwe.dlamini@example.com', 'phone' => '27821100001', 'city' => 'Johannesburg', 'home_address' => '12 Commissioner Street, Johannesburg', 'driver_platform' => 'uber', 'credit_score' => 708, 'initial_balance' => 6200, 'latitude' => -26.2041, 'longitude' => 28.0473, 'date_of_birth' => '1994-02-13'],
            ['first_name' => 'Naledi', 'last_name' => 'Mokoena', 'email' => 'naledi.mokoena@example.com', 'phone' => '27821100002', 'city' => 'Pretoria', 'home_address' => '45 Church Square, Pretoria', 'driver_platform' => 'bolt', 'credit_score' => 681, 'initial_balance' => 5100, 'latitude' => -25.7479, 'longitude' => 28.2293, 'date_of_birth' => '1992-07-28'],
            ['first_name' => 'Thabo', 'last_name' => 'Maseko', 'email' => 'thabo.maseko@example.com', 'phone' => '27821100003', 'city' => 'Soweto', 'home_address' => '88 Vilakazi Street, Soweto', 'driver_platform' => 'indrive', 'credit_score' => 642, 'initial_balance' => 4700, 'latitude' => -26.2485, 'longitude' => 27.8540, 'date_of_birth' => '1989-09-15'],
            ['first_name' => 'Lerato', 'last_name' => 'Nkosi', 'email' => 'lerato.nkosi@example.com', 'phone' => '27821100004', 'city' => 'Midrand', 'home_address' => '27 Lever Road, Midrand', 'driver_platform' => 'mr_d', 'credit_score' => 694, 'initial_balance' => 5600, 'latitude' => -25.9992, 'longitude' => 28.1263, 'date_of_birth' => '1995-04-06'],
            ['first_name' => 'Sibusiso', 'last_name' => 'Khumalo', 'email' => 'sibusiso.khumalo@example.com', 'phone' => '27821100005', 'city' => 'Centurion', 'home_address' => '19 Heuwel Road, Centurion', 'driver_platform' => 'takealot', 'credit_score' => 655, 'initial_balance' => 4300, 'latitude' => -25.8600, 'longitude' => 28.1890, 'date_of_birth' => '1991-12-19'],
            ['first_name' => 'Ayanda', 'last_name' => 'Mthembu', 'email' => 'ayanda.mthembu@example.com', 'phone' => '27821100006', 'city' => 'Durban', 'home_address' => '101 West Street, Durban', 'driver_platform' => 'checkers_sixty60', 'credit_score' => 721, 'initial_balance' => 7100, 'latitude' => -29.8587, 'longitude' => 31.0218, 'date_of_birth' => '1993-01-23'],
            ['first_name' => 'Nokuthula', 'last_name' => 'Zulu', 'email' => 'nokuthula.zulu@example.com', 'phone' => '27821100007', 'city' => 'Umhlanga', 'home_address' => '8 Lagoon Drive, Umhlanga', 'driver_platform' => 'uber', 'credit_score' => 676, 'initial_balance' => 4900, 'latitude' => -29.7266, 'longitude' => 31.0846, 'date_of_birth' => '1996-05-10'],
            ['first_name' => 'Bongani', 'last_name' => 'Cele', 'email' => 'bongani.cele@example.com', 'phone' => '27821100008', 'city' => 'Pinetown', 'home_address' => '55 Josiah Gumede Road, Pinetown', 'driver_platform' => 'bolt', 'credit_score' => 633, 'initial_balance' => 3900, 'latitude' => -29.8146, 'longitude' => 30.8503, 'date_of_birth' => '1988-11-04'],
            ['first_name' => 'Zanele', 'last_name' => 'Mabaso', 'email' => 'zanele.mabaso@example.com', 'phone' => '27821100009', 'city' => 'Cape Town', 'home_address' => '73 Long Street, Cape Town', 'driver_platform' => 'mr_d', 'credit_score' => 704, 'initial_balance' => 6400, 'latitude' => -33.9249, 'longitude' => 18.4241, 'date_of_birth' => '1994-08-21'],
            ['first_name' => 'Mihlali', 'last_name' => 'Ndlovu', 'email' => 'mihlali.ndlovu@example.com', 'phone' => '27821100010', 'city' => 'Khayelitsha', 'home_address' => '14 Spine Road, Khayelitsha', 'driver_platform' => 'checkers_sixty60', 'credit_score' => 648, 'initial_balance' => 4500, 'latitude' => -34.0380, 'longitude' => 18.6778, 'date_of_birth' => '1997-03-17'],
            ['first_name' => 'Kagiso', 'last_name' => 'Molefe', 'email' => 'kagiso.molefe@example.com', 'phone' => '27821100011', 'city' => 'Bloemfontein', 'home_address' => '24 Nelson Mandela Drive, Bloemfontein', 'driver_platform' => 'takealot', 'credit_score' => 671, 'initial_balance' => 5200, 'latitude' => -29.0852, 'longitude' => 26.1596, 'date_of_birth' => '1990-06-30'],
            ['first_name' => 'Refilwe', 'last_name' => 'Mahlangu', 'email' => 'refilwe.mahlangu@example.com', 'phone' => '27821100012', 'city' => 'Polokwane', 'home_address' => '6 Grobler Street, Polokwane', 'driver_platform' => 'indrive', 'credit_score' => 619, 'initial_balance' => 3600, 'latitude' => -23.9045, 'longitude' => 29.4689, 'date_of_birth' => '1992-10-12'],
            ['first_name' => 'Tshepo', 'last_name' => 'Moeketsi', 'email' => 'tshepo.moeketsi@example.com', 'phone' => '27821100013', 'city' => 'Rustenburg', 'home_address' => '31 Beyers Naude Drive, Rustenburg', 'driver_platform' => 'uber', 'credit_score' => 688, 'initial_balance' => 5500, 'latitude' => -25.6676, 'longitude' => 27.2421, 'date_of_birth' => '1987-02-08'],
            ['first_name' => 'Nandi', 'last_name' => 'Sithole', 'email' => 'nandi.sithole@example.com', 'phone' => '27821100014', 'city' => 'Gqeberha', 'home_address' => '63 Cape Road, Gqeberha', 'driver_platform' => 'other', 'driver_platform_other' => 'Uber Eats', 'credit_score' => 661, 'initial_balance' => 4800, 'latitude' => -33.9608, 'longitude' => 25.6022, 'date_of_birth' => '1995-09-02'],
            ['first_name' => 'Luyanda', 'last_name' => 'Jacobs', 'email' => 'luyanda.jacobs@example.com', 'phone' => '27821100015', 'city' => 'East London', 'home_address' => '17 Oxford Street, East London', 'driver_platform' => 'bolt', 'credit_score' => 645, 'initial_balance' => 4100, 'latitude' => -33.0153, 'longitude' => 27.9116, 'date_of_birth' => '1993-12-05'],
        ];

        foreach ($drivers as $driverData) {
            $name = $driverData['first_name'] . ' ' . $driverData['last_name'];

            $user = User::updateOrCreate(
                ['phone' => $driverData['phone']],
                [
                    'name' => $name,
                    'first_name' => $driverData['first_name'],
                    'last_name' => $driverData['last_name'],
                    'date_of_birth' => $driverData['date_of_birth'],
                    'gender' => 'male',
                    'email' => $driverData['email'],
                    'phone' => $driverData['phone'],
                    'id_number' => $this->generateSouthAfricanIdNumber($driverData['date_of_birth']),
                    'home_address' => $driverData['home_address'],
                    'city' => $driverData['city'],
                    'country' => 'South Africa',
                    'latitude' => $driverData['latitude'],
                    'longitude' => $driverData['longitude'],
                    'driver_platform' => $driverData['driver_platform'],
                    'driver_platform_other' => $driverData['driver_platform_other'] ?? null,
                    'password' => Hash::make('Driver123!'),
                    'credit_score' => $driverData['credit_score'],
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            if (!$user->hasRole('driver')) {
                $user->assignRole('driver');
            }

            $user->wallet()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => $driverData['initial_balance'],
                    'outstanding_balance' => 0,
                    'total_credit_used' => 0,
                    'total_repayments' => 0,
                    'currency' => 'ZAR',
                ]
            );

            $user->creditLimit()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'limit' => $this->calculateCreditLimit($driverData['credit_score']),
                    'used' => 0,
                    'review_date' => now()->addDays(90),
                    'status' => 'active',
                ]
            );
        }

        $this->command->info('15 additional South African drivers seeded successfully.');
    }

    private function calculateCreditLimit(int $creditScore): int
    {
        if ($creditScore >= 800) {
            return 50000;
        }
        if ($creditScore >= 700) {
            return 30000;
        }
        if ($creditScore >= 600) {
            return 15000;
        }
        if ($creditScore >= 500) {
            return 8000;
        }

        return 3000;
    }

    private function generateSouthAfricanIdNumber(string $dateOfBirth): string
    {
        $parts = explode('-', $dateOfBirth);
        $yyMMdd = substr($parts[0], 2, 2) . $parts[1] . $parts[2];
        $serial = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        return $yyMMdd . $serial . '08';
    }
}
