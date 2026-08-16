<?php

namespace Tests\Unit\Services;

use App\Repositories\RoomVisitorRepository;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Proves RoomVisitorRepository::addVisitor performs a single atomic
 * insertOrIgnore against the UNIQUE(room_id, user_id) constraint, with NO
 * wrapping DB::transaction and NO SELECT-then-INSERT (firstOrCreate). This is
 * the change that removes the gap/insert-intention locks that caused the InnoDB
 * deadlock cycle on concurrent join for the same room.
 */
class RoomVisitorRepositoryAtomicTest extends TestCase
{
    public function test_add_visitor_uses_insert_or_ignore_without_transaction(): void
    {
        // If addVisitor ever reintroduces a wrapping transaction, this fails.
        DB::shouldReceive('transaction')->never();

        $builder = Mockery::mock();
        $builder->shouldReceive('insertOrIgnore')
            ->once()
            ->with(Mockery::on(function ($row) {
                return ($row['room_id'] ?? null) === 7309
                    && ($row['user_id'] ?? null) === 32119
                    && array_key_exists('created_at', $row)
                    && array_key_exists('updated_at', $row);
            }))
            ->andReturn(1);

        DB::shouldReceive('table')->with('room_visitors')->once()->andReturn($builder);

        $repo = new RoomVisitorRepository();
        $result = $repo->addVisitor(7309, 32119);

        $this->assertTrue($result);
    }

    public function test_add_visitor_source_has_no_first_or_create_or_transaction(): void
    {
        $source = file_get_contents(
            __DIR__ . '/../../../app/Repositories/RoomVisitorRepository.php'
        );

        // Isolate the addVisitor method body.
        $start = strpos($source, 'public function addVisitor(');
        $next = strpos($source, 'public function removeVisitor(', $start);
        $body = substr($source, $start, $next - $start);

        $this->assertStringNotContainsString('DB::transaction', $body, 'addVisitor must not wrap the insert in a transaction.');
        $this->assertStringNotContainsString('firstOrCreate', $body, 'addVisitor must not use firstOrCreate (SELECT-then-INSERT).');
        $this->assertStringContainsString('insertOrIgnore', $body, 'addVisitor must use the atomic insertOrIgnore.');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
