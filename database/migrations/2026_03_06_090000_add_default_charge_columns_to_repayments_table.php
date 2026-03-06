<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            if (!Schema::hasColumn('repayments', 'repayment_type')) {
                $table->enum('repayment_type', ['regular', 'default_fee', 'default_interest'])
                    ->default('regular')
                    ->after('amount');
                $table->index(['lease_id', 'repayment_type']);
            }

            if (!Schema::hasColumn('repayments', 'base_repayment_id')) {
                $table->foreignId('base_repayment_id')
                    ->nullable()
                    ->after('repayment_type')
                    ->constrained('repayments')
                    ->nullOnDelete();
                $table->index('base_repayment_id');
            }

            if (!Schema::hasColumn('repayments', 'charged_for_date')) {
                $table->date('charged_for_date')->nullable()->after('due_date');
                $table->index('charged_for_date');
            }

            if (!Schema::hasColumn('repayments', 'metadata')) {
                $table->json('metadata')->nullable()->after('transaction_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            if (Schema::hasColumn('repayments', 'metadata')) {
                $table->dropColumn('metadata');
            }

            if (Schema::hasColumn('repayments', 'charged_for_date')) {
                $table->dropIndex(['charged_for_date']);
                $table->dropColumn('charged_for_date');
            }

            if (Schema::hasColumn('repayments', 'base_repayment_id')) {
                $table->dropConstrainedForeignId('base_repayment_id');
            }

            if (Schema::hasColumn('repayments', 'repayment_type')) {
                $table->dropIndex(['lease_id', 'repayment_type']);
                $table->dropColumn('repayment_type');
            }
        });
    }
};

