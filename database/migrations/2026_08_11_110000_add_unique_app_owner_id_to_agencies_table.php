<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce one-owner-per-user at the database level.
     *
     * The admin ownership-transfer flow checks uniqueness in PHP
     * (AgencyController::addSavingLogic) but that read is not part of the
     * row lock taken in update(), so two concurrent transfers can each read
     * "no duplicate" and both write the same app_owner_id -> duplicate
     * ownership. A UNIQUE index makes the losing transaction fail atomically
     * with error 1062.
     *
     * NOTE (MySQL): a UNIQUE index allows multiple NULL values, so agencies
     * with no owner yet (app_owner_id IS NULL) are unaffected.
     *
     * WARNING for the operator (Tariq): if the live table already contains
     * duplicate non-NULL app_owner_id values, creating the index fails with
     * 1062. Deduplicate first, then re-run. The up() below turns that failure
     * into a clear, actionable message instead of a raw stack trace.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('agencies', 'app_owner_id')) {
            return;
        }

        if ($this->hasIndex('agencies', 'uq_agencies_app_owner_id')) {
            return;
        }

        try {
            Schema::table('agencies', function (Blueprint $table) {
                $table->unique('app_owner_id', 'uq_agencies_app_owner_id');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new \RuntimeException(
                    'Cannot add UNIQUE index on agencies.app_owner_id: duplicate '
                    . 'owner values already exist. Deduplicate them '
                    . '(each non-NULL app_owner_id must map to a single agency) '
                    . 'and re-run this migration.',
                    0,
                    $e
                );
            }

            throw $e;
        }
    }

    public function down(): void
    {
        if (! $this->hasIndex('agencies', 'uq_agencies_app_owner_id')) {
            return;
        }

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropUnique('uq_agencies_app_owner_id');
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
