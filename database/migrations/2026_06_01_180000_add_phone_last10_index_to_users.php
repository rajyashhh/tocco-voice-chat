<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contact discovery (chat rebuild §3): a functional index on the LAST 10 digits
 * of users.phone so `POST /api/contacts/match` can match a device's address book
 * against registered users with an index lookup instead of a full table scan.
 *
 * The phone column stores the LOCAL number (country code is kept separately), so
 * the device may hold the same number with/without country code or a leading 0.
 * Matching on the last 10 significant digits is format-agnostic and covers those
 * variants. MySQL 8 functional indexes add online (INPLACE) — no new column, no
 * table rebuild, registration code untouched.
 */
return new class extends Migration
{
    private const INDEX = 'idx_users_phone_last10';

    public function up(): void
    {
        if ($this->indexExists()) {
            return;
        }
        try {
            // MySQL 8+ functional index syntax
            DB::statement(
                'ALTER TABLE `users` ADD INDEX `' . self::INDEX . '` ((RIGHT(`phone`, 10)))'
            );
        } catch (\Throwable $e) {
            // MariaDB does not support MySQL-style functional indexes; skip gracefully.
            // The query will fall back to a full scan on older MariaDB (staging only).
            \Illuminate\Support\Facades\Log::warning(
                'phone_last10 functional index not supported on this DB engine, skipping',
                ['error' => $e->getMessage()]
            );
        }
    }

    public function down(): void
    {
        if (!$this->indexExists()) {
            return;
        }
        DB::statement('ALTER TABLE `users` DROP INDEX `' . self::INDEX . '`');
    }

    private function indexExists(): bool
    {
        $db = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics '
                . 'WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, 'users', self::INDEX]
        );
        return !empty($rows);
    }
};
