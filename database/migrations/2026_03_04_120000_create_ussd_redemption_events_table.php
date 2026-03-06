<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ussd_redemption_events')) {
            Schema::create('ussd_redemption_events', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->nullable();
                $table->string('service_code')->nullable();
                $table->string('network_code')->nullable();
                $table->string('phone_raw', 64);
                $table->string('phone_normalized', 32);
                $table->string('ussd_text')->nullable();
                $table->string('pump_number', 50)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('merchant_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('fuel_station_id')->nullable()->constrained('fuel_stations')->nullOnDelete();
                $table->foreignId('fuel_voucher_id')->nullable()->constrained('fuel_vouchers')->nullOnDelete();
                $table->string('voucher_code')->nullable();
                $table->string('status', 20)->default('pending');
                $table->string('dispatch_token', 64)->nullable();
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('error_message')->nullable();
                $table->json('receipt_payload')->nullable();
                $table->timestamps();

                $table->index('phone_normalized');
                $table->index('status');
                $table->index('dispatch_token');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ussd_redemption_events');
    }
};
