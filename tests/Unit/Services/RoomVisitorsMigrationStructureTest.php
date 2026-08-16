<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Guards the ordering and guards inside the room_visitors uniqueness migration
 * (2026_06_03_000001_unique_and_dedup_room_visitors_table). The correctness of
 * the deadlock fix depends on three things happening in this exact order inside
 * up():
 *
 *   1. DELETE duplicate (room_id, user_id) rows FIRST — otherwise adding the
 *      UNIQUE constraint fails with errno 1062.
 *   2. Drop the redundant indexes (idx_room_visitors_room_id,
 *      idx_room_visitors_room_user) guarded by isset() so re-runs are safe.
 *   3. Add UNIQUE(room_id, user_id) named uq_room_visitors_room_user.
 *
 * Pure source inspection (no DB), so it runs deterministically anywhere.
 */
class RoomVisitorsMigrationStructureTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = file_get_contents(
            __DIR__ . '/../../../database/migrations/2026_06_03_000001_unique_and_dedup_room_visitors_table.php'
        );
    }

    public function test_dedup_delete_runs_before_unique_is_added(): void
    {
        $dedupPos = strpos($this->source, 'DELETE r1 FROM room_visitors');
        $uniquePos = strpos($this->source, "unique(['room_id', 'user_id']");

        $this->assertNotFalse($dedupPos, 'The mandatory dedup DELETE must be present in up().');
        $this->assertNotFalse($uniquePos, 'The UNIQUE(room_id, user_id) add must be present in up().');
        $this->assertLessThan(
            $uniquePos,
            $dedupPos,
            'Dedup must precede the UNIQUE add, otherwise the constraint fails with 1062.'
        );
    }

    public function test_dedup_keeps_smallest_id_per_pair(): void
    {
        // The self-join must delete the larger id (r1.id > r2.id), keeping the
        // smallest id per (room_id, user_id) pair.
        $this->assertStringContainsString('r1.room_id = r2.room_id', $this->source);
        $this->assertStringContainsString('r1.user_id = r2.user_id', $this->source);
        $this->assertStringContainsString('r1.id > r2.id', $this->source);
    }

    public function test_redundant_indexes_are_dropped_with_isset_guard(): void
    {
        $this->assertStringContainsString("isset(\$indexes['idx_room_visitors_room_id'])", $this->source);
        $this->assertStringContainsString("dropIndex('idx_room_visitors_room_id')", $this->source);
        $this->assertStringContainsString("isset(\$indexes['idx_room_visitors_room_user'])", $this->source);
        $this->assertStringContainsString("dropIndex('idx_room_visitors_room_user')", $this->source);
    }

    public function test_unique_constraint_has_expected_name(): void
    {
        $this->assertStringContainsString("'uq_room_visitors_room_user'", $this->source);
    }

    public function test_unique_add_is_guarded_against_reruns(): void
    {
        $this->assertStringContainsString("!isset(\$indexes['uq_room_visitors_room_user'])", $this->source);
    }
}
