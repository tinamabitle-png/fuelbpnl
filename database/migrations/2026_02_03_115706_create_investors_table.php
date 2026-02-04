<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestorsTable extends Migration
{
    public function up()
    {
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('registration_number')->unique();
            $table->string('tax_id')->nullable();
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->text('company_address');
            $table->string('city');
            $table->string('country');
            $table->decimal('total_investment_capital', 15, 2)->default(0);
            $table->decimal('available_capital', 15, 2)->default(0);
            $table->decimal('invested_capital', 15, 2)->default(0);
            $table->decimal('interest_earned', 15, 2)->default(0);
            $table->enum('risk_profile', ['conservative', 'moderate', 'aggressive']);
            $table->decimal('minimum_investment_amount', 15, 2)->default(1000);
            $table->decimal('maximum_investment_amount', 15, 2)->default(100000);
            $table->decimal('preferred_interest_rate_min', 5, 2)->default(5);
            $table->decimal('preferred_interest_rate_max', 5, 2)->default(25);
            $table->enum('investment_horizon', ['short_term', 'medium_term', 'long_term']);
            $table->enum('status', ['active', 'pending_approval', 'suspended'])->default('pending_approval');
            $table->integer('credit_score')->default(500);
            $table->integer('investor_score')->default(0);
            $table->boolean('auto_invest_enabled')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('investors');
    }
}