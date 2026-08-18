<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_team_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('plan_slug');
            $table->string('plan_name');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('ZAR');
            $table->string('billing_cycle')->default('monthly');
            $table->unsignedInteger('loan_book_limit')->nullable();
            $table->string('status')->default('pending');
            $table->string('paystack_reference')->nullable()->unique();
            $table->string('paystack_access_code')->nullable();
            $table->text('paystack_authorization_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index(['plan_slug', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_team_subscriptions');
    }
};
