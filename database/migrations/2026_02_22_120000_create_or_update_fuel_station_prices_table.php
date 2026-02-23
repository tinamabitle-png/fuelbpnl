<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fuel_station_prices')) {
            Schema::create('fuel_station_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fuel_station_id')->constrained('fuel_stations')->cascadeOnDelete();
                $table->enum('fuel_type', ['petrol', 'diesel', 'super']);
                $table->decimal('price_per_liter', 10, 2);
                $table->string('currency', 3)->default('ZAR');
                $table->string('source', 32)->default('manual');
                $table->boolean('is_active')->default(true);
                $table->timestamp('effective_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['fuel_station_id', 'fuel_type']);
                $table->index(['fuel_station_id', 'fuel_type', 'effective_at']);
                $table->index('source');
            });

            return;
        }

        Schema::table('fuel_station_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('fuel_station_prices', 'currency')) {
                $table->string('currency', 3)->default('ZAR')->after('price_per_liter');
            }

            if (!Schema::hasColumn('fuel_station_prices', 'source')) {
                $table->string('source', 32)->default('manual')->after('currency');
            }

            if (!Schema::hasColumn('fuel_station_prices', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('source');
            }

            if (!Schema::hasColumn('fuel_station_prices', 'effective_at')) {
                $table->timestamp('effective_at')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('fuel_station_prices', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('effective_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('fuel_station_prices', 'meta')) {
                $table->json('meta')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('fuel_station_prices', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('fuel_station_prices', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left blank to avoid dropping/altering shared pricing tables on rollback.
    }
};
