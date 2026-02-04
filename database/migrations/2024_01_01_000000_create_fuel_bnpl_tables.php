<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Disable foreign key checks temporarily
        Schema::disableForeignKeyConstraints();

        // 1. Create fuel stations table (independent table)
        Schema::create('fuel_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company');
            $table->string('license_number')->unique();
            $table->string('address');
            $table->string('city');
            $table->string('country')->default('Kenya');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('contact_person');
            $table->string('contact_phone');
            $table->string('contact_email')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('wallet_balance', 15, 2)->default(0);
            $table->decimal('total_settlements', 15, 2)->default(0);
            $table->timestamps();
            
            $table->index('status');
            $table->index(['latitude', 'longitude']);
            $table->index('company');
        });

        // 2. Create wallets table
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->decimal('total_credit_used', 15, 2)->default(0);
            $table->decimal('total_repayments', 15, 2)->default(0);
            $table->string('currency', 3)->default('KES');
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index('outstanding_balance');
        });

        // 3. Create credit limits table
        Schema::create('credit_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('limit', 15, 2)->default(5000);
            $table->decimal('used', 15, 2)->default(0);
            $table->date('review_date');
            $table->enum('status', ['active', 'frozen', 'under_review'])->default('active');
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index('review_date');
        });

        // 4. Create leases table
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->decimal('interest_amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->integer('term_days');
            $table->decimal('daily_repayment', 15, 2);
            $table->enum('status', ['active', 'completed', 'defaulted', 'cancelled'])->default('active');
            $table->dateTime('issued_at')->useCurrent();
            $table->date('due_date');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('defaulted_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });

        // 5. Create settlements table
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_station_id')->constrained('fuel_stations')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->integer('voucher_count');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('reference')->unique();
            $table->date('settlement_date');
            $table->dateTime('processed_at')->nullable();
            $table->string('payment_method')->default('bank_transfer');
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['fuel_station_id', 'status']);
            $table->index('settlement_date');
            $table->index('reference');
        });

        // 6. Create fuel vouchers table
        Schema::create('fuel_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('qr_code')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('fuel_station_id')->constrained('fuel_stations')->onDelete('cascade');
            $table->foreignId('lease_id')->nullable()->constrained('leases')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('liters', 8, 3);
            $table->enum('fuel_type', ['petrol', 'diesel', 'super']);
            $table->enum('status', ['issued', 'redeemed', 'expired', 'cancelled'])->default('issued');
            $table->dateTime('issued_at')->useCurrent();
            $table->dateTime('redeemed_at')->nullable();
            $table->dateTime('expires_at');
            $table->foreignId('settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
            $table->string('transaction_reference')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['fuel_station_id', 'status']);
            $table->index('expires_at');
            $table->index('redeemed_at');
        });

        // 7. Create repayments table
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained('leases')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->date('paid_at')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue', 'defaulted'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->timestamps();
            
            $table->index(['lease_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('due_date');
            $table->index('paid_at');
        });

        // 8. Create wallet transactions table
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description');
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['wallet_id', 'type']);
            $table->index('reference');
            $table->index('created_at');
        });

        // 9. Create audit logs table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });

        // 10. Create OTPs table
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone');
            $table->string('code', 6);
            $table->enum('purpose', ['registration', 'login', 'reset_password', 'transaction']);
            $table->boolean('used')->default(false);
            $table->dateTime('expires_at');
            $table->timestamps();
            
            $table->index(['phone', 'code', 'used']);
            $table->index('expires_at');
        });

        // 11. Create devices table
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_id');
            $table->string('device_name');
            $table->enum('device_type', ['android', 'ios', 'web']);
            $table->string('fcm_token')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'device_id']);
            $table->index('device_id');
        });

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        
        Schema::dropIfExists('devices');
        Schema::dropIfExists('otps');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('repayments');
        Schema::dropIfExists('fuel_vouchers');
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('leases');
        Schema::dropIfExists('credit_limits');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('fuel_stations');
        
        Schema::enableForeignKeyConstraints();
    }
};