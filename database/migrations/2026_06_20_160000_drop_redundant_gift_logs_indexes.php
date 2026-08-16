<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave-2 (P1-4): prune redundant indexes on gift_logs to cut write amplification.
 *
 * gift_logs is the hottest write table in the app — every gift send batch-inserts
 * one row per receiver, and the table carried 20 indexes. Each redundant index is
 * pure write cost (extra B-tree maintenance on every insert/update, more buffer
 * pool pressure) with ZERO read benefit, because a wider index already serves the
 * same access path as a leftmost prefix.
 *
 * Each index dropped here is a strict leftmost-prefix of a surviving wider index,
 * so no query plan loses an option (the optimizer transparently uses the wider
 * index's prefix). Net index count goes DOWN (20 -> 17), never up.
 *
 *   1. gift_logs_room_boom_uuid_index (room_boom_uuid)
 *        -> prefix of UNIQUE uq_gift_logs_room_boom_uuid_receiver (room_boom_uuid, receiver_id)
 *   2. idx_gl_created_agency (created_at, agency_id, giftPrice)
 *        -> prefix of idx_gift_logs_created_agency_receiver_price
 *           (created_at, agency_id, receiver_id, giftPrice)  [also covering for SUM(giftPrice)]
 *   3. idx_gl_sender_giftprice (sender_id, giftPrice)
 *        -> covered by gift_logs_sender_id_created_at_giftprice_index
 *           (sender_id, created_at, giftPrice); no query orders/ranges by giftPrice
 *           per sender, so the narrower index has no unique use.
 *
 * Idempotent (drops only if present) and reversible (down() recreates them).
 */
return new class extends Migration
{
    /** index name => columns (used for both drop and reversible recreate) */
    private const REDUNDANT = [
        'gift_logs_room_boom_uuid_index' => ['room_boom_uuid'],
        'idx_gl_created_agency'          => ['created_at', 'agency_id', 'giftPrice'],
        'idx_gl_sender_giftprice'       => ['sender_id', 'giftPrice'],
    ];

    public function up(): void
    {
        foreach (self::REDUNDANT as $name => $columns) {
            if ($this->indexNameExists('gift_logs', $name)) {
                Schema::table('gift_logs', function (Blueprint $table) use ($name) {
                    $table->dropIndex($name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::REDUNDANT as $name => $columns) {
            if (! $this->indexNameExists('gift_logs', $name)) {
                Schema::table('gift_logs', function (Blueprint $table) use ($name, $columns) {
                    $table->index($columns, $name);
                });
            }
        }
    }

    private function indexNameExists(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
