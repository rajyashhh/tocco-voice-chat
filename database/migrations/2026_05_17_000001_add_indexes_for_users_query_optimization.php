<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add composite index on users(lat, long) for location queries
        // This significantly speeds up proximity searches
        Schema::table('users', function (Blueprint $table) {
            $table->index(['lat', 'long'], 'idx_users_lat_long');
        });

        // Add index on deleted_at for soft delete checks
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'idx_users_deleted_at')) {
                $table->index('deleted_at', 'idx_users_deleted_at');
            }
        });

        // Add composite index on profile_user_ignores for faster ignore checks
        Schema::table('profile_user_ignores', function (Blueprint $table) {
            if (!$this->indexExists('profile_user_ignores', 'idx_profile_ignores_lookup')) {
                $table->index(['ignore_user_id', 'user_id'], 'idx_profile_ignores_lookup');
            }
        });

        // Add composite index on profile_user_likes for faster like checks
        Schema::table('profile_user_likes', function (Blueprint $table) {
            if (!$this->indexExists('profile_user_likes', 'idx_profile_likes_lookup')) {
                $table->index(['liked_user_id', 'user_id'], 'idx_profile_likes_lookup');
            }
        });

        // Add index on user_id for reverse lookups
        Schema::table('profile_user_ignores', function (Blueprint $table) {
            if (!$this->indexExists('profile_user_ignores', 'idx_profile_ignores_user_id')) {
                $table->index('user_id', 'idx_profile_ignores_user_id');
            }
        });

        Schema::table('profile_user_likes', function (Blueprint $table) {
            if (!$this->indexExists('profile_user_likes', 'idx_profile_likes_user_id')) {
                $table->index('user_id', 'idx_profile_likes_user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_lat_long');
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'idx_users_deleted_at')) {
                $table->dropIndex('idx_users_deleted_at');
            }
        });

        Schema::table('profile_user_ignores', function (Blueprint $table) {
            if ($this->indexExists('profile_user_ignores', 'idx_profile_ignores_lookup')) {
                $table->dropIndex('idx_profile_ignores_lookup');
            }
        });

        Schema::table('profile_user_likes', function (Blueprint $table) {
            if ($this->indexExists('profile_user_likes', 'idx_profile_likes_lookup')) {
                $table->dropIndex('idx_profile_likes_lookup');
            }
        });

        Schema::table('profile_user_ignores', function (Blueprint $table) {
            if ($this->indexExists('profile_user_ignores', 'idx_profile_ignores_user_id')) {
                $table->dropIndex('idx_profile_ignores_user_id');
            }
        });

        Schema::table('profile_user_likes', function (Blueprint $table) {
            if ($this->indexExists('profile_user_likes', 'idx_profile_likes_user_id')) {
                $table->dropIndex('idx_profile_likes_user_id');
            }
        });
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function indexExists($table, $index)
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select(
            "SELECT COUNT(*) as count
             FROM information_schema.statistics
             WHERE table_schema = ?
             AND table_name = ?
             AND index_name = ?",
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
