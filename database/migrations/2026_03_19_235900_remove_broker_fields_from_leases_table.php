<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leases')) {
            return;
        }

        Schema::table('leases', function (Blueprint $table) {
            if (Schema::hasColumn('leases', 'broker_id')) {
                $table->dropForeign(['broker_id']);
                $table->dropIndex(['broker_id', 'status']);
                $table->dropColumn([
                    'broker_id',
                    'ownership_type',
                    'management_status',
                    'broker_agreed_at',
                    'broker_notes',
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leases')) {
            return;
        }

        Schema::table('leases', function (Blueprint $table) {
            if (! Schema::hasColumn('leases', 'broker_id')) {
                $table->foreignId('broker_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->string('ownership_type', 32)
                    ->default('platform')
                    ->after('daily_repayment');
                $table->string('management_status', 32)
                    ->default('platform_managed')
                    ->after('ownership_type');
                $table->timestamp('broker_agreed_at')
                    ->nullable()
                    ->after('management_status');
                $table->text('broker_notes')
                    ->nullable()
                    ->after('broker_agreed_at');
                $table->index(['broker_id', 'status']);
            }
        });
    }
};
