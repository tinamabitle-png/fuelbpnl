<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            if (!Schema::hasColumn('otps', 'email')) {
                $table->string('email')->nullable()->after('phone');
                $table->index(['email', 'code', 'used']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            if (Schema::hasColumn('otps', 'email')) {
                $table->dropIndex('otps_email_code_used_index');
                $table->dropColumn('email');
            }
        });
    }
};

