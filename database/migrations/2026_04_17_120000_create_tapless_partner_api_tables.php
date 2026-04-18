<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tapless_api_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('public_key')->unique();
            $table->text('secret_encrypted');
            $table->text('webhook_url')->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tapless_api_partner_fuel_station', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tapless_api_partner_id')
                ->constrained('tapless_api_partners')
                ->cascadeOnDelete();
            $table->foreignId('fuel_station_id')
                ->constrained('fuel_stations')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tapless_api_partner_id', 'fuel_station_id'], 'tapless_partner_station_unique');
        });

        Schema::create('tapless_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();
            $table->foreignId('partner_id')
                ->constrained('tapless_api_partners')
                ->cascadeOnDelete();
            $table->foreignId('fuel_station_id')
                ->constrained('fuel_stations')
                ->cascadeOnDelete();
            $table->foreignId('fuel_voucher_id')
                ->nullable()
                ->constrained('fuel_vouchers')
                ->nullOnDelete();
            $table->string('external_reference');
            $table->string('scan_input')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('ZAR');
            $table->string('status')->default('created');
            $table->decimal('device_latitude', 11, 8)->nullable();
            $table->decimal('device_longitude', 11, 8)->nullable();
            $table->string('pump_number')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->json('metadata')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['partner_id', 'external_reference'], 'tapless_partner_external_reference_unique');
            $table->index(['partner_id', 'status']);
            $table->index(['fuel_station_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tapless_payment_intents');
        Schema::dropIfExists('tapless_api_partner_fuel_station');
        Schema::dropIfExists('tapless_api_partners');
    }
};
