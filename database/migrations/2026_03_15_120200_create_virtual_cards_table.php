<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('provider', 32)->default('flutterwave');
            $table->string('provider_card_id', 128)->nullable()->unique();
            $table->string('label', 64)->nullable();
            $table->string('currency', 3)->default('ZAR');
            $table->enum('status', ['active', 'frozen', 'terminated'])->default('active');
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_cards');
    }
};

