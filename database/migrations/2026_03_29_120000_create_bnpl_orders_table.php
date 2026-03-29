<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bnpl_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shopper_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reference', 64)->unique();
            $table->string('status', 32)->default('draft'); // draft|pending_checkout|approved|declined|fulfilled|cancelled|disputed|defaulted

            $table->string('title', 120)->nullable();
            $table->text('description')->nullable();

            $table->decimal('amount_total', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('financed_amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('installments_count')->default(4);
            $table->string('currency', 3)->default('ZAR');

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('handover_otp_hash', 120)->nullable();
            $table->timestamp('handover_completed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bnpl_orders');
    }
};

