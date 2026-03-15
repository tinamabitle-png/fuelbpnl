<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('virtual_cards')) {
            return;
        }

        Schema::table('virtual_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_cards', 'brand')) {
                $table->string('brand', 64)->default('generic')->after('provider_card_id');
                $table->index(['user_id', 'brand', 'status']);
            }
        });

        DB::table('virtual_cards')->whereNull('brand')->update(['brand' => 'generic']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('virtual_cards')) {
            return;
        }

        Schema::table('virtual_cards', function (Blueprint $table) {
            if (Schema::hasColumn('virtual_cards', 'brand')) {
                $table->dropIndex(['user_id', 'brand', 'status']);
                $table->dropColumn('brand');
            }
        });
    }
};

