<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('milestone_rewards', function (Blueprint $table) {
            $table->string('reward')->nullable()->comment('Reward value or image path')->change();
        });
    }

    public function down(): void
    {
        Schema::table('milestone_rewards', function (Blueprint $table) {
            $table->integer('reward')->nullable()->comment('Reward value')->change();
        });
    }
};
