<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fuel_vouchers') || !Schema::hasColumn('fuel_vouchers', 'status')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            // Keep the column compatible with application logic which uses 'approved'.
            DB::statement("ALTER TABLE `fuel_vouchers` MODIFY `status` ENUM('issued','approved','redeemed','expired','cancelled') NOT NULL DEFAULT 'issued'");
            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        // SQLite: Laravel enums create CHECK constraints; rebuild the table so 'approved' is allowed.
        Schema::disableForeignKeyConstraints();

        Schema::create('fuel_vouchers__tmp', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('qr_code')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('fuel_station_id')->constrained('fuel_stations')->onDelete('cascade');
            $table->foreignId('lease_id')->nullable()->constrained('leases')->nullOnDelete();
            $table->string('funding_source', 16)->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('redeemed_fuel_amount', 10, 2)->nullable();
            $table->decimal('redeemed_airtime_amount', 10, 2)->nullable();
            $table->decimal('liters', 8, 3);
            $table->string('fuel_type', 16);
            $table->string('status', 32)->default('issued');
            $table->dateTime('issued_at')->useCurrent();
            $table->dateTime('redeemed_at')->nullable();
            $table->dateTime('expires_at');
            $table->foreignId('settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
            $table->string('transaction_reference')->nullable();
            $table->string('airtime_phone', 32)->nullable();
            $table->string('airtime_reference', 128)->nullable();
            $table->string('airtime_status', 32)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['fuel_station_id', 'status']);
            $table->index('expires_at');
            $table->index('redeemed_at');
        });

        DB::statement("
            INSERT INTO fuel_vouchers__tmp (
                id, code, qr_code, user_id, fuel_station_id, lease_id, funding_source,
                amount, redeemed_fuel_amount, redeemed_airtime_amount, liters, fuel_type, status,
                issued_at, redeemed_at, expires_at, settlement_id, transaction_reference,
                airtime_phone, airtime_reference, airtime_status, created_at, updated_at
            )
            SELECT
                id, code, qr_code, user_id, fuel_station_id, lease_id, funding_source,
                amount, redeemed_fuel_amount, redeemed_airtime_amount, liters, fuel_type, status,
                issued_at, redeemed_at, expires_at, settlement_id, transaction_reference,
                airtime_phone, airtime_reference, airtime_status, created_at, updated_at
            FROM fuel_vouchers
        ");

        Schema::drop('fuel_vouchers');
        DB::statement('ALTER TABLE fuel_vouchers__tmp RENAME TO fuel_vouchers');

        // Recreate named index after the old table (and its indexes) has been dropped.
        Schema::table('fuel_vouchers', function (Blueprint $table) {
            $table->index(['user_id', 'funding_source', 'status'], 'fuel_vouchers_user_funding_status_idx');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (!Schema::hasTable('fuel_vouchers') || !Schema::hasColumn('fuel_vouchers', 'status')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `fuel_vouchers` MODIFY `status` ENUM('issued','redeemed','expired','cancelled') NOT NULL DEFAULT 'issued'");
    }
};
