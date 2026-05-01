<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bank_statement_uploads')) {
            Schema::create('bank_statement_uploads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('source', 60)->default('web');
                $table->string('source_reference')->nullable();
                $table->string('original_filename')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('temporary_path')->nullable();
                $table->string('status', 30)->default('pending');
                $table->string('ocr_provider', 60)->nullable();
                $table->string('ocr_processor_type', 60)->nullable();
                $table->string('ocr_region', 60)->nullable();
                $table->decimal('ocr_confidence', 5, 2)->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('credit_consents')) {
            Schema::create('credit_consents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('source', 60);
                $table->text('scope');
                $table->timestamp('granted_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->string('evidence_ref')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'source']);
                $table->index(['user_id', 'granted_at']);
            });
        }

        if (!Schema::hasTable('credit_scores')) {
            Schema::create('credit_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedSmallInteger('score');
                $table->string('band', 30);
                $table->string('version', 50)->default('rules-v1');
                $table->json('reasons_json')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('scored_at')->useCurrent();
                $table->timestamps();

                $table->index(['user_id', 'scored_at']);
                $table->index(['band', 'version']);
            });
        }

        if (!Schema::hasTable('credit_decisions')) {
            Schema::create('credit_decisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('upload_id')->nullable()->constrained('bank_statement_uploads')->nullOnDelete();
                $table->foreignId('score_id')->nullable()->constrained('credit_scores')->nullOnDelete();
                $table->unsignedTinyInteger('score')->nullable();
                $table->enum('decision', ['approve', 'review', 'deny'])->nullable();
                $table->string('application_type', 60)->default('voucher_bnpl');
                $table->json('reasons')->nullable();
                $table->json('explanation_json')->nullable();
                $table->string('model_version')->default('rules-v1');
                $table->string('policy_version', 50)->default('policy-v1');
                $table->string('source')->default('internal_credit_engine');
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'decision']);
                $table->index(['application_type', 'decision']);
                $table->index(['user_id', 'decided_at']);
            });
        }

        if (!Schema::hasTable('credit_audit_logs')) {
            Schema::create('credit_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100);
                $table->string('entity_type', 120);
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('payload_json')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['entity_type', 'entity_id']);
                $table->index(['actor_id', 'action']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_audit_logs');
        Schema::dropIfExists('credit_decisions');
        Schema::dropIfExists('credit_scores');
        Schema::dropIfExists('credit_consents');
        Schema::dropIfExists('bank_statement_uploads');
    }
};
