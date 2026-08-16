<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('fair_luck_settings')->insert([
            'key' => 'global_vault_negative_limit',
            'value' => '30000',
            'description' => 'The negative balance limit for the global vault wallet.',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        Schema::table('fair_luck_settings', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fair_luck_settings', function (Blueprint $table) {
            //
        });
    }
};
