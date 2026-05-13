<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_decisions') || !Schema::hasColumn('credit_decisions', 'score')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE credit_decisions MODIFY score SMALLINT UNSIGNED NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE credit_decisions ALTER COLUMN score TYPE SMALLINT');
            return;
        }

        Schema::table('credit_decisions', function (Blueprint $table) {
            $table->unsignedSmallInteger('score')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('credit_decisions') || !Schema::hasColumn('credit_decisions', 'score')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE credit_decisions MODIFY score TINYINT UNSIGNED NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE credit_decisions ALTER COLUMN score TYPE SMALLINT');
            return;
        }

        Schema::table('credit_decisions', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->nullable()->change();
        });
    }
};
