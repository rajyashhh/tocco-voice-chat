<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops verified-dead columns (zero logic readers/writers; only boilerplate
 * references, which were removed in the same commit) from users, rooms and
 * profiles, plus the indexes that covered them.
 */
return new class extends Migration
{
    private const USERS_COLUMNS = [
        'locktime', 'cp_card', 'keys_num', 'idno', 'img_2', 'img_3', 'login_ip',
        'is_idcard', 'dashboard_manager_id', 'reel_following_type', 'isOnline',
    ];

    private const ROOMS_COLUMNS = [
        'commission_proportion', 'fresh_time', 'start_hour', 'end_hour', 'week_star',
        'super_uid', 'visitor_count', 'no_of_members', 'openid', 'image_size',
    ];

    private function dropIndexSafe(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table)) return;

        $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (count($exists) === 0) return;

        Schema::table($table, function (Blueprint $t) use ($indexName) {
            $t->dropIndex($indexName);
        });
    }

    private function dropColumnsSafe(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) return;

        $existing = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($table, $c)));
        if (empty($existing)) return;

        Schema::table($table, function (Blueprint $t) use ($existing) {
            $t->dropColumn($existing);
        });
    }

    public function up(): void
    {
        $this->dropIndexSafe('users', 'idx_users_dashboard_manager_id');
        $this->dropIndexSafe('users', 'idx_users_country_online');

        $this->dropColumnsSafe('users', self::USERS_COLUMNS);
        $this->dropColumnsSafe('rooms', self::ROOMS_COLUMNS);
        $this->dropColumnsSafe('profiles', ['image_size']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'locktime')) $table->dateTime('locktime')->nullable();
            if (!Schema::hasColumn('users', 'cp_card')) $table->unsignedInteger('cp_card')->nullable();
            if (!Schema::hasColumn('users', 'keys_num')) $table->unsignedInteger('keys_num')->nullable();
            if (!Schema::hasColumn('users', 'idno')) $table->string('idno', 50)->nullable();
            if (!Schema::hasColumn('users', 'img_2')) $table->string('img_2')->nullable();
            if (!Schema::hasColumn('users', 'img_3')) $table->string('img_3')->nullable();
            if (!Schema::hasColumn('users', 'login_ip')) $table->string('login_ip', 45)->nullable();
            if (!Schema::hasColumn('users', 'is_idcard')) $table->unsignedTinyInteger('is_idcard')->default(0)->nullable();
            if (!Schema::hasColumn('users', 'dashboard_manager_id')) $table->bigInteger('dashboard_manager_id')->default(0);
            if (!Schema::hasColumn('users', 'reel_following_type')) $table->string('reel_following_type')->nullable()->default(0);
            if (!Schema::hasColumn('users', 'isOnline')) $table->boolean('isOnline')->default(false)->nullable();
        });

        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'commission_proportion')) $table->string('commission_proportion')->nullable();
            if (!Schema::hasColumn('rooms', 'fresh_time')) $table->string('fresh_time')->nullable();
            if (!Schema::hasColumn('rooms', 'start_hour')) $table->unsignedTinyInteger('start_hour')->nullable();
            if (!Schema::hasColumn('rooms', 'end_hour')) $table->unsignedTinyInteger('end_hour')->nullable();
            if (!Schema::hasColumn('rooms', 'week_star')) $table->unsignedTinyInteger('week_star')->default(2)->nullable();
            if (!Schema::hasColumn('rooms', 'super_uid')) $table->unsignedTinyInteger('super_uid')->default(2)->nullable();
            if (!Schema::hasColumn('rooms', 'visitor_count')) $table->integer('visitor_count')->nullable()->default(0);
            if (!Schema::hasColumn('rooms', 'no_of_members')) $table->integer('no_of_members')->default(0);
            if (!Schema::hasColumn('rooms', 'openid')) $table->string('openid')->nullable();
            if (!Schema::hasColumn('rooms', 'image_size')) $table->integer('image_size')->nullable();
        });

        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'image_size')) $table->integer('image_size')->nullable();
        });
    }
};
