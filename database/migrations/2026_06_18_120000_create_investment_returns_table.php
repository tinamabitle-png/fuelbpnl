<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('investment_returns')) {
            return;
        }

        Schema::create('investment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_investment_id')->constrained('lease_investments')->cascadeOnDelete();
            $table->string('type', 40)->default('interest');
            $table->decimal('amount', 15, 2);
            $table->dateTime('payment_date')->nullable();
            $table->string('reference')->nullable()->unique();
            $table->string('status', 40)->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lease_investment_id', 'status']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_returns');
    }
};
