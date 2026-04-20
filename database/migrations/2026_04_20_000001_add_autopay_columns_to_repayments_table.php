<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            if (!Schema::hasColumn('repayments', 'autopay_attempts')) {
                $table->unsignedInteger('autopay_attempts')->default(0)->after('metadata');
            }

            if (!Schema::hasColumn('repayments', 'autopay_last_attempt_at')) {
                $table->timestamp('autopay_last_attempt_at')->nullable()->after('autopay_attempts');
            }

            if (!Schema::hasColumn('repayments', 'autopay_next_attempt_at')) {
                $table->timestamp('autopay_next_attempt_at')->nullable()->after('autopay_last_attempt_at');
            }

            if (!Schema::hasColumn('repayments', 'autopay_status')) {
                $table->string('autopay_status')->nullable()->after('autopay_next_attempt_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            $columns = [
                'autopay_status',
                'autopay_next_attempt_at',
                'autopay_last_attempt_at',
                'autopay_attempts',
            ];

            $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('repayments', $column)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
