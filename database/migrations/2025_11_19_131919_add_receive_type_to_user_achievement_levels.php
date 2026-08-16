<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasColumn('user_achievement_levels', 'receive_type')) {
            Schema::table('user_achievement_levels', function (Blueprint $table) {
                $table->string('receive_type')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('user_achievement_levels', 'receive_type')) {
            Schema::table('user_achievement_levels', function (Blueprint $table) {
                $table->dropColumn('receive_type');
            });
        }
    }
};
