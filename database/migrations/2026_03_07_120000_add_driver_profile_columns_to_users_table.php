<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'home_address')) {
                $table->string('home_address', 500)->nullable()->after('merchant_franchise_id');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city', 120)->nullable()->after('home_address');
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country', 120)->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('country');
            }
            if (!Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('users', 'driver_platform')) {
                $table->string('driver_platform', 60)->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('users', 'driver_platform_other')) {
                $table->string('driver_platform_other', 120)->nullable()->after('driver_platform');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'driver_platform_other',
                'driver_platform',
                'longitude',
                'latitude',
                'country',
                'city',
                'home_address',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

