<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumeToUserBadges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('badge_id')
                ->references('id')
                ->on('badges')
                ->onDelete('cascade');
            $table->index('user_id');
            $table->index('badge_id');
            $table->index('expire');
            $table->index(['user_id', 'expire']);
            $table->index(['badge_id', 'expire']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_badges', function (Blueprint $table) {
             $table->dropForeign(['user_id']);
        $table->dropForeign(['badge_id']);
        });
    }
}
