<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\MessageReplay;

/**
 * Phase 6 — the GROUP MESSAGE SEND HTTP ENDPOINT
 * (POST /api/groups/{group}/messages -> GroupController::sendMessage).
 *
 * This is the group twin of the 1:1 ChatMessagesController::store. It persists the
 * message row, then hands it to the SAME MessageService::handleMessage fork that the
 * 1:1 path uses — for a type='group' room that fork runs the §5.2 posting gate
 * (GroupService::assertCanPost), allocates the per-room server_seq and dispatches
 * BroadcastGroupMessage. We drive the REAL controller from the container with a REAL
 * authenticated user (exactly what the route binds after auth:sanctum) and assert,
 * per case:
 *
 *   - the back end is the guard: a non-member / muted / only_admins_post member is
 *     rejected 403 and NO row is written (pre-gated before persist, no orphan);
 *   - an authorized member's send persists with a server_seq and fans out to the
 *     other active members' user:#{id} channels in ONE Centrifugo /broadcast;
 *   - the response is the project envelope {success, message, data};
 *   - idempotency: a repeat Idempotency-Key returns the stored message and never
 *     creates a duplicate row (the unique index is the hard guarantee);
 *   - reply_to must reference a message in THIS group's room (cross-room reply 422);
 *   - the 1:1 send path is untouched (separate path, separate broadcaster).
 */
class GroupSendMessageEndpointTest extends GroupEndpointHttpTestCase
{
    private const API_URL = 'http://centrifugo.test:8000/api';

    protected function setUp(): void
    {
        parent::setUp();

        // Drive the REAL Centrifugo broadcaster so the group fan-out is an
        // observable HTTP /broadcast call. Faked from the start so fixture-time
        // system-event fan-outs (createGroup / addMembers) never hit the network.
        config(['broadcasting.realtime_transport' => 'centrifugo']);
        config(['broadcasting.default' => 'centrifugo']);
        config(['broadcasting.connections.centrifugo' => [
            'driver'  => 'centrifugo',
            'api_url' => self::API_URL,
            'api_key' => 'test-key',
            'timeout' => 3,
            'verify'  => false,
        ]]);

        $this->fakeCentrifugo();
    }

    /**
     * The real ChatMessageResource (the broadcast/response payload builder) reads
     * reacts / message_albums / message_replays. The base harness only lays down
     * users/chat_rooms/chat_messages/profiles, so add those three tables here so the
     * resource renders against ground truth instead of erroring on a missing table.
     */
    protected function buildPrerequisiteSchema(): void
    {
        parent::buildPrerequisiteSchema();

        Schema::create('reacts', function ($t) {
            $t->id();
            $t->unsignedBigInteger('chat_room_id')->nullable();
            $t->unsignedBigInteger('chat_message_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('react')->nullable();
            $t->timestamps();
        });
        Schema::create('message_albums', function ($t) {
            $t->id();
            $t->unsignedBigInteger('chat_message_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('file')->nullable();
            $t->string('frame')->nullable();
            $t->string('type')->nullable();
            $t->timestamps();
        });
        Schema::create('message_replays', function ($t) {
            $t->id();
            $t->unsignedBigInteger('message_id')->nullable();
            $t->unsignedBigInteger('from_message_id')->nullable();
            $t->timestamps();
        });
    }

    private function fakeCentrifugo(): void
    {
        Http::fake([self::API_URL . '/*' => Http::response(['result' => (object) []], 200)]);
    }

    /**
     * Build a POST request to the send endpoint, bound to the caller, optionally
     * carrying an Idempotency-Key header (= client_uuid).
     */
    private function sendRequest(User $actor, array $body, ?string $idempotencyKey = null): Request
    {
        $server = $idempotencyKey !== null ? ['HTTP_IDEMPOTENCY_KEY' => $idempotencyKey] : [];
        $request = Request::create('/api/groups/1/messages', 'POST', $body, [], [], $server);
        $request->setUserResolver(fn () => $actor);
        \Illuminate\Support\Facades\Auth::setUser($actor);
        $this->app->instance('request', $request);

        return $request;
    }

    /** Owner(1) + the given active members, through the real service. */
    private function makeGroupWithMembers(User $owner, array $memberIds, array $meta = []): ChatGroup
    {
        $group = $this->makeGroup($owner, $meta);
        foreach ($memberIds as $id) {
            $this->seedUser($id);
        }
        if (!empty($memberIds)) {
            $this->service()->addMembers($owner, $group, $memberIds);
        }

        return $group;
    }

    // --- authorized send --------------------------------------------------

    public function test_member_send_persists_with_server_seq_and_broadcasts_once_to_other_active_members(): void
    {
        $owner  = $this->seedUser(1);
        $group  = $this->makeGroupWithMembers($owner, [2, 3]);

        $this->fakeCentrifugo();
        $req = $this->sendRequest(User::find(2), ['message' => 'hi group']);
        $res = $this->decode($this->controller()->sendMessage($req, $group->id));

        $this->assertSuccessEnvelope($res, 201);
        $this->assertSame('hi group', $res['body']['data']['message']);
        $this->assertSame(2, (int) $res['body']['data']['user_id']);

        // Persisted with a server_seq (the shared fork ran fully).
        $msg = ChatMessage::where('chat_room_id', $group->chat_room_id)
            ->where('message', 'hi group')->first();
        $this->assertNotNull($msg);
        $this->assertNotNull($msg->server_seq);

        // Exactly ONE group fan-out to the OTHER active members (sender 2 excluded;
        // owner 1 + member 3 receive on user:#{id}).
        Http::assertSent(function (HttpRequest $r) {
            if ($r->url() !== self::API_URL . '/broadcast') {
                return false;
            }
            $channels = $r->data()['channels'] ?? [];
            sort($channels);
            $data = $r->data()['data'] ?? [];

            return ($data['event'] ?? null) === 'getGroupMessageBloc'
                && $channels === ['user:#1', 'user:#3'];
        });
    }

    // --- back end is the guard: forbidden senders, no orphan row ----------

    public function test_non_member_send_is_403_and_writes_no_row(): void
    {
        $owner   = $this->seedUser(1);
        $group   = $this->makeGroupWithMembers($owner, [2]);
        $stranger = $this->seedUser(99);

        $this->fakeCentrifugo();
        $req = $this->sendRequest($stranger, ['message' => 'sneak']);
        $res = $this->decode($this->controller()->sendMessage($req, $group->id));

        $this->assertErrorEnvelope($res, 403);
        $this->assertSame(0, ChatMessage::where('chat_room_id', $group->chat_room_id)
            ->where('message', 'sneak')->count(), 'forbidden send must not persist a row');
        Http::assertNothingSent();
    }

    public function test_muted_member_send_is_403_and_writes_no_row(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2]);
        $this->service()->muteMember($owner, $group, 2);

        $this->fakeCentrifugo();
        $req = $this->sendRequest(User::find(2), ['message' => 'muted speak']);
        $res = $this->decode($this->controller()->sendMessage($req, $group->id));

        $this->assertErrorEnvelope($res, 403);
        $this->assertSame(0, ChatMessage::where('chat_room_id', $group->chat_room_id)
            ->where('message', 'muted speak')->count());
        Http::assertNothingSent();
    }

    public function test_only_admins_post_blocks_plain_member_403_no_row(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2], ['only_admins_post' => true]);

        $this->fakeCentrifugo();
        $req = $this->sendRequest(User::find(2), ['message' => 'members hush']);
        $res = $this->decode($this->controller()->sendMessage($req, $group->id));

        $this->assertErrorEnvelope($res, 403);
        $this->assertSame(0, ChatMessage::where('chat_room_id', $group->chat_room_id)
            ->where('message', 'members hush')->count());
        Http::assertNothingSent();

        // The owner (staff) still gets through under only_admins_post.
        $this->fakeCentrifugo();
        $req = $this->sendRequest($owner, ['message' => 'admin speaks']);
        $res = $this->decode($this->controller()->sendMessage($req, $group->id));
        $this->assertSuccessEnvelope($res, 201);
    }

    // --- idempotency ------------------------------------------------------

    public function test_repeat_idempotency_key_returns_stored_message_and_no_duplicate(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2]);

        $this->fakeCentrifugo();
        $key = 'client-uuid-abc';

        $first = $this->decode($this->controller()->sendMessage(
            $this->sendRequest(User::find(2), ['message' => 'once'], $key),
            $group->id
        ));
        $this->assertSuccessEnvelope($first, 201);
        $firstId = (int) $first['body']['data']['id'];

        // Replay with the SAME key: same stored message, no new row.
        $second = $this->decode($this->controller()->sendMessage(
            $this->sendRequest(User::find(2), ['message' => 'once'], $key),
            $group->id
        ));
        $this->assertSuccessEnvelope($second, 200);
        $this->assertSame($firstId, (int) $second['body']['data']['id'], 'replay must return the same message');

        $this->assertSame(1, ChatMessage::where('chat_room_id', $group->chat_room_id)
            ->where('client_uuid', $key)->count(), 'no duplicate row for a repeat key');
    }

    // --- reply_to scoping -------------------------------------------------

    public function test_reply_to_in_same_room_links_a_reply(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2]);

        $this->fakeCentrifugo();
        $first = $this->decode($this->controller()->sendMessage(
            $this->sendRequest($owner, ['message' => 'parent']),
            $group->id
        ));
        $parentId = (int) $first['body']['data']['id'];

        $reply = $this->decode($this->controller()->sendMessage(
            $this->sendRequest(User::find(2), ['message' => 'child', 'reply_to' => $parentId]),
            $group->id
        ));
        $this->assertSuccessEnvelope($reply, 201);

        $replyId = (int) $reply['body']['data']['id'];
        $this->assertSame(1, MessageReplay::where('message_id', $replyId)
            ->where('from_message_id', $parentId)->count(), 'reply must be linked to the parent');
    }

    public function test_reply_to_from_another_room_is_rejected_422(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroupWithMembers($owner, [2]);

        // A message that lives in a DIFFERENT room.
        $otherRoomId = ChatRoom::create(['user_id' => 1, 'user_id2' => 2, 'type' => 'friends'])->id;
        $foreign = ChatMessage::create([
            'chat_room_id' => $otherRoomId,
            'user_id'      => 1,
            'message'      => 'foreign',
            'type'         => 'text',
            'status'       => 'sended',
        ]);

        $this->fakeCentrifugo();
        $req = $this->sendRequest(User::find(2), ['message' => 'cross-room reply', 'reply_to' => $foreign->id]);
        $res = $this->decode($this->controller()->sendMessage($req, $group->id));

        $this->assertErrorEnvelope($res, 422);
        $this->assertSame(0, ChatMessage::where('chat_room_id', $group->chat_room_id)
            ->where('message', 'cross-room reply')->count());
    }

    // --- unknown group ----------------------------------------------------

    public function test_unknown_group_id_is_404(): void
    {
        $owner = $this->seedUser(1);
        $this->makeGroupWithMembers($owner, []);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->controller()->sendMessage($this->sendRequest($owner, ['message' => 'x']), 999999);
    }
}
