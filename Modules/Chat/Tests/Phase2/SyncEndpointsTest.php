<?php

namespace Modules\Chat\Tests\Phase2;

use Illuminate\Support\Facades\DB;
use Modules\Chat\Http\Controllers\SyncController;

/**
 * Phase 2 §6.5 — REST sync surface.
 *
 * Covers the three contract endpoints plus the access boundary:
 *  - GET /sync/rooms          : per-room last_seq + my_last_read_seq + last msg, keyset.
 *  - messages?since_seq=N      : forward gap-fill (ASC, server_seq > N).
 *  - messages?before_seq=N     : backward keyset history (DESC, server_seq < N).
 *  - membership guard          : non-members get 403; cleared_seq hides history.
 */
class SyncEndpointsTest extends Phase2SyncTestCase
{
    private function controller(): SyncController
    {
        return new SyncController();
    }

    // ---------------------------------------------------------------------
    // GET /api/v1/sync/rooms
    // ---------------------------------------------------------------------

    public function test_rooms_list_returns_last_seq_and_my_last_read_seq(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);

        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1, ['last_read_seq' => 2]);
        $this->addMember($room, 2);

        $this->addMessage($room, 2, 'a'); // seq 1
        $this->addMessage($room, 1, 'b'); // seq 2
        $this->addMessage($room, 2, 'c'); // seq 3

        $response = $this->controller()->rooms($this->requestAs(1));
        $body = $this->decode($response);

        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['data']);

        $row = $body['data'][0];
        $this->assertSame($room, $row['room_id']);
        $this->assertSame(3, $row['last_seq']);
        $this->assertSame(2, $row['my_last_read_seq']);
        // unread = last_seq - my_last_read_seq (O(1)).
        $this->assertSame(1, $row['unread_count']);
        $this->assertSame('dm', $row['type']);
        // last_message denormalized header is present and seq-keyed.
        $this->assertSame(3, $row['last_message']['server_seq']);
        $this->assertSame('c', $row['last_message']['message']);
    }

    public function test_rooms_list_only_returns_callers_rooms(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $this->seedUser(3);

        $mine = $this->makeRoom(1, 2);
        $this->addMember($mine, 1);
        $this->addMember($mine, 2);

        $theirs = $this->makeRoom(2, 3);
        $this->addMember($theirs, 2);
        $this->addMember($theirs, 3);

        $body = $this->decode($this->controller()->rooms($this->requestAs(1)));

        $ids = array_column($body['data'], 'room_id');
        $this->assertSame([$mine], $ids);
    }

    public function test_rooms_list_keyset_pagination_with_before_room_id(): void
    {
        $this->seedUser(1);
        // Five rooms for user 1.
        $roomIds = [];
        for ($i = 0; $i < 5; $i++) {
            $r = $this->makeRoom(1, null);
            $this->addMember($r, 1);
            $roomIds[] = $r;
        }

        // Page 1: newest two (highest ids first).
        $page1 = $this->decode($this->controller()->rooms($this->requestAs(1, ['limit' => 2])));
        $this->assertCount(2, $page1['data']);
        $this->assertTrue($page1['paginates']['has_more']);
        $this->assertSame([$roomIds[4], $roomIds[3]], array_column($page1['data'], 'room_id'));

        $cursor = $page1['paginates']['next_before_room_id'];
        $this->assertSame($roomIds[3], $cursor);

        // Page 2 via cursor.
        $page2 = $this->decode($this->controller()->rooms($this->requestAs(1, [
            'limit' => 2,
            'before_room_id' => $cursor,
        ])));
        $this->assertSame([$roomIds[2], $roomIds[1]], array_column($page2['data'], 'room_id'));
        $this->assertTrue($page2['paginates']['has_more']);

        // Page 3: last room, no more.
        $page3 = $this->decode($this->controller()->rooms($this->requestAs(1, [
            'limit' => 2,
            'before_room_id' => $page2['paginates']['next_before_room_id'],
        ])));
        $this->assertSame([$roomIds[0]], array_column($page3['data'], 'room_id'));
        $this->assertFalse($page3['paginates']['has_more']);
        $this->assertNull($page3['paginates']['next_before_room_id']);
    }

    public function test_rooms_list_falls_back_to_legacy_participant_columns(): void
    {
        // Room created on the legacy 1:1 model: user_id/user_id2 set, but NOT yet
        // backfilled into chat_room_members. The caller must still see it.
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2);
        $this->addMessage($room, 2, 'legacy'); // seq 1

        $body = $this->decode($this->controller()->rooms($this->requestAs(1)));

        $this->assertCount(1, $body['data']);
        $this->assertSame($room, $body['data'][0]['room_id']);
        // No membership row => read cursor unknown => fully unread.
        $this->assertSame(0, $body['data'][0]['my_last_read_seq']);
        $this->assertSame(1, $body['data'][0]['unread_count']);
    }

    // ---------------------------------------------------------------------
    // GET /api/v1/rooms/{id}/messages — membership guard
    // ---------------------------------------------------------------------

    public function test_messages_forbidden_for_non_member(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $this->seedUser(3);

        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1);
        $this->addMember($room, 2);
        $this->addMessage($room, 1, 'secret');

        $response = $this->controller()->messages($this->requestAs(3), $room);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($this->decode($response)['success']);
    }

    public function test_messages_404_for_missing_room(): void
    {
        $this->seedUser(1);
        $response = $this->controller()->messages($this->requestAs(1), 999999);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_banned_member_is_not_allowed(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        // user 2 is ALSO the legacy user_id2 — the banned membership row must win
        // over the legacy participant match (regression guard).
        $room = $this->makeRoom(1, 2, 'group');
        $this->addMember($room, 1);
        $this->addMember($room, 2, ['status' => 'banned']);
        $this->addMessage($room, 1, 'x');

        $response = $this->controller()->messages($this->requestAs(2), $room);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_muted_member_can_still_read(): void
    {
        // Muting restricts posting, not reading (plan §5.2).
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2, 'group');
        $this->addMember($room, 1);
        $this->addMember($room, 2, ['status' => 'muted']);
        $this->addMessage($room, 1, 'hello'); // seq 1

        $response = $this->controller()->messages($this->requestAs(2, ['since_seq' => 0]), $room);
        $this->assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame([1], array_column($body['data'], 'server_seq'));
    }

    public function test_left_member_room_excluded_from_rooms_list(): void
    {
        // A user who left a legacy 1:1 room (membership row status=left) must not
        // see it in the list even though the legacy column still names them.
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1);
        $this->addMember($room, 2, ['status' => 'left']);
        $this->addMessage($room, 1, 'x');

        $body = $this->decode($this->controller()->rooms($this->requestAs(2)));
        $this->assertCount(0, $body['data']);

        // The other (active) participant still sees it.
        $body1 = $this->decode($this->controller()->rooms($this->requestAs(1)));
        $this->assertCount(1, $body1['data']);
    }

    // ---------------------------------------------------------------------
    // since_seq (gap-fill / recovery fallback)
    // ---------------------------------------------------------------------

    public function test_messages_since_seq_returns_ascending_after_seq(): void
    {
        $this->seedUser(1);
        $this->seedUser(2);
        $room = $this->makeRoom(1, 2);
        $this->addMember($room, 1);
        $this->addMember($room, 2);

        foreach (['a', 'b', 'c', 'd', 'e'] as $m) {
            $this->addMessage($room, 1, $m);
        }

        $body = $this->decode($this->controller()->messages(
            $this->requestAs(1, ['since_seq' => 2]),
            $room
        ));

        $seqs = array_column($body['data'], 'server_seq');
        $this->assertSame([3, 4, 5], $seqs, 'since_seq returns server_seq > N, ascending');
        $this->assertSame('since', $body['paginates']['mode']);
        $this->assertSame(5, $body['paginates']['room_last_seq']);
    }

    public function test_messages_since_seq_respects_limit(): void
    {
        $this->seedUser(1);
        $room = $this->makeRoom(1, null);
        $this->addMember($room, 1);
        for ($i = 0; $i < 10; $i++) {
            $this->addMessage($room, 1, "m{$i}");
        }

        $body = $this->decode($this->controller()->messages(
            $this->requestAs(1, ['since_seq' => 0, 'limit' => 4]),
            $room
        ));

        $this->assertSame([1, 2, 3, 4], array_column($body['data'], 'server_seq'));
        $this->assertTrue($body['paginates']['has_more']);
    }

    // ---------------------------------------------------------------------
    // before_seq (backward keyset history)
    // ---------------------------------------------------------------------

    public function test_messages_before_seq_returns_descending_older(): void
    {
        $this->seedUser(1);
        $room = $this->makeRoom(1, null);
        $this->addMember($room, 1);
        for ($i = 0; $i < 6; $i++) {
            $this->addMessage($room, 1, "m{$i}"); // seq 1..6
        }

        $body = $this->decode($this->controller()->messages(
            $this->requestAs(1, ['before_seq' => 5, 'limit' => 30]),
            $room
        ));

        // server_seq < 5, DESC.
        $this->assertSame([4, 3, 2, 1], array_column($body['data'], 'server_seq'));
        $this->assertSame('before', $body['paginates']['mode']);
    }

    public function test_messages_default_returns_newest_page_descending(): void
    {
        $this->seedUser(1);
        $room = $this->makeRoom(1, null);
        $this->addMember($room, 1);
        for ($i = 0; $i < 5; $i++) {
            $this->addMessage($room, 1, "m{$i}"); // seq 1..5
        }

        $body = $this->decode($this->controller()->messages(
            $this->requestAs(1, ['limit' => 2]),
            $room
        ));

        $this->assertSame([5, 4], array_column($body['data'], 'server_seq'));
        $this->assertTrue($body['paginates']['has_more']);
    }

    // ---------------------------------------------------------------------
    // cleared_seq (per-member clear point)
    // ---------------------------------------------------------------------

    public function test_messages_hidden_below_cleared_seq(): void
    {
        $this->seedUser(1);
        $room = $this->makeRoom(1, null);
        // Member cleared history up to seq 3 -> only seq > 3 visible.
        $this->addMember($room, 1, ['cleared_seq' => 3]);
        for ($i = 0; $i < 5; $i++) {
            $this->addMessage($room, 1, "m{$i}"); // seq 1..5
        }

        $sinceBody = $this->decode($this->controller()->messages(
            $this->requestAs(1, ['since_seq' => 0]),
            $room
        ));
        $this->assertSame([4, 5], array_column($sinceBody['data'], 'server_seq'));

        $beforeBody = $this->decode($this->controller()->messages(
            $this->requestAs(1, ['before_seq' => 100]),
            $room
        ));
        $this->assertSame([5, 4], array_column($beforeBody['data'], 'server_seq'));
    }

    public function test_messages_only_include_sequenced_rows(): void
    {
        // A legacy message with NULL server_seq must never leak into the sync
        // stream (it has no stable position for the client).
        $this->seedUser(1);
        $room = $this->makeRoom(1, null);
        $this->addMember($room, 1);

        DB::table('chat_messages')->insert([
            'chat_room_id' => $room, 'user_id' => 1, 'message' => 'unsequenced',
            'server_seq' => null, 'status' => 'sended', 'type' => 'message',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->addMessage($room, 1, 'sequenced'); // seq 1

        $body = $this->decode($this->controller()->messages(
            $this->requestAs(1, ['since_seq' => 0]),
            $room
        ));

        $this->assertSame([1], array_column($body['data'], 'server_seq'));
        $this->assertCount(1, $body['data']);
    }
}
