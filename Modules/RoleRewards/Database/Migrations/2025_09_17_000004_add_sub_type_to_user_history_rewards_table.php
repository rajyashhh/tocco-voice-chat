<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('user_history_rewards', function (Blueprint $table) {
            if (!Schema::hasColumn('user_history_rewards', 'sub_type')) {
                $table->string('sub_type')->nullable()->comment('Sub type of the reward');
            }

        });
    }

    public function down(): void
    {
        Schema::table('user_history_rewards', function (Blueprint $table) {
            $table->dropColumn('sub_type');
        });
    }
};
