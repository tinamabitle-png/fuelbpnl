<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('form', 80);
            $table->string('action', 80);
            $table->string('outcome', 40)->nullable(); // ok|fail|...

            $table->string('ip', 64)->nullable();
            $table->string('country_code', 8)->nullable(); // from CF-IPCountry if available
            $table->string('submitted_city', 120)->nullable(); // from form input when present
            $table->string('submitted_country', 120)->nullable(); // from form input when present

            $table->string('path', 255)->nullable();
            $table->string('referer', 255)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamps();

            $table->index(['created_at', 'id']);
            $table->index(['form', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_interactions');
    }
};

