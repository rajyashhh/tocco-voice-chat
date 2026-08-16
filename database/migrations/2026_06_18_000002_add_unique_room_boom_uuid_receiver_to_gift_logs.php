<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add a UNIQUE (room_boom_uuid, receiver_id) index on gift_logs.
 *
 * This is the exactly-once guard for lucky-gift supporter rows. The lucky
 * post-job (ProcessLuckyGiftPostJob) dispatches the supporter-row writer
 * (UpdateUserDataWhenSendGift → SendGiftService::sendGift3ForLuckyGift) with a
 * STABLE job_nonce carried as room_boom_uuid; combined with insertOrIgnore, a
 * re-dispatched / retried job writes ZERO duplicate supporter rows.
 *
 * Safe for the existing data and the normal gift path:
 *   - MySQL UNIQUE treats NULLs as DISTINCT, so the many legacy/normal rows with
 *     room_boom_uuid = NULL never collide with each other.
 *   - The normal sendGift3 path mints a fresh UUID per send, so each
 *     (uuid, receiver_id) pair is naturally unique.
 *
 * Idempotent: skips if an index with the same leading columns already exists, and
 * aborts with a clear message if any pre-existing duplicate pair would block it
 * (so the migration never fails opaquely mid-deploy).
 */
return new class extends Migration
{
    private const INDEX_NAME = 'uq_gift_logs_room_boom_uuid_receiver';

    public function up(): void
    {
        if ($this->compositeIndexExists('gift_logs', ['room_boom_uuid', 'receiver_id'])) {
            return;
        }

        $duplicates = DB::table('gift_logs')
            ->select('room_boom_uuid', 'receiver_id', DB::raw('COUNT(*) AS c'))
            ->whereNotNull('room_boom_uuid')
            ->groupBy('room_boom_uuid', 'receiver_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                'gift_logs has existing duplicate (room_boom_uuid, receiver_id) pairs; '
                . 'deduplicate before adding the unique index '
                . self::INDEX_NAME . '.'
            );
        }

        Schema::table('gift_logs', function (Blueprint $table) {
            $table->unique(['room_boom_uuid', 'receiver_id'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if ($this->indexNameExists('gift_logs', self::INDEX_NAME)) {
            Schema::table('gift_logs', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        }
    }

    private function compositeIndexExists(string $table, array $leadingColumns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            $columns = array_values($index['columns'] ?? []);
            if (array_slice($columns, 0, count($leadingColumns)) === $leadingColumns) {
                return true;
            }
        }

        return false;
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
