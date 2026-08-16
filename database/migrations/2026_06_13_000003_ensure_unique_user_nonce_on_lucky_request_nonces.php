<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * B-CON-2 / G4: the whole idempotency contract rests on UNIQUE(user_id, nonce) —
 * without it insertOrIgnore becomes a silent no-op = double-charge. The creating
 * migration (2026_06_12_000003) already declares it; this is a CONFIRM-ONLY guard
 * that adds the index if (and only if) it is somehow missing, and is otherwise a
 * no-op. A CI test (UniqueUserNonceConstraintTest) fails the build if it is absent.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!$this->hasUserNonceUnique()) {
            Schema::table('lucky_request_nonces', function ($table) {
                $table->unique(['user_id', 'nonce']);
            });
        }
    }

    public function down(): void
    {
        // Confirm-only: never drop the idempotency guard on rollback.
    }

    private function hasUserNonceUnique(): bool
    {
        try {
            $indexes = DB::select('SHOW INDEX FROM lucky_request_nonces');
        } catch (\Throwable $e) {
            return true; // can't introspect → assume present (creator declared it)
        }

        $byName = [];
        foreach ($indexes as $idx) {
            $row = (array) $idx;
            $name = $row['Key_name'] ?? null;
            $col  = $row['Column_name'] ?? null;
            $nonUnique = (int) ($row['Non_unique'] ?? 1);
            if ($name === null || $nonUnique !== 0) {
                continue;
            }
            $byName[$name][(int) ($row['Seq_in_index'] ?? 0)] = $col;
        }

        foreach ($byName as $cols) {
            ksort($cols);
            $vals = array_values($cols);
            if (count($vals) === 2 && $vals[0] === 'user_id' && $vals[1] === 'nonce') {
                return true;
            }
        }

        return false;
    }
};
