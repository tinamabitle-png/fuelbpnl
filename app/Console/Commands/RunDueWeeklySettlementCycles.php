<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\SettlementController;
use Illuminate\Console\Command;

class RunDueWeeklySettlementCycles extends Command
{
    protected $signature = 'settlements:run-due-weekly-cycles';

    protected $description = 'Process due weekly settlement payout cycles for brands and stations.';

    public function handle(SettlementController $controller): int
    {
        $result = $controller->executeDueWeeklyCycles(null);

        $this->info((string) ($result['message'] ?? 'Weekly cycle run completed.'));

        if ((int) ($result['failed'] ?? 0) > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

