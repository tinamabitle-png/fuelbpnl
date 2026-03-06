<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('merchant_franchises')) {
            Schema::create('merchant_franchises', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('logo_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $brands = [
            ['slug' => 'astron-energy', 'name' => 'Astron Energy'],
            ['slug' => 'bp-southern-africa', 'name' => 'BP Southern Africa'],
            ['slug' => 'central-energy-fund', 'name' => 'Central Energy Fund'],
            ['slug' => 'engen', 'name' => 'Engen'],
            ['slug' => 'eskom', 'name' => 'Eskom'],
            ['slug' => 'mulilo', 'name' => 'Mulilo'],
            ['slug' => 'petrosa', 'name' => 'PetroSA'],
            ['slug' => 'puma-energy', 'name' => 'Puma Energy'],
            ['slug' => 'sasol', 'name' => 'Sasol'],
            ['slug' => 'shell-sa', 'name' => 'Shell SA'],
            ['slug' => 'totalenergies', 'name' => 'TotalEnergies'],
            ['slug' => 'vivo-energy', 'name' => 'Vivo Energy'],
        ];

        foreach ($brands as $index => $brand) {
            DB::table('merchant_franchises')->updateOrInsert(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'logo_path' => 'images/brands/' . $brand['slug'] . '.png',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_franchises');
    }
};

