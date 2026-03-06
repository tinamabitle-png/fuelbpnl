<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'id_number')) {
                $table->string('id_number')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'id_document_path')) {
                $table->string('id_document_path')->nullable()->after('id_number');
            }
            if (!Schema::hasColumn('users', 'driver_license_path')) {
                $table->string('driver_license_path')->nullable()->after('id_document_path');
            }
            if (!Schema::hasColumn('users', 'bank_statement_path')) {
                $table->string('bank_statement_path')->nullable()->after('driver_license_path');
            }
            if (!Schema::hasColumn('users', 'payment_method_preference')) {
                $table->string('payment_method_preference')->nullable()->after('bank_statement_path');
            }
            if (!Schema::hasColumn('users', 'payment_account_name')) {
                $table->string('payment_account_name')->nullable()->after('payment_method_preference');
            }
            if (!Schema::hasColumn('users', 'payment_account_number')) {
                $table->string('payment_account_number')->nullable()->after('payment_account_name');
            }
            if (!Schema::hasColumn('users', 'payment_bank_name')) {
                $table->string('payment_bank_name')->nullable()->after('payment_account_number');
            }
            if (!Schema::hasColumn('users', 'payment_branch_code')) {
                $table->string('payment_branch_code')->nullable()->after('payment_bank_name');
            }
            if (!Schema::hasColumn('users', 'id_verification_status')) {
                $table->string('id_verification_status')->default('unverified')->after('payment_branch_code');
            }
            if (!Schema::hasColumn('users', 'id_verified_at')) {
                $table->timestamp('id_verified_at')->nullable()->after('id_verification_status');
            }
            if (!Schema::hasColumn('users', 'id_verification_provider')) {
                $table->string('id_verification_provider')->nullable()->after('id_verified_at');
            }
            if (!Schema::hasColumn('users', 'merchant_franchise_id')) {
                $table->foreignId('merchant_franchise_id')->nullable()->after('id_verification_provider')->constrained('merchant_franchises')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'merchant_franchise_id')) {
                $table->dropConstrainedForeignId('merchant_franchise_id');
            }
        });
    }
};

