<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Lease;
use App\Models\FuelVoucher;
use App\Models\Repayment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create test drivers
        $drivers = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '254788888888',
                'credit_score' => 720,
                'initial_balance' => 15000,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '254799999999',
                'credit_score' => 680,
                'initial_balance' => 8000,
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike@example.com',
                'phone' => '254710101010',
                'credit_score' => 580,
                'initial_balance' => 3000,
            ],
            [
                'name' => 'Grace Wangari',
                'email' => 'grace@example.com',
                'phone' => '254711111112',
                'credit_score' => 650,
                'initial_balance' => 5000,
            ],
            [
                'name' => 'David Ochieng',
                'email' => 'david@example.com',
                'phone' => '254712121212',
                'credit_score' => 780,
                'initial_balance' => 20000,
            ],
        ];
        
        foreach ($drivers as $driverData) {
            $user = User::create([
                'name' => $driverData['name'],
                'email' => $driverData['email'],
                'phone' => $driverData['phone'],
                'password' => Hash::make('Driver123!'),
                'credit_score' => $driverData['credit_score'],
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            
            $user->assignRole('driver');
            
            // Create wallet
            $user->wallet()->create([
                'balance' => $driverData['initial_balance'],
                'outstanding_balance' => 0,
                'total_credit_used' => 0,
                'total_repayments' => 0,
                'currency' => 'ZAR',
            ]);
            
            // Create credit limit based on score
            $creditLimit = $this->calculateCreditLimit($driverData['credit_score']);
            $user->creditLimit()->create([
                'limit' => $creditLimit,
                'used' => 0,
                'review_date' => now()->addDays(90),
            ]);
            
            // Create leases and vouchers for some users
            if ($driverData['credit_score'] < 700) {
                $this->createTestLease($user);
            }
        }
        
        $this->command->info('Test users seeded successfully.');
    }
    
    private function calculateCreditLimit($creditScore)
    {
        if ($creditScore >= 800) return 50000;
        if ($creditScore >= 700) return 30000;
        if ($creditScore >= 600) return 15000;
        if ($creditScore >= 500) return 8000;
        if ($creditScore >= 400) return 3000;
        return 1000;
    }
    
    private function createTestLease(User $user)
    {
        $stations = \App\Models\FuelStation::limit(3)->get();
        
        foreach ($stations as $station) {
            // Create lease
            $principal = rand(1000, 5000);
            $interestRate = 5.0;
            $interest = $principal * ($interestRate / 100);
            $totalAmount = $principal + $interest;
            $termDays = 30;
            
            $lease = Lease::create([
                'user_id' => $user->id,
                'principal_amount' => $principal,
                'interest_rate' => $interestRate,
                'interest_amount' => $interest,
                'total_amount' => $totalAmount,
                'term_days' => $termDays,
                'daily_repayment' => $totalAmount / $termDays,
                'issued_at' => Carbon::now()->subDays(rand(5, 20)),
                'due_date' => Carbon::now()->addDays(rand(10, 30)),
            ]);
            
            // Create voucher
            $voucher = FuelVoucher::create([
                'code' => 'TEST' . strtoupper(uniqid()),
                'qr_code' => 'QR' . time() . rand(1000, 9999),
                'user_id' => $user->id,
                'fuel_station_id' => $station->id,
                'lease_id' => $lease->id,
                'amount' => $principal,
                'liters' => $principal / 150, // Assuming 150 KES per liter
                'fuel_type' => 'petrol',
                'status' => rand(0, 1) ? 'redeemed' : 'issued',
                'issued_at' => Carbon::now()->subDays(rand(1, 10)),
                'redeemed_at' => rand(0, 1) ? Carbon::now()->subDays(rand(1, 5)) : null,
                'expires_at' => Carbon::now()->addDays(30),
            ]);
            
            // Update user's wallet
            $user->wallet->increment('outstanding_balance', $principal);
            $user->wallet->increment('total_credit_used', $principal);
            
            // Update credit limit used
            $user->creditLimit->increment('used', $principal);
            
            // Create repayments
            $this->createRepayments($lease);
        }
    }
    
    private function createRepayments(Lease $lease)
    {
        $totalDays = $lease->term_days;
        $dailyAmount = $lease->daily_repayment;
        $issuedAt = $lease->issued_at;
        
        for ($i = 1; $i <= $totalDays; $i++) {
            $dueDate = $issuedAt->copy()->addDays($i);
            $status = 'pending';
            
            if ($dueDate->isPast()) {
                $status = rand(0, 1) ? 'paid' : (rand(0, 1) ? 'overdue' : 'pending');
            }
            
            Repayment::create([
                'lease_id' => $lease->id,
                'user_id' => $lease->user_id,
                'amount' => $dailyAmount,
                'due_date' => $dueDate,
                'paid_at' => $status === 'paid' ? $dueDate->copy()->addDays(rand(0, 2)) : null,
                'status' => $status,
            ]);
        }
    }
}
