<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_achievement_levels', function (Blueprint $table) {
            $table->foreignId('achievement_id')->nullable()->constrained('achievements')->cascadeOnDelete()->after('achievement_level_id');
            $table->string('custom_image')->nullable();
            $table->foreignId('achievement_level_id')->nullable()->change();
        });
    }

    /*u
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_achievement_levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('achievement_id');
            $table->dropColumn('custom_image');
            $table->foreignId('achievement_level_id')->constrained('achievement_levels')->cascadeOnDelete()->nullable('false')->change();
        });
    }
};
