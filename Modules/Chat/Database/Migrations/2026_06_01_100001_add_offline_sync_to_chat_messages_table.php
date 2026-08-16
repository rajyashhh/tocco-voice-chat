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
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'client_uuid')) {
                $table->char('client_uuid', 36)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('chat_messages', 'server_seq')) {
                $table->unsignedBigInteger('server_seq')->nullable()->after('client_uuid');
            }
            if (!Schema::hasColumn('chat_messages', 'kind')) {
                $table->enum('kind', ['user', 'system'])->default('user')->after('server_seq');
            }
            if (!Schema::hasColumn('chat_messages', 'system_event')) {
                $table->string('system_event', 40)->nullable()->after('kind');
            }
            if (!Schema::hasColumn('chat_messages', 'system_meta')) {
                $table->json('system_meta')->nullable()->after('system_event');
            }
            if (!Schema::hasColumn('chat_messages', 'reply_to_id')) {
                $table->unsignedBigInteger('reply_to_id')->nullable()->after('system_meta');
            }
            if (!Schema::hasColumn('chat_messages', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('reply_to_id');
            }
            if (!Schema::hasColumn('chat_messages', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('edited_at');
            }
            if (!Schema::hasColumn('chat_messages', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('delivered_at');
            }
        });

        // Widen `message` from VARCHAR(255) to TEXT (long messages were truncated).
        // Done via raw statement to keep exact MariaDB type control and avoid
        // doctrine/dbal change() pitfalls with mixed column types on the same table.
        DB::statement('ALTER TABLE `chat_messages` MODIFY `message` TEXT NULL');

        // Self-referencing FK for replies. Guarded so re-runs are safe.
        if (!$this->foreignKeyExists('chat_messages', 'chat_messages_reply_to_id_foreign')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->foreign('reply_to_id')
                    ->references('id')->on('chat_messages')
                    ->onDelete('set null')->onUpdate('cascade');
            });
        }

        // Unique: one row per (room, client_uuid) — backs Idempotency-Key dedup.
        // MySQL/MariaDB treat NULLs as distinct, so legacy rows with NULL
        // client_uuid do not collide and no backfill is required first.
        if (!$this->indexExists('chat_messages', 'uq_msg_room_client')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->unique(['chat_room_id', 'client_uuid'], 'uq_msg_room_client');
            });
        }

        // Unique: one row per (room, server_seq). NULL server_seq stays distinct.
        if (!$this->indexExists('chat_messages', 'uq_msg_room_seq')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->unique(['chat_room_id', 'server_seq'], 'uq_msg_room_seq');
            });
        }

        // Composite index to back keyset pagination fallback.
        if (!$this->indexExists('chat_messages', 'idx_msg_room_id')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->index(['chat_room_id', 'id'], 'idx_msg_room_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore an explicit single-column index covering the chat_room_id FK
        // BEFORE dropping any composite index prefixed by chat_room_id.
        //
        // up() created uq_msg_room_client / uq_msg_room_seq / idx_msg_room_id —
        // all leftmost-prefixed by chat_room_id. MariaDB silently drops the
        // FK's auto-index (chat_messages_chat_room_id_foreign) once a composite
        // covers the same prefix. Without restoring a dedicated FK-covering
        // index, dropping the last chat_room_id-prefixed index fails with
        // errno 1553 (index "needed in a foreign key constraint"). Re-creating
        // the single-column index first keeps the FK covered through the drops
        // and leaves the table at its exact pre-migration index state.
        if (
            $this->foreignKeyExists('chat_messages', 'chat_messages_chat_room_id_foreign')
            && !$this->indexExists('chat_messages', 'chat_messages_chat_room_id_foreign')
        ) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->index('chat_room_id', 'chat_messages_chat_room_id_foreign');
            });
        }

        if ($this->indexExists('chat_messages', 'idx_msg_room_id')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropIndex('idx_msg_room_id');
            });
        }
        if ($this->indexExists('chat_messages', 'uq_msg_room_seq')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropUnique('uq_msg_room_seq');
            });
        }
        if ($this->indexExists('chat_messages', 'uq_msg_room_client')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropUnique('uq_msg_room_client');
            });
        }
        if ($this->foreignKeyExists('chat_messages', 'chat_messages_reply_to_id_foreign')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropForeign('chat_messages_reply_to_id_foreign');
            });
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            $columns = [
                'read_at',
                'delivered_at',
                'edited_at',
                'reply_to_id',
                'system_meta',
                'system_event',
                'kind',
                'server_seq',
                'client_uuid',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('chat_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Restore `message` to its original VARCHAR(255) nullable definition.
        DB::statement('ALTER TABLE `chat_messages` MODIFY `message` VARCHAR(255) NULL');
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.table_constraints')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
