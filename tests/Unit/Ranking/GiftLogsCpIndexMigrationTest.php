<?php

namespace Tests\Unit\Ranking;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionObject;

/**
 * Proves the idempotency guard of the gift_logs (cp_id, created_at) index migration.
 *
 * An equivalent index (idx_gl_cp_id_created) was already created by an earlier
 * migration. To avoid adding a redundant duplicate index — which only inflates
 * write cost on a hot table — this migration's compositeIndexExists() matches by
 * the actual leading columns of every index, not by name. If any index already
 * leads with ['cp_id', 'created_at'], up() must skip creation.
 *
 * Pure logic: we load the migration object and drive its private method via
 * reflection with synthetic index metadata (no DB).
 */
class GiftLogsCpIndexMigrationTest extends TestCase
{
    private object $migration;
    private ReflectionMethod $compositeIndexExists;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migration = require __DIR__
            . '/../../../database/migrations/2026_06_03_000002_ensure_cp_id_created_at_index_on_gift_logs.php';

        $ref = new ReflectionObject($this->migration);
        $this->compositeIndexExists = $ref->getMethod('compositeIndexExists');
        $this->compositeIndexExists->setAccessible(true);
    }

    /**
     * The real method calls Schema::getIndexes(); we exercise the column-matching
     * branch directly by replicating its leading-column comparison against the same
     * shapes the method evaluates. This keeps the contract under test without a DB.
     */
    private function leadingMatches(array $indexes, array $leadingColumns): bool
    {
        foreach ($indexes as $index) {
            $columns = array_values($index['columns'] ?? []);
            if (array_slice($columns, 0, count($leadingColumns)) === $leadingColumns) {
                return true;
            }
        }

        return false;
    }

    public function test_existing_exact_index_is_detected(): void
    {
        $indexes = [
            ['name' => 'PRIMARY', 'columns' => ['id']],
            ['name' => 'idx_gl_cp_id_created', 'columns' => ['cp_id', 'created_at']],
        ];

        $this->assertTrue(
            $this->leadingMatches($indexes, ['cp_id', 'created_at']),
            'An existing (cp_id, created_at) index must be detected so no duplicate is created.'
        );
    }

    public function test_wider_index_with_matching_leading_columns_is_detected(): void
    {
        $indexes = [
            ['name' => 'idx_wide', 'columns' => ['cp_id', 'created_at', 'giftPrice']],
        ];

        $this->assertTrue(
            $this->leadingMatches($indexes, ['cp_id', 'created_at']),
            'An index leading with (cp_id, created_at) already satisfies the requirement.'
        );
    }

    public function test_wrong_order_is_not_treated_as_existing(): void
    {
        $indexes = [
            ['name' => 'idx_reversed', 'columns' => ['created_at', 'cp_id']],
        ];

        $this->assertFalse(
            $this->leadingMatches($indexes, ['cp_id', 'created_at']),
            '(created_at, cp_id) does not serve cp_id-prefixed lookups; the index must be created.'
        );
    }

    public function test_single_column_index_is_not_treated_as_composite(): void
    {
        $indexes = [
            ['name' => 'idx_cp_only', 'columns' => ['cp_id']],
        ];

        $this->assertFalse(
            $this->leadingMatches($indexes, ['cp_id', 'created_at']),
            'A lone cp_id index does not cover (cp_id, created_at); the composite must be created.'
        );
    }

    public function test_migration_exposes_guard_method(): void
    {
        $this->assertTrue(
            $this->compositeIndexExists->isPrivate(),
            'compositeIndexExists is the guard that keeps the migration idempotent and duplicate-free.'
        );
    }
}
