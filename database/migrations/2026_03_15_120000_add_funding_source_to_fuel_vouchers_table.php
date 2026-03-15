<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fuel_vouchers')) {
            return;
        }

        Schema::table('fuel_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_vouchers', 'funding_source')) {
                $table->string('funding_source', 16)->nullable()->after('lease_id');
                $table->index(['user_id', 'funding_source', 'status'], 'fuel_vouchers_user_funding_status_idx');
            }
        });

        // Backfill: lease-backed vouchers are BNPL; lease-less vouchers are wallet-funded.
        if (Schema::hasColumn('fuel_vouchers', 'funding_source')) {
            DB::table('fuel_vouchers')
                ->whereNull('funding_source')
                ->whereNull('lease_id')
                ->update(['funding_source' => 'wallet']);

            DB::table('fuel_vouchers')
                ->whereNull('funding_source')
                ->whereNotNull('lease_id')
                ->update(['funding_source' => 'bnpl']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('fuel_vouchers')) {
            return;
        }

        Schema::table('fuel_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('fuel_vouchers', 'funding_source')) {
                $table->dropIndex('fuel_vouchers_user_funding_status_idx');
                $table->dropColumn('funding_source');
            }
        });
    }
};

