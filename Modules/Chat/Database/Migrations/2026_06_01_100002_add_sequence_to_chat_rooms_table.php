<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_rooms', 'last_seq')) {
                $table->unsignedBigInteger('last_seq')->default(0)->after('type');
            }
            if (!Schema::hasColumn('chat_rooms', 'last_message_id')) {
                $table->unsignedBigInteger('last_message_id')->nullable()->after('last_seq');
            }
            if (!Schema::hasColumn('chat_rooms', 'last_message_at')) {
                $table->timestamp('last_message_at')->nullable()->after('last_message_id');
            }
        });

        // Widen `type` to VARCHAR(20) so it can carry 'group' alongside 'friends'.
        // Raw statement keeps exact MariaDB type and avoids dbal change() caveats.
        DB::statement("ALTER TABLE `chat_rooms` MODIFY `type` VARCHAR(20) NOT NULL DEFAULT 'friends'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $columns = ['last_message_at', 'last_message_id', 'last_seq'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('chat_rooms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Restore original `type` definition (VARCHAR(255) default 'friends').
        DB::statement("ALTER TABLE `chat_rooms` MODIFY `type` VARCHAR(255) NOT NULL DEFAULT 'friends'");
    }
};
