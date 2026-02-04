<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', [
                'registration_certificate',
                'tax_certificate', 
                'license_certificate',
                'environmental_clearance',
                'safety_certificate',
                'financial_statement',
                'bank_reference',
                'director_ids',
                'proof_of_address',
                'other'
            ]);
            $table->string('document_path');
            $table->string('document_name');
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['investor_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_documents');
    }
};