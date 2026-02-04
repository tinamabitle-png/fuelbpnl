<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaseInvestmentsTable extends Migration
{
    public function up()
    {
        Schema::create('lease_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->foreignId('investor_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_invested', 15, 2);
            $table->decimal('percentage_ownership', 5, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->decimal('expected_interest', 15, 2);
            $table->decimal('interest_earned', 15, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'defaulted', 'cancelled'])->default('active');
            $table->dateTime('investment_date');
            $table->dateTime('maturity_date');
            $table->dateTime('expected_maturity_date');
            $table->dateTime('actual_maturity_date')->nullable();
            $table->decimal('return_on_investment', 5, 2)->default(0);
            $table->enum('payment_schedule', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->boolean('auto_reinvest')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lease_investments');
    }
}
