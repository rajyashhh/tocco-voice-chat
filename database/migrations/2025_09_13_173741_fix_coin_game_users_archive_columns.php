<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE coin_game_users_archive
                MODIFY user_id bigint(20) unsigned DEFAULT NULL,
                MODIFY coins bigint(20) NOT NULL,
                MODIFY type tinyint(1) NOT NULL,
                MODIFY game_id varchar(255) DEFAULT NULL,
                MODIFY round_id varchar(191) DEFAULT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE coin_game_users_archive
                MODIFY user_id bigint(20) unsigned NOT NULL,
                MODIFY coins bigint(20) DEFAULT NULL,
                MODIFY type varchar(50) DEFAULT NULL,
                MODIFY game_id bigint(20) unsigned DEFAULT NULL,
                MODIFY round_id bigint(20) unsigned DEFAULT NULL
        ");
    }
};
