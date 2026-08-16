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
            [
                'key' => 'fair_luck_app_fee_rate',
                'value' => '0.05',
                'description' => 'Percentage of the gift taken by the application (e.g., 0.05 for 5%).',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'fair_luck_receiver_fee_rate',
                'value' => '0.05',
                'description' => 'Percentage of the gift given to the receiver/host (e.g., 0.05 for 5%).',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
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
