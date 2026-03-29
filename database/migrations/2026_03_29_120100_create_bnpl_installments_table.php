<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bnpl_installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bnpl_order_id')->constrained('bnpl_orders')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence'); // 1..N

            $table->timestamp('due_at')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 32)->default('pending'); // pending|paid|overdue|failed|cancelled

            $table->timestamp('paid_at')->nullable();
            $table->string('payment_gateway', 32)->nullable(); // paystack|payshap|...
            $table->string('payment_reference', 120)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['bnpl_order_id', 'sequence']);
            $table->index(['bnpl_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bnpl_installments');
    }
};

