<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fuel_vouchers')) {
            return;
        }

        Schema::table('fuel_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_vouchers', 'redeemed_fuel_amount')) {
                $table->decimal('redeemed_fuel_amount', 10, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('fuel_vouchers', 'redeemed_airtime_amount')) {
                $table->decimal('redeemed_airtime_amount', 10, 2)->nullable()->after('redeemed_fuel_amount');
            }
            if (!Schema::hasColumn('fuel_vouchers', 'airtime_phone')) {
                $table->string('airtime_phone', 32)->nullable()->after('transaction_reference');
            }
            if (!Schema::hasColumn('fuel_vouchers', 'airtime_reference')) {
                $table->string('airtime_reference', 128)->nullable()->after('airtime_phone');
            }
            if (!Schema::hasColumn('fuel_vouchers', 'airtime_status')) {
                $table->string('airtime_status', 32)->nullable()->after('airtime_reference');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fuel_vouchers')) {
            return;
        }

        Schema::table('fuel_vouchers', function (Blueprint $table) {
            $columns = [
                'redeemed_fuel_amount',
                'redeemed_airtime_amount',
                'airtime_phone',
                'airtime_reference',
                'airtime_status',
            ];

            $existing = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('fuel_vouchers', $column)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
