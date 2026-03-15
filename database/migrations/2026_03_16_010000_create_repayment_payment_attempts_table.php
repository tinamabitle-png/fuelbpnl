<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repayment_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();

            $table->string('provider', 32)->default('flutterwave');
            $table->string('method', 32)->default('1voucher');

            $table->string('tx_ref', 80)->unique();
            $table->string('flw_ref', 80)->nullable()->unique();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('USD');

            $table->string('status', 24)->default('pending'); // pending|successful|failed

            $table->json('repayment_ids');
            $table->json('meta')->nullable();
            $table->json('provider_response')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repayment_payment_attempts');
    }
};

