<?php

namespace Modules\Chat\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off backfill for the realtime/offline-sync chat rebuild.
 *
 *  (a) Assigns a gap-free per-room `server_seq` to every legacy chat_messages
 *      row that lacks one (ordered by id ASC), then sets chat_rooms.last_seq to
 *      that room's max seq.
 *  (b) Migrates the legacy 1:1 membership (chat_rooms.user_id / user_id2) into
 *      the unified chat_room_members table (role=member, status=active).
 *
 * Idempotent: re-runs only touch still-NULL seqs (continuing from the room's
 * current max) and use insertOrIgnore against uq_room_user, so no duplicates.
 * Chunked throughout to bound memory and lock footprint on the production table.
 */
class BackfillChatSequenceAndMembers extends Command
{
    protected $signature = 'chat:backfill-sequence-members
                            {--room-chunk=200 : Number of rooms processed per batch}
                            {--message-chunk=1000 : Number of messages per insert/update batch within a room}
                            {--only=* : Limit to a phase: "seq" and/or "members" (default: both)}';

    protected $description = 'Backfill per-room server_seq and migrate 1:1 membership into chat_room_members (idempotent, chunked)';

    public function handle(): int
    {
        $phases = $this->resolvePhases();

        if (in_array('seq', $phases, true)) {
            $this->backfillServerSeq();
        }

        if (in_array('members', $phases, true)) {
            $this->backfillMembers();
        }

        $this->newLine();
        $this->info('Backfill complete.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function resolvePhases(): array
    {
        $only = array_values(array_filter((array) $this->option('only')));

        return empty($only) ? ['seq', 'members'] : $only;
    }

    /**
     * (a) Per-room server_seq backfill + chat_rooms.last_seq sync.
     */
    private function backfillServerSeq(): void
    {
        $this->info('Phase 1/2: backfilling chat_messages.server_seq ...');

        if (!Schema::hasColumn('chat_messages', 'server_seq') || !Schema::hasColumn('chat_rooms', 'last_seq')) {
            $this->warn('  Required columns missing (server_seq / last_seq). Run migrations first. Skipping.');
            return;
        }

        $roomChunk = (int) $this->option('room-chunk');
        $messageChunk = (int) $this->option('message-chunk');

        // Only rooms that still have at least one unsequenced message.
        $total = DB::table('chat_rooms')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('chat_messages')
                    ->whereColumn('chat_messages.chat_room_id', 'chat_rooms.id')
                    ->whereNull('chat_messages.server_seq');
            })
            ->count();

        if ($total === 0) {
            $this->line('  No unsequenced messages found. Nothing to do.');
            return;
        }

        $this->line("  Rooms with unsequenced messages: {$total}");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::table('chat_rooms')
            ->select('id')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('chat_messages')
                    ->whereColumn('chat_messages.chat_room_id', 'chat_rooms.id')
                    ->whereNull('chat_messages.server_seq');
            })
            ->orderBy('id')
            ->chunk($roomChunk, function ($rooms) use ($bar, $messageChunk) {
                foreach ($rooms as $room) {
                    $this->sequenceRoom((int) $room->id, $messageChunk);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->line('  server_seq backfill done.');
    }

    /**
     * Assign contiguous server_seq to a single room's unsequenced messages,
     * continuing from its current max, then sync chat_rooms.last_seq.
     */
    private function sequenceRoom(int $roomId, int $messageChunk): void
    {
        $seq = (int) DB::table('chat_messages')
            ->where('chat_room_id', $roomId)
            ->max('server_seq'); // NULL -> 0

        do {
            $ids = DB::table('chat_messages')
                ->where('chat_room_id', $roomId)
                ->whereNull('server_seq')
                ->orderBy('id')
                ->limit($messageChunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            DB::transaction(function () use ($ids, &$seq) {
                foreach ($ids as $id) {
                    $seq++;
                    DB::table('chat_messages')
                        ->where('id', $id)
                        ->whereNull('server_seq') // re-check: concurrency/re-run safe
                        ->update(['server_seq' => $seq]);
                }
            });
        } while ($ids->count() === $messageChunk);

        // Sync the room counter without ever moving it backwards.
        DB::update(
            'UPDATE chat_rooms SET last_seq = GREATEST(last_seq, ?), updated_at = updated_at WHERE id = ?',
            [$seq, $roomId]
        );
    }

    /**
     * (b) Migrate legacy 1:1 membership into chat_room_members.
     */
    private function backfillMembers(): void
    {
        $this->info('Phase 2/2: migrating 1:1 membership into chat_room_members ...');

        if (!Schema::hasTable('chat_room_members')) {
            $this->warn('  chat_room_members table missing. Run migrations first. Skipping.');
            return;
        }

        $roomChunk = (int) $this->option('room-chunk');

        $total = DB::table('chat_rooms')
            ->where(function ($q) {
                $q->whereNotNull('user_id')->orWhereNotNull('user_id2');
            })
            ->count();

        if ($total === 0) {
            $this->line('  No rooms with legacy participants. Nothing to do.');
            return;
        }

        $this->line("  Rooms to process: {$total}");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $now = now();

        DB::table('chat_rooms')
            ->select(['id', 'user_id', 'user_id2', 'created_at'])
            ->where(function ($q) {
                $q->whereNotNull('user_id')->orWhereNotNull('user_id2');
            })
            ->orderBy('id')
            ->chunk($roomChunk, function ($rooms) use ($bar, $now) {
                $rows = [];

                foreach ($rooms as $room) {
                    $joinedAt = $room->created_at ?: $now;

                    foreach ([$room->user_id, $room->user_id2] as $participant) {
                        if ($participant === null) {
                            continue;
                        }

                        $rows[] = [
                            'chat_room_id'       => $room->id,
                            'user_id'            => $participant,
                            'role'               => 'member',
                            'status'             => 'active',
                            'last_read_seq'      => 0,
                            'last_delivered_seq' => 0,
                            'cleared_seq'        => 0,
                            'joined_at'          => $joinedAt,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ];
                    }

                    $bar->advance();
                }

                if (!empty($rows)) {
                    // uq_room_user makes this idempotent across re-runs.
                    DB::table('chat_room_members')->insertOrIgnore($rows);
                }
            });

        $bar->finish();
        $this->newLine();
        $this->line('  Membership migration done.');
    }
}
