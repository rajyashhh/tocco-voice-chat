<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Online (ALGORITHM=INPLACE, LOCK=NONE) secondary indexes for hot read paths that
 * currently full-scan under 1M-concurrent load. Each add is guarded against an
 * existing index of the same name AND against an existing index whose leading
 * columns already match, so it never creates a duplicate and is safe to re-run.
 *
 *  - reals(user_id):  RealsService::getUserReals does
 *      Real::where('user_id', $userId)->orderByDesc('id')->paginate(...)
 *    (the per-user reels feed). user_id has no index — full scan today.
 *    InnoDB appends the PK (id) to secondary keys, so (user_id) alone serves the
 *    equality + ORDER BY id as a tight index range read.
 *
 *  - moment(user_id, created_at):  MomentRepository::getUserMoments does
 *      Moment::where('user_id', $userId)->orderBy('created_at','desc')->paginate(...)
 *    (the per-user moments feed). user_id has no index — full scan today.
 *    The index leads with user_id (equality) then created_at so the ORDER BY
 *    created_at DESC is served from the index without a filesort.
 *
 *  - chat_messages(user_id, status):  the unread-"message" counter in
 *    UserCounterServices::getCountByType('message') does
 *      ChatMessage::where('user_id', $user->id)->where('status','received')->count()
 *    on every my-data / bootstrap call (MyDataResource). Every existing
 *    chat_messages index leads with chat_room_id (idx_chatroom_status,
 *    idx_chat_update), so none can serve this user_id-leading query — full scan
 *    of a millions-of-rows table per my-data. (user_id, status) covers it.
 *
 *  - follows(followed_user_id, user_id, status):  the friends counter self-join
 *    in UserCounterServices::getCountByType('friend') joins follows f1 to f2 on
 *      f1.user_id = f2.followed_user_id AND f1.followed_user_id = f2.user_id
 *    with f1.status = 1 AND f2.status = 1. The existing idx_follows_user_followed
 *    (user_id, followed_user_id) serves the forward direction only; the reverse
 *    join leg (followed_user_id, user_id) has no composite, and status is never
 *    queried alone (cardinality ~2 — a bare status index would be useless, the
 *    same reason is_winner was dropped in 2026_06_13_000006). This composite
 *    covers the reverse join leg and the status=1 filter in one index.
 *
 * SKIPPED (already covered — see report):
 *  - profile_visitors(user_id):  idx_profile_visitors_user_id exists (2026_05_17_120001).
 *  - official_messages:          idx_om_user_type_created / idx_om_user_type_date
 *                                (user_id, type, created_at) already serve the hot
 *                                system-message counter.
 *
 * NOTE: the existing chat_messages indexes (idx_chatroom_status, idx_chat_update)
 * lead with chat_room_id and DO cover the room message list (WHERE chat_room_id=?
 * ORDER BY id), but NOT the user_id-leading unread counter — hence the new
 * (user_id, status) index above. The follows composite makes the single-column
 * idx_follows_followed_user_id redundant; dropping it is left to a separate
 * online migration after EXPLAIN confirms the composite is picked on the test DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('reals', 'user_id')
            && !$this->indexNameExists('reals', 'idx_reals_user_id')
            && !$this->leadingColumnsExist('reals', ['user_id'])) {
            DB::statement('ALTER TABLE reals ADD INDEX idx_reals_user_id (user_id), ALGORITHM=INPLACE, LOCK=NONE');
        }

        if (Schema::hasColumn('moment', 'user_id')
            && Schema::hasColumn('moment', 'created_at')
            && !$this->indexNameExists('moment', 'idx_moment_user_created')
            && !$this->leadingColumnsExist('moment', ['user_id', 'created_at'])) {
            DB::statement('ALTER TABLE moment ADD INDEX idx_moment_user_created (user_id, created_at), ALGORITHM=INPLACE, LOCK=NONE');
        }

        if (Schema::hasColumn('chat_messages', 'user_id')
            && Schema::hasColumn('chat_messages', 'status')
            && !$this->indexNameExists('chat_messages', 'idx_chat_user_status')
            && !$this->leadingColumnsExist('chat_messages', ['user_id', 'status'])) {
            DB::statement('ALTER TABLE chat_messages ADD INDEX idx_chat_user_status (user_id, status), ALGORITHM=INPLACE, LOCK=NONE');
        }

        if (Schema::hasColumn('follows', 'status')
            && !$this->indexNameExists('follows', 'idx_follows_followed_user_status')
            && !$this->leadingColumnsExist('follows', ['followed_user_id', 'user_id', 'status'])) {
            DB::statement('ALTER TABLE follows ADD INDEX idx_follows_followed_user_status (followed_user_id, user_id, status), ALGORITHM=INPLACE, LOCK=NONE');
        }
    }

    public function down(): void
    {
        if ($this->indexNameExists('reals', 'idx_reals_user_id')) {
            DB::statement('ALTER TABLE reals DROP INDEX idx_reals_user_id, ALGORITHM=INPLACE, LOCK=NONE');
        }

        if ($this->indexNameExists('moment', 'idx_moment_user_created')) {
            DB::statement('ALTER TABLE moment DROP INDEX idx_moment_user_created, ALGORITHM=INPLACE, LOCK=NONE');
        }

        if ($this->indexNameExists('chat_messages', 'idx_chat_user_status')) {
            DB::statement('ALTER TABLE chat_messages DROP INDEX idx_chat_user_status, ALGORITHM=INPLACE, LOCK=NONE');
        }

        if ($this->indexNameExists('follows', 'idx_follows_followed_user_status')) {
            DB::statement('ALTER TABLE follows DROP INDEX idx_follows_followed_user_status, ALGORITHM=INPLACE, LOCK=NONE');
        }
    }

    private function indexNameExists(string $table, string $index): bool
    {
        $databaseName = Schema::getConnection()->getDatabaseName();

        $result = DB::select(
            'SELECT COUNT(*) as count
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?',
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
    }

    /**
     * True if any existing index on the table starts with exactly the given
     * leading columns (so a differently-named but equivalent index is honoured).
     */
    private function leadingColumnsExist(string $table, array $leadingColumns): bool
    {
        $databaseName = Schema::getConnection()->getDatabaseName();

        $rows = DB::select(
            'SELECT index_name, seq_in_index, column_name
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
             ORDER BY index_name, seq_in_index',
            [$databaseName, $table]
        );

        $byIndex = [];
        foreach ($rows as $row) {
            // information_schema column-name case varies by MySQL build — normalize.
            $r = array_change_key_case((array) $row, CASE_LOWER);
            $byIndex[$r['index_name']][] = $r['column_name'];
        }

        foreach ($byIndex as $columns) {
            if (array_slice($columns, 0, count($leadingColumns)) === $leadingColumns) {
                return true;
            }
        }

        return false;
    }
};
