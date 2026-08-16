<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the three legacy chat tables (conversations, messages, files) that
 * belonged to the old App\Services\Chat stack. That stack and its create
 * migrations were removed when 1:1 chat moved to Modules\Chat. All three
 * tables were verified empty (0 rows) before this drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('files');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Legacy tables are intentionally not recreated — chat now lives in
        // Modules\Chat (chat_rooms / chat_messages). This drop is one-way.
    }
};
