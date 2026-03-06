<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_approvals', function (Blueprint $table) {
            if (!Schema::hasColumn('account_approvals', 'business_address')) {
                $table->string('business_address', 500)->nullable()->after('merchant_franchise_id');
            }
            if (!Schema::hasColumn('account_approvals', 'city')) {
                $table->string('city', 120)->nullable()->after('business_address');
            }
            if (!Schema::hasColumn('account_approvals', 'country')) {
                $table->string('country', 120)->nullable()->after('city');
            }
            if (!Schema::hasColumn('account_approvals', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('country');
            }
            if (!Schema::hasColumn('account_approvals', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_approvals', function (Blueprint $table) {
            foreach (['longitude', 'latitude', 'country', 'city', 'business_address'] as $column) {
                if (Schema::hasColumn('account_approvals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

