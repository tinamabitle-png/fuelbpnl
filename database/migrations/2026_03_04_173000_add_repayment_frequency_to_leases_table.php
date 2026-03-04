<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            if (!Schema::hasColumn('leases', 'repayment_frequency')) {
                $table->enum('repayment_frequency', ['daily', 'weekly'])
                    ->default('daily')
                    ->after('daily_repayment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            if (Schema::hasColumn('leases', 'repayment_frequency')) {
                $table->dropColumn('repayment_frequency');
            }
        });
    }
};

