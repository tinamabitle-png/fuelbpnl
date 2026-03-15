<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('virtual_cards')) {
            return;
        }

        Schema::table('virtual_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_cards', 'masked_pan')) {
                $table->string('masked_pan', 32)->nullable()->after('provider_card_id');
            }
            if (!Schema::hasColumn('virtual_cards', 'last4')) {
                $table->string('last4', 4)->nullable()->after('masked_pan');
            }
            if (!Schema::hasColumn('virtual_cards', 'expiry_month')) {
                $table->unsignedTinyInteger('expiry_month')->nullable()->after('last4');
            }
            if (!Schema::hasColumn('virtual_cards', 'expiry_year')) {
                $table->unsignedSmallInteger('expiry_year')->nullable()->after('expiry_month');
            }
            if (!Schema::hasColumn('virtual_cards', 'card_scheme')) {
                $table->string('card_scheme', 16)->nullable()->after('expiry_year');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('virtual_cards')) {
            return;
        }

        Schema::table('virtual_cards', function (Blueprint $table) {
            $columns = ['masked_pan', 'last4', 'expiry_month', 'expiry_year', 'card_scheme'];
            $existing = array_values(array_filter($columns, static fn (string $c): bool => Schema::hasColumn('virtual_cards', $c)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};

