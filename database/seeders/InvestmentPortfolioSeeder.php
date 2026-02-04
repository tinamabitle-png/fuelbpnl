<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Investor;
use App\Models\Lease;
use App\Models\LeaseInvestment;
use App\Models\InvestmentReturn;
use Carbon\Carbon;
use Illuminate\Support\Str;

class InvestmentPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding investment portfolios...');

        $investors = Investor::where('status', 'active')->get();
        $leases = Lease::where('status', 'active')->take(100)->get();

        if ($leases->isEmpty()) {
            $this->command->warn('No active leases found. Please seed leases first.');
            return;
        }

        $totalInvestments = 0;

        foreach ($investors as $investor) {
            // Determine how many leases this investor will fund (based on available capital)
            $maxInvestments = min(floor($investor->available_capital / 100000), 15);
            $numInvestments = rand(3, $maxInvestments);

            for ($i = 0; $i < $numInvestments; $i++) {
                // Find a lease that hasn't been fully funded yet
                $lease = $leases->random();

                // Calculate investment amount (not exceeding available capital or lease amount)
                $maxAmount = min(
                    $investor->available_capital,
                    $lease->total_amount * 0.8, // Max 80% of lease
                    rand(100000, 5000000)
                );

                if ($maxAmount < $investor->minimum_investment_amount) {
                    continue;
                }

                // Determine investment status
                $statuses = ['active', 'active', 'active', 'completed', 'defaulted'];
                $status = $statuses[array_rand($statuses)];

                // Create investment
                $investment = LeaseInvestment::create([
                    'lease_id' => $lease->id,
                    'investor_id' => $investor->id,
                    'amount_invested' => $maxAmount,
                    'percentage_ownership' => ($maxAmount / $lease->total_amount) * 100,
                    'interest_rate' => $lease->interest_rate,
                    'expected_interest' => $maxAmount * ($lease->interest_rate / 100) * ($lease->term_days / 365),
                    'status' => $status,
                    'investment_date' => Carbon::now()->subDays(rand(1, 90)),
                    'maturity_date' => $lease->due_date,
                    'expected_maturity_date' => $lease->due_date,
                    'actual_maturity_date' => $status === 'completed' ? Carbon::now()->subDays(rand(1, 30)) : null,
                    'return_on_investment' => $status === 'completed' ? rand(10, 25) : 0,
                    'payment_schedule' => 'daily',
                    'auto_reinvest' => rand(0, 1),
                ]);

                // Update investor capital
                $investor->decrement('available_capital', $maxAmount);
                $investor->increment('invested_capital', $maxAmount);

                // Create returns for completed investments
                if ($status === 'completed') {
                    $this->createInvestmentReturns($investment);
                }

                $totalInvestments++;
            }

            $this->command->info("Created {$numInvestments} investments for {$investor->company_name}");
        }

        $this->command->info("Successfully created {$totalInvestments} sample investments across all investors.");
    }
    
    private function createInvestmentReturns(LeaseInvestment $investment): void
    {
        $totalReturns = 0;
        $expectedTotal = $investment->expected_interest;
        $numReturns = rand(10, 30); // Simulate daily returns

        for ($i = 0; $i < $numReturns; $i++) {
            $returnAmount = min(
                $expectedTotal - $totalReturns,
                rand(100, 5000)
            );

            if ($returnAmount <= 0) break;

            InvestmentReturn::create([
                'lease_investment_id' => $investment->id,
                'type' => 'interest',
                'amount' => $returnAmount,
                'payment_date' => Carbon::now()->subDays($numReturns - $i),
                'reference' => 'RET-' . time() . '-' . Str::random(8),
                'status' => 'completed',
                'notes' => 'Daily interest payment',
            ]);

            $totalReturns += $returnAmount;
            $investment->increment('interest_earned', $returnAmount);
        }

        // Update investor's earned interest
        $investment->investor->increment('interest_earned', $totalReturns);
    }
}