<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Lease;
use App\Models\FuelVoucher;
use App\Models\Repayment;
use App\Models\LeaseInvestment;
use App\Models\Investor;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class LeaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding sample leases...');

        // Get all drivers and fuel stations
        $drivers = User::role('driver')->get();
        $stations = \App\Models\FuelStation::where('status', 'active')->get();
        
        // Get investors without the scope method that doesn't exist
        $investors = Investor::where('status', 'active')->get();

        if ($drivers->isEmpty() || $stations->isEmpty()) {
            $this->command->warn('No drivers or fuel stations found. Please seed users and stations first.');
            return;
        }

        // Updated: Use valid ENUM values for fuel_type
        $fuelTypes = ['petrol', 'diesel', 'super']; // Valid values from migration
        
        $statuses = ['active', 'active', 'active', 'active', 'active', 'completed', 'defaulted'];
        $totalLeases = 200;
        $createdCount = 0;
        $investorFundedCount = 0;

        for ($i = 0; $i < $totalLeases; $i++) {
            try {
                $driver = $drivers->random();
                $station = $stations->random();
                
                // Determine lease status with weighted probabilities
                $status = $statuses[array_rand($statuses)];
                
                // Generate lease parameters
                $principal = $this->generatePrincipalAmount($driver->credit_score);
                $interestRate = $this->generateInterestRate($driver->credit_score, $status);
                $interestAmount = $principal * ($interestRate / 100);
                $totalAmount = $principal + $interestAmount;
                $termDays = $this->generateTermDays($driver->credit_score);
                $dailyRepayment = round($totalAmount / $termDays, 2);
                
                // Determine dates based on status
                $issuedAt = Carbon::now()->subDays(rand(5, 180));
                $dueDate = $issuedAt->copy()->addDays($termDays);
                
                // Adjust dates based on status
                list($completedAt, $defaultedAt, $actualStatus) = $this->determineDatesByStatus(
                    $status, $issuedAt, $dueDate
                );
                
                // Create the lease
                $lease = Lease::create([
                    'user_id' => $driver->id,
                    'principal_amount' => $principal,
                    'interest_rate' => $interestRate,
                    'interest_amount' => $interestAmount,
                    'total_amount' => $totalAmount,
                    'term_days' => $termDays,
                    'daily_repayment' => $dailyRepayment,
                    'status' => $actualStatus,
                    'issued_at' => $issuedAt,
                    'due_date' => $dueDate,
                    'completed_at' => $completedAt,
                    'defaulted_at' => $defaultedAt,
                ]);
                
                // Create associated voucher with valid fuel_type
                $fuelType = $fuelTypes[array_rand($fuelTypes)];
                $this->createFuelVoucher($lease, $station, $principal, $fuelType, $issuedAt);
                
                // Create repayments
                $this->createRepaymentSchedule($lease, $issuedAt, $dueDate, $actualStatus);
                
                // Update user's wallet and credit limit
                $this->updateUserFinancials($driver, $lease, $actualStatus);
                
                // Randomly assign investor funding (30% of leases)
                if ($investors->isNotEmpty() && rand(1, 100) <= 30) {
                    $this->createLeaseInvestment($lease, $investors->random());
                    $investorFundedCount++;
                }
                
                $createdCount++;
                
                if ($createdCount % 50 === 0) {
                    $this->command->info("Created {$createdCount} leases...");
                }
                
            } catch (\Exception $e) {
                $this->command->warn("Failed to create lease: " . $e->getMessage());
                continue;
            }
        }
        
        $this->command->info("Successfully created {$createdCount} leases.");
        $this->command->info("{$investorFundedCount} leases were investor-funded.");
        $this->command->info("Lease status distribution:");
        
        $statusCounts = Lease::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
            
        foreach ($statusCounts as $status => $count) {
            $percentage = ($count / $createdCount) * 100;
            $this->command->info("  {$status}: {$count} ({$percentage}%)");
        }
        
        // Calculate and display portfolio metrics
        $this->calculatePortfolioMetrics();
    }
    
    /**
     * Generate principal amount based on credit score
     */
    private function generatePrincipalAmount(int $creditScore): float
    {
        if ($creditScore >= 800) {
            return rand(5000, 20000);
        } elseif ($creditScore >= 700) {
            return rand(3000, 12000);
        } elseif ($creditScore >= 600) {
            return rand(2000, 8000);
        } elseif ($creditScore >= 500) {
            return rand(1000, 5000);
        } else {
            return rand(500, 3000);
        }
    }
    
    /**
     * Generate interest rate based on credit score and status
     */
    private function generateInterestRate(int $creditScore, string $status): float
    {
        $baseRate = match (true) {
            $creditScore >= 800 => 3.5,
            $creditScore >= 700 => 4.5,
            $creditScore >= 600 => 5.5,
            $creditScore >= 500 => 7.0,
            default => 9.0,
        };
        
        // Add risk premium for potentially riskier leases
        if ($status === 'defaulted') {
            $baseRate += rand(2, 5);
        }
        
        return round($baseRate + (rand(0, 10) / 10), 2);
    }
    
    /**
     * Generate term days based on credit score
     */
    private function generateTermDays(int $creditScore): int
    {
        if ($creditScore >= 800) {
            return rand(60, 180);
        } elseif ($creditScore >= 700) {
            return rand(45, 120);
        } elseif ($creditScore >= 600) {
            return rand(30, 90);
        } elseif ($creditScore >= 500) {
            return rand(15, 60);
        } else {
            return rand(7, 30);
        }
    }
    
    /**
     * Determine dates based on lease status
     */
    private function determineDatesByStatus(string $status, Carbon $issuedAt, Carbon $dueDate): array
    {
        $completedAt = null;
        $defaultedAt = null;
        $actualStatus = $status;
        
        switch ($status) {
            case 'completed':
                // Randomly complete somewhere between 80-100% of term
                $daysEarly = rand(0, floor(($dueDate->diffInDays($issuedAt) * 0.2)));
                $completedAt = $dueDate->copy()->subDays($daysEarly);
                break;
                
            case 'defaulted':
                // Default somewhere after due date (1-30 days late)
                $daysLate = rand(1, 30);
                $defaultedAt = $dueDate->copy()->addDays($daysLate);
                break;
                
            case 'active':
                // If due date is in the past, consider making it overdue
                if ($dueDate->isPast() && rand(1, 100) <= 20) {
                    $actualStatus = 'defaulted';
                    $daysLate = rand(1, 60);
                    $defaultedAt = $dueDate->copy()->addDays($daysLate);
                }
                break;
        }
        
        return [$completedAt, $defaultedAt, $actualStatus];
    }
    
    /**
     * Create fuel voucher for lease
     */
    private function createFuelVoucher(Lease $lease, $station, float $amount, string $fuelType, Carbon $issuedAt): void
    {
        $statuses = ['issued', 'redeemed', 'redeemed', 'issued', 'cancelled'];
        $voucherStatus = $statuses[array_rand($statuses)];
        
        // Adjust status based on lease status
        if ($lease->status === 'completed') {
            $voucherStatus = 'redeemed';
        } elseif ($lease->status === 'defaulted') {
            $voucherStatus = rand(0, 1) ? 'redeemed' : 'issued';
        }
        
        $voucher = FuelVoucher::create([
            'code' => 'VC' . strtoupper(Str::random(8)),
            'qr_code' => 'QR-' . time() . '-' . Str::random(6),
            'user_id' => $lease->user_id,
            'fuel_station_id' => $station->id,
            'lease_id' => $lease->id,
            'amount' => $amount,
            'liters' => round($amount / 150, 3), // Assuming average price of 150 ZAR per liter
            'fuel_type' => $fuelType, // Now using valid ENUM value
            'status' => $voucherStatus,
            'issued_at' => $issuedAt,
            'redeemed_at' => $voucherStatus === 'redeemed' ? $issuedAt->copy()->addDays(rand(1, 5)) : null,
            'expires_at' => $issuedAt->copy()->addDays(30),
        ]);
    }
    
    /**
     * Create repayment schedule for lease
     */
    private function createRepaymentSchedule(Lease $lease, Carbon $issuedAt, Carbon $dueDate, string $status): void
    {
        $totalDays = $lease->term_days;
        $dailyAmount = $lease->daily_repayment;
        $totalPaid = 0;
        
        for ($day = 1; $day <= $totalDays; $day++) {
            $dueDateForDay = $issuedAt->copy()->addDays($day);
            $statusForDay = 'pending';
            $paidAt = null;
            
            // Determine payment status based on lease status and day
            if ($status === 'completed') {
                // All payments made, possibly with some delays
                if ($dueDateForDay->isPast()) {
                    $statusForDay = 'paid';
                    $paidAt = $dueDateForDay->copy()->addDays(rand(0, 3));
                    $totalPaid += $dailyAmount;
                }
            } elseif ($status === 'defaulted') {
                // Some payments made, then stopped
                if ($dueDateForDay->lte($lease->defaulted_at)) {
                    if (rand(1, 100) <= 70) { // 70% chance of payment before default
                        $statusForDay = 'paid';
                        $paidAt = $dueDateForDay->copy()->addDays(rand(0, 7));
                        $totalPaid += $dailyAmount;
                    } else {
                        $statusForDay = 'overdue';
                    }
                } else {
                    $statusForDay = 'overdue';
                }
            } else {
                // Active lease - simulate various payment patterns
                if ($dueDateForDay->isPast()) {
                    $paymentProbability = match (true) {
                        $lease->user->credit_score >= 800 => 95,
                        $lease->user->credit_score >= 700 => 85,
                        $lease->user->credit_score >= 600 => 70,
                        $lease->user->credit_score >= 500 => 60,
                        default => 50,
                    };
                    
                    if (rand(1, 100) <= $paymentProbability) {
                        $statusForDay = 'paid';
                        $paidAt = $dueDateForDay->copy()->addDays(rand(0, 5));
                        $totalPaid += $dailyAmount;
                    } else {
                        $statusForDay = rand(0, 1) ? 'overdue' : 'pending';
                    }
                }
            }
            
            Repayment::create([
                'lease_id' => $lease->id,
                'user_id' => $lease->user_id,
                'amount' => $dailyAmount,
                'due_date' => $dueDateForDay,
                'paid_at' => $paidAt,
                'status' => $statusForDay,
                'payment_method' => $statusForDay === 'paid' ? $this->randomPaymentMethod() : null,
                'transaction_reference' => $statusForDay === 'paid' ? 'TXN-' . time() . '-' . Str::random(6) : null,
            ]);
        }
        
        // Update lease progress if completed
        if ($status === 'completed' && abs($totalPaid - $lease->total_amount) < 1) {
            $lease->update([
                'completed_at' => $dueDate->copy()->subDays(rand(0, 10)),
            ]);
        }
    }
    
    /**
     * Random payment method generator
     */
    private function randomPaymentMethod(): string
    {
        $methods = ['mpesa', 'bank_transfer', 'credit_card', 'cash', 'equity'];
        return $methods[array_rand($methods)];
    }
    
    /**
     * Update user financials after lease creation
     */
    private function updateUserFinancials(User $user, Lease $lease, string $status): void
    {
        $wallet = $user->wallet;
        $creditLimit = $user->creditLimit;
        
        if (!$wallet) {
            $wallet = $user->wallet()->create([
                'balance' => 0,
                'outstanding_balance' => 0,
                'total_credit_used' => 0,
                'total_repayments' => 0,
                'currency' => 'ZAR',
            ]);
        }
        
        if (!$creditLimit) {
            $creditLimit = $user->creditLimit()->create([
                'limit' => $this->calculateCreditLimit($user->credit_score),
                'used' => 0,
                'review_date' => now()->addDays(90),
                'status' => 'active',
            ]);
        }
        
        // Update outstanding balance
        $wallet->increment('outstanding_balance', $lease->principal_amount);
        $wallet->increment('total_credit_used', $lease->principal_amount);
        
        // Update credit limit used
        $creditLimit->increment('used', $lease->principal_amount);
        
        // Update total repayments if lease is completed
        if ($status === 'completed') {
            $wallet->increment('total_repayments', $lease->total_amount);
            $wallet->decrement('outstanding_balance', $lease->principal_amount);
            $creditLimit->decrement('used', $lease->principal_amount);
        }
        
        // Update user's credit score based on payment behavior
        $this->updateCreditScore($user, $status);
    }
    
    /**
     * Calculate credit limit based on credit score
     */
    private function calculateCreditLimit(int $creditScore): float
    {
        if ($creditScore >= 800) return 50000;
        if ($creditScore >= 700) return 30000;
        if ($creditScore >= 600) return 15000;
        if ($creditScore >= 500) return 8000;
        if ($creditScore >= 400) return 3000;
        return 1000;
    }
    
    /**
     * Update user credit score based on lease performance
     */
    private function updateCreditScore(User $user, string $status): void
    {
        $change = match ($status) {
            'completed' => rand(10, 25),
            'defaulted' => rand(-50, -30),
            default => rand(-5, 5),
        };
        
        $newScore = max(300, min(850, $user->credit_score + $change));
        $user->update(['credit_score' => $newScore]);
    }
    
    /**
     * Create investor funding for lease
     */
    private function createLeaseInvestment(Lease $lease, Investor $investor): void
    {
        // Determine investment amount (50-80% of lease amount)
        $investmentPercentage = rand(50, 80) / 100;
        $investmentAmount = round($lease->total_amount * $investmentPercentage, 2);
        
        // Ensure investor has enough capital
        if ($investmentAmount > $investor->available_capital) {
            $investmentAmount = $investor->available_capital;
        }
        
        if ($investmentAmount < $investor->minimum_investment_amount) {
            return; // Skip if below minimum
        }
        
        $investment = LeaseInvestment::create([
            'lease_id' => $lease->id,
            'investor_id' => $investor->id,
            'amount_invested' => $investmentAmount,
            'percentage_ownership' => round(($investmentAmount / $lease->total_amount) * 100, 2),
            'interest_rate' => $lease->interest_rate * 0.8, // Investor gets 80% of interest
            'expected_interest' => round($investmentAmount * (($lease->interest_rate * 0.8) / 100) * ($lease->term_days / 365), 2),
            'interest_earned' => 0,
            'status' => $lease->status,
            'investment_date' => $lease->issued_at,
            'maturity_date' => $lease->due_date,
            'expected_maturity_date' => $lease->due_date,
            'actual_maturity_date' => $lease->completed_at,
            'return_on_investment' => 0,
            'payment_schedule' => 'daily',
            'auto_reinvest' => rand(0, 1) === 1,
        ]);
        
        // Update investor capital
        $investor->decrement('available_capital', $investmentAmount);
        $investor->increment('invested_capital', $investmentAmount);
        
        // If lease is completed, create returns for investor
        if ($lease->status === 'completed') {
            $this->createInvestmentReturns($investment);
        }
    }
    
    /**
     * Create returns for completed investments
     */
    private function createInvestmentReturns(LeaseInvestment $investment): void
    {
        $totalReturns = $investment->expected_interest * rand(90, 110) / 100; // 90-110% of expected
        $numReturns = min(30, $investment->lease->term_days);
        $returnAmount = round($totalReturns / $numReturns, 2);
        
        for ($i = 0; $i < $numReturns; $i++) {
            $paymentDate = $investment->investment_date->copy()->addDays($i + 1);
            
            if ($paymentDate->isPast()) {
                $investment->returns()->create([
                    'type' => 'interest',
                    'amount' => $returnAmount,
                    'payment_date' => $paymentDate,
                    'reference' => 'RET-' . $paymentDate->format('Ymd') . '-' . Str::random(6),
                    'status' => 'completed',
                    'notes' => 'Daily interest payment',
                ]);
                
                $investment->increment('interest_earned', $returnAmount);
            }
        }
        
        // Update final metrics
        if ($investment->amount_invested > 0) {
            $investment->update([
                'return_on_investment' => ($investment->interest_earned / $investment->amount_invested) * 100,
                'status' => 'completed',
                'actual_maturity_date' => $investment->lease->completed_at,
            ]);
            
            // Update investor
            $investment->investor->increment('interest_earned', $investment->interest_earned);
        }
    }
    
    /**
     * Calculate and display portfolio metrics
     */
    private function calculatePortfolioMetrics(): void
    {
        $totalLeases = Lease::count();
        if ($totalLeases === 0) {
            $this->command->info("\nNo leases created to calculate metrics.");
            return;
        }
        
        $totalPrincipal = Lease::sum('principal_amount');
        $totalInterest = Lease::sum('interest_amount');
        $totalAmount = Lease::sum('total_amount');
        
        // Use direct queries instead of scopes
        $activeLeases = Lease::where('status', 'active')->count();
        $completedLeases = Lease::where('status', 'completed')->count();
        $defaultedLeases = Lease::where('status', 'defaulted')->count();
        
        $avgInterestRate = Lease::avg('interest_rate');
        $avgTerm = Lease::avg('term_days');
        $avgPrincipal = Lease::avg('principal_amount');
        
        $this->command->info("\n=== PORTFOLIO METRICS ===");
        $this->command->info("Total Leases: {$totalLeases}");
        $this->command->info("Total Principal: ZAR " . number_format($totalPrincipal, 2));
        $this->command->info("Total Interest: ZAR " . number_format($totalInterest, 2));
        $this->command->info("Total Portfolio: ZAR " . number_format($totalAmount, 2));
        $this->command->info("\nStatus Distribution:");
        $this->command->info("  Active: {$activeLeases} (" . round(($activeLeases/$totalLeases)*100, 1) . "%)");
        $this->command->info("  Completed: {$completedLeases} (" . round(($completedLeases/$totalLeases)*100, 1) . "%)");
        $this->command->info("  Defaulted: {$defaultedLeases} (" . round(($defaultedLeases/$totalLeases)*100, 1) . "%)");
        $this->command->info("\nAverages:");
        $this->command->info("  Interest Rate: " . round($avgInterestRate, 2) . "%");
        $this->command->info("  Term: " . round($avgTerm, 1) . " days");
        $this->command->info("  Principal: ZAR " . number_format($avgPrincipal, 2));
        
        // Investor-funded metrics
        $investorFundedLeases = Lease::whereHas('leaseInvestments')->count();
        $totalInvestorFunding = LeaseInvestment::sum('amount_invested');
        
        // Calculate average investor ownership safely
        $avgInvestorOwnership = Lease::whereHas('leaseInvestments')
            ->withSum('leaseInvestments', 'percentage_ownership')
            ->get()
            ->avg('lease_investments_sum_percentage_ownership') ?? 0;
            
        $this->command->info("\nInvestor Metrics:");
        $this->command->info("  Investor-Funded Leases: {$investorFundedLeases}");
        $this->command->info("  Total Investor Funding: ZAR " . number_format($totalInvestorFunding, 2));
        $this->command->info("  Avg. Investor Ownership: " . round($avgInvestorOwnership, 2) . "%");
        
        // Calculate portfolio health metrics
        $defaultRate = $defaultedLeases > 0 ? ($defaultedLeases / $totalLeases) * 100 : 0;
        $completionRate = $completedLeases > 0 ? ($completedLeases / $totalLeases) * 100 : 0;
        
        $this->command->info("\nPortfolio Health:");
        $this->command->info("  Default Rate: " . round($defaultRate, 2) . "%");
        $this->command->info("  Completion Rate: " . round($completionRate, 2) . "%");
        
        // Calculate weighted average credit score
        $leasesWithUsers = Lease::with('user')->get();
        $weightedCreditScore = $leasesWithUsers->sum(function($lease) {
            return $lease->principal_amount * ($lease->user->credit_score ?? 0);
        });
        $avgWeightedCreditScore = $totalPrincipal > 0 ? $weightedCreditScore / $totalPrincipal : 0;
        
        $this->command->info("  Weighted Avg Credit Score: " . round($avgWeightedCreditScore, 0));
        
        // Calculate days overdue for active leases
        $activeLeasesData = Lease::where('status', 'active')
            ->where('due_date', '<', now())
            ->get();
            
        if ($activeLeasesData->count() > 0) {
            $avgDaysOverdue = $activeLeasesData->avg(function($lease) {
                return now()->diffInDays($lease->due_date);
            });
            $this->command->info("  Avg Days Overdue (Active Leases): " . round($avgDaysOverdue, 1) . " days");
        }
    }
}