<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Investor;
use App\Models\LeaseInvestment;
use Carbon\Carbon;

class InvestorAnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding investor analytics data...');

        $investors = Investor::where('status', 'active')->get();

        foreach ($investors as $investor) {
            // Calculate performance metrics based on investments
            $investments = $investor->leaseInvestments()->get();
            
            if ($investments->isEmpty()) {
                continue;
            }

            $totalInvested = $investments->sum('amount_invested');
            $totalEarned = $investments->sum('interest_earned');
            $completedInvestments = $investments->where('status', 'completed');
            $defaultedInvestments = $investments->where('status', 'defaulted');
            $activeInvestments = $investments->where('status', 'active');

            // Calculate metrics
            $averageReturn = $totalInvested > 0 ? ($totalEarned / $totalInvested) * 100 : 0;
            $defaultRate = $investments->count() > 0 ? 
                ($defaultedInvestments->count() / $investments->count()) * 100 : 0;

            // Calculate investor score (0-100)
            $investorScore = $this->calculateInvestorScore([
                'average_return' => $averageReturn,
                'default_rate' => $defaultRate,
                'investment_count' => $investments->count(),
                'diversity_score' => $this->calculateDiversityScore($investments),
                'tenure_months' => $investor->created_at->diffInMonths(now()),
            ]);

            // NOTE: These columns don't exist in your Investor model's $fillable
            // You would need to add them to your database first
            // For now, we'll just log the calculated values
            
            $this->command->info("Analytics for {$investor->company_name}:");
            $this->command->info("  Average Return: " . round($averageReturn, 2) . "%");
            $this->command->info("  Default Rate: " . round($defaultRate, 2) . "%");
            $this->command->info("  Total Investments: " . $investments->count());
            $this->command->info("  Active Investments: " . $activeInvestments->count());
            $this->command->info("  Completed Investments: " . $completedInvestments->count());
            $this->command->info("  Investor Score: " . $investorScore);
            
            // If you add these columns to your database, uncomment this:
            /*
            $investor->update([
                'average_return_rate' => round($averageReturn, 2),
                'default_rate' => round($defaultRate, 2),
                'total_investments' => $investments->count(),
                'active_investments' => $activeInvestments->count(),
                'completed_investments' => $completedInvestments->count(),
                'investor_score' => $investorScore,
            ]);
            */
        }

        $this->command->info('Investor analytics calculations completed.');
        $this->command->info('NOTE: To save these analytics, add the following columns to your investors table:');
        $this->command->info('  - average_return_rate (decimal:2)');
        $this->command->info('  - default_rate (decimal:2)');
        $this->command->info('  - total_investments (integer)');
        $this->command->info('  - active_investments (integer)');
        $this->command->info('  - completed_investments (integer)');
        $this->command->info('  - investor_score (integer)');
    }

    /**
     * Calculate investor score based on multiple factors
     */
    private function calculateInvestorScore(array $metrics): int
    {
        $score = 0;

        // Return rate (0-40 points)
        if ($metrics['average_return'] >= 20) $score += 40;
        elseif ($metrics['average_return'] >= 15) $score += 35;
        elseif ($metrics['average_return'] >= 10) $score += 30;
        elseif ($metrics['average_return'] >= 5) $score += 20;
        else $score += 10;

        // Default rate (0-30 points)
        if ($metrics['default_rate'] <= 1) $score += 30;
        elseif ($metrics['default_rate'] <= 3) $score += 25;
        elseif ($metrics['default_rate'] <= 5) $score += 20;
        elseif ($metrics['default_rate'] <= 10) $score += 10;
        else $score += 5;

        // Investment count (0-15 points)
        if ($metrics['investment_count'] >= 100) $score += 15;
        elseif ($metrics['investment_count'] >= 50) $score += 12;
        elseif ($metrics['investment_count'] >= 20) $score += 10;
        elseif ($metrics['investment_count'] >= 10) $score += 7;
        elseif ($metrics['investment_count'] >= 5) $score += 5;
        else $score += 3;

        // Diversity (0-10 points)
        $score += $metrics['diversity_score'];

        // Tenure (0-5 points)
        if ($metrics['tenure_months'] >= 24) $score += 5;
        elseif ($metrics['tenure_months'] >= 12) $score += 4;
        elseif ($metrics['tenure_months'] >= 6) $score += 3;
        elseif ($metrics['tenure_months'] >= 3) $score += 2;
        else $score += 1;

        return min(100, $score);
    }

    /**
     * Calculate portfolio diversity score
     */
    private function calculateDiversityScore($investments): int
    {
        if ($investments->count() < 5) return 2;

        // Count unique lease characteristics
        $uniqueLeases = $investments->unique('lease_id')->count();
        $diversityRatio = $uniqueLeases / $investments->count();

        if ($diversityRatio >= 0.9) return 10;
        if ($diversityRatio >= 0.7) return 8;
        if ($diversityRatio >= 0.5) return 6;
        if ($diversityRatio >= 0.3) return 4;
        return 2;
    }
}