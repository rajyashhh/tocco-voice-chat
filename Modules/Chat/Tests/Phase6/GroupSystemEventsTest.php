<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Http\Services\GroupSystemEventService;

/**
 * Phase 6 §5.5 — group system events land in the unified chat_messages timeline as
 * kind='system' rows with the correct system_event tag + system_meta payload AND
 * the sensitive ones write a chat_group_audit_logs row. Driven through the real
 * GroupService -> GroupSystemEventService -> NextServerSeqService path.
 *
 * A system event must:
 *   - be a real chat_messages row keyed to the SAME chat_room_id;
 *   - carry kind='system', system_event=<the event>, the actor as user_id;
 *   - consume a gap-free server_seq from the room's NextServerSeqService;
 *   - advance chat_rooms.last_message_id / last_message_at.
 */
class GroupSystemEventsTest extends GroupServiceTestCase
{
    private function eventsOfType(int $roomId, string $type): \Illuminate\Support\Collection
    {
        return ChatMessage::where('chat_room_id', $roomId)
            ->where('kind', 'system')
            ->where('system_event', $type)
            ->get();
    }

    public function test_add_members_emits_one_batched_members_joined_event_and_audit(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->seedUser(3);

        $this->service()->addMembers($owner, $group, [2, 3]);

        // A bulk add emits ONE batched members_joined row carrying user_ids[] — not
        // one member_joined per user (that quadratic storm is the whole point of the
        // fix). The client renders "N members joined" from the array.
        $this->assertCount(0, $this->eventsOfType($group->chat_room_id, GroupSystemEventService::MEMBER_JOINED));

        $events = $this->eventsOfType($group->chat_room_id, GroupSystemEventService::MEMBERS_JOINED);
        $this->assertCount(1, $events);

        $ev = $events->first();
        $this->assertSame('system', $ev->kind);
        $this->assertSame((int) $group->chat_room_id, (int) $ev->chat_room_id);
        $this->assertSame(1, (int) $ev->user_id);          // actor = the owner who added
        $this->assertSame([2, 3], array_map('intval', $ev->system_meta['user_ids']));
        $this->assertSame(1, (int) $ev->system_meta['invited_by']);
        $this->assertGreaterThan(0, (int) $ev->server_seq);

        // audit row.
        $audit = $this->auditLogs($group)->firstWhere('action', GroupSystemEventService::AUDIT_ADD_MEMBER);
        $this->assertNotNull($audit);
        $this->assertSame(1, (int) $audit->actor_id);
        $this->assertSame([2, 3], $audit->meta['user_ids']);
    }

    public function test_kick_emits_member_kicked_event_and_audit(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'member');

        $this->service()->removeMember($owner, $group, 2);

        $ev = $this->eventsOfType($group->chat_room_id, GroupSystemEventService::MEMBER_KICKED)->first();
        $this->assertNotNull($ev);
        $this->assertSame(2, (int) $ev->system_meta['user_id']);
        $this->assertSame(1, (int) $ev->system_meta['by']);

        $audit = $this->auditLogs($group)->firstWhere('action', GroupSystemEventService::AUDIT_KICK);
        $this->assertNotNull($audit);
        $this->assertSame(1, (int) $audit->actor_id);
        $this->assertSame(2, (int) $audit->target_id);
    }

    public function test_promote_and_demote_emit_events_and_audit(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'member');

        $this->service()->promote($owner, $group, 2);
        $promoted = $this->eventsOfType($group->chat_room_id, GroupSystemEventService::MEMBER_PROMOTED)->first();
        $this->assertNotNull($promoted);
        $this->assertSame(2, (int) $promoted->system_meta['user_id']);
        $this->assertNotNull($this->auditLogs($group)->firstWhere('action', GroupSystemEventService::AUDIT_PROMOTE));

        $this->service()->demote($owner, $group, 2);
        $demoted = $this->eventsOfType($group->chat_room_id, GroupSystemEventService::MEMBER_DEMOTED)->first();
        $this->assertNotNull($demoted);
        $this->assertSame(2, (int) $demoted->system_meta['user_id']);
        $this->assertNotNull($this->auditLogs($group)->firstWhere('action', GroupSystemEventService::AUDIT_DEMOTE));
    }

    public function test_mute_emits_member_muted_event_and_audit(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->addMemberRow($group, 2, 'member');

        $until = now()->addHour();
        $this->service()->muteMember($owner, $group, 2, $until);

        $ev = $this->eventsOfType($group->chat_room_id, GroupSystemEventService::MEMBER_MUTED)->first();
        $this->assertNotNull($ev);
        $this->assertSame(2, (int) $ev->system_meta['user_id']);
        $this->assertNotNull($ev->system_meta['muted_until']);

        $this->assertNotNull($this->auditLogs($group)->firstWhere('action', GroupSystemEventService::AUDIT_MUTE));
    }

    public function test_leave_emits_member_left_event_without_audit(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->service()->addMembers($owner, $group, [2]);

        $this->service()->leave(User::find(2), $group);

        $ev = $this->eventsOfType($group->chat_room_id, GroupSystemEventService::MEMBER_LEFT)->first();
        $this->assertNotNull($ev);
        $this->assertSame(2, (int) $ev->system_meta['user_id']);

        // leave is self-service: NOT audited (no kick/mute/etc. action recorded).
        $this->assertNull($this->auditLogs($group)->firstWhere('action', 'leave'));
    }

    public function test_transfer_ownership_emits_owner_transferred_event_and_audit(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->service()->addMembers($owner, $group, [2]);

        $this->service()->transferOwnership($owner, $group, 2);

        $ev = $this->eventsOfType($group->chat_room_id, GroupSystemEventService::OWNER_TRANSFERRED)->first();
        $this->assertNotNull($ev);
        $this->assertSame(1, (int) $ev->system_meta['from']);
        $this->assertSame(2, (int) $ev->system_meta['to']);

        $this->assertNotNull($this->auditLogs($group)->firstWhere('action', GroupSystemEventService::AUDIT_TRANSFER_OWNER));
    }

    public function test_rename_emits_group_renamed_event_and_edit_meta_audit(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner, ['name' => 'Old Name']);

        $this->service()->updateGroupMeta($owner, $group, ['name' => 'New Name']);

        $ev = $this->eventsOfType($group->chat_room_id, GroupSystemEventService::GROUP_RENAMED)->first();
        $this->assertNotNull($ev);
        $this->assertSame('Old Name', $ev->system_meta['from']);
        $this->assertSame('New Name', $ev->system_meta['to']);

        $audit = $this->auditLogs($group)->firstWhere('action', GroupSystemEventService::AUDIT_EDIT_META);
        $this->assertNotNull($audit);
        $this->assertContains('name', $audit->meta['changed']);
    }

    public function test_system_events_consume_gapfree_server_seq_and_advance_room_pointer(): void
    {
        $owner = $this->seedUser(1);
        $group = $this->makeGroup($owner);
        $this->seedUser(2);
        $this->seedUser(3);

        // three events: add 2, add 3, kick 2.
        $this->service()->addMembers($owner, $group, [2]);
        $this->service()->addMembers($owner, $group, [3]);
        $this->service()->removeMember($owner, $group, 2);

        $events = $this->systemEvents($group); // ordered by server_seq
        $this->assertCount(3, $events);

        // strictly increasing, gap-free per the room sequence (1,2,3).
        $seqs = $events->pluck('server_seq')->map(fn ($s) => (int) $s)->all();
        $this->assertSame([1, 2, 3], $seqs);

        // room denormalized pointers advanced to the last event.
        $room = \Modules\Chat\Entities\ChatRoom::find($group->chat_room_id);
        $this->assertSame(3, (int) $room->last_seq);
        $this->assertSame((int) $events->last()->id, (int) $room->last_message_id);
        $this->assertNotNull($room->last_message_at);
    }
}
