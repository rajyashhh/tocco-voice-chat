<?php

use Modules\Chat\Http\Controllers\ChatMessagesController;
use Modules\Chat\Http\Controllers\ChatReactsController;
use Modules\Chat\Http\Controllers\ChatRoomController;
use Modules\Chat\Http\Controllers\GroupController;
use Modules\Chat\Http\Controllers\PinToTopController;
use Modules\Chat\Http\Controllers\SyncController;

Route::middleware(['auth:sanctum', 'verified','generalBan','userBan','localization' ,'update.last.seen'])->group(function () {

    Route::get('/users/list', [ChatRoomController::class, 'users_list']);

    // Contact discovery (chat rebuild §3): match the device address book against
    // registered users by phone (last-10-digits, indexed). Returns matches with a
    // phone_key the client maps back to the locally saved contact name.
    Route::post('/contacts/match', [\Modules\Chat\Http\Controllers\ContactsController::class, 'match']);

    //Chat Room
    Route::get('/Chat-room/search-user', [ChatRoomController::class, 'find_user']);
    Route::post('/Chat-room/cursor', [ChatRoomController::class, 'cursor']);

    // 1:1 read cursor (monotonic) — twin of POST /groups/{group}/read. Declared
    // before the resource so the literal '/read' segment is matched here and never
    // captured as the resource's {Chat_room} id. Persists last_read_seq so the
    // server stops recomputing the DM unread badge from my_last_read_seq=0.
    Route::post('/Chat-room/{id}/read', [ChatRoomController::class, 'markRead'])->whereNumber('id');

    // Lightweight 1:1 get-or-create that returns ONLY the chat_room_id (no message
    // page / mark-seen / OpenChat). The offline-first client calls this on open to
    // resolve the real server room id before the seq-keyed sync + outbox send, so
    // a conversation opened from a profile/picker (no chat_id) never runs against
    // room 0. Declared before the resource so the literal '/ensure' segment is
    // matched here and never captured as the resource's {Chat_room} id.
    Route::post('/Chat-room/ensure', [ChatRoomController::class, 'ensure']);
    // Only index/store/destroy exist on ChatRoomController; create/show/edit/update
    // were never implemented (client uses POST /Chat-room/{id}/ensure etc.).
    Route::resource('/Chat-room', ChatRoomController::class)->only('index', 'store', 'destroy');
    Route::post('/Chat-room/accept-request', [ChatRoomController::class,'accept_request']);
    Route::get('/close-chat', [ChatRoomController::class,'close_Chat']);
    Route::get('/user-status/{id}', [ChatRoomController::class,'userStatus'])->whereNumber('id');
    Route::get('/guest-chat', [ChatRoomController::class,'guestChat']);
    Route::resource('/Chat-PinToTop', PinToTopController::class)->only('index', 'store', 'destroy');

    //Chat Message

    // Idempotency-Key guard (fast-path dedup of offline-outbox retries) applies
    // only to message creation, so store() is registered explicitly with the
    // 'idempotency' middleware and excluded from the resource (other verbs are
    // unaffected). Declared before the resource so this POST mapping wins.
    Route::post('/Chat-Message', [ChatMessagesController::class, 'store'])
        ->middleware('idempotency');
    Route::resource('/Chat-Message', ChatMessagesController::class)->only('update');
    Route::post('/delete-Chat-Message', [ChatMessagesController::class,'deleteForAll']);
    Route::post('/delete-Chat-Message-ForMe', [ChatMessagesController::class,'deleteForMe']);
    Route::resource('/Chat-Message-React', ChatReactsController::class)->only('store');
    Route::post('/find-user', [ChatRoomController::class,'find_user']);
    Route::post('/invite-room', [ChatRoomController::class,'inviteRoom']);

    // Realtime-rebuild Phase 2 (§6.5): REST sync surface for recovery fallback,
    // gap-fill and app-owned state. Read-only, server_seq-keyed, membership-guarded.
    Route::prefix('v1')->group(function () {
        Route::get('/sync/rooms', [SyncController::class, 'rooms']);
        Route::get('/rooms/{id}/messages', [SyncController::class, 'messages'])
            ->whereNumber('id');
    });

    // Realtime-rebuild Phase 6 (§5): group chat HTTP surface. The back end is the
    // real guard — GroupController validates input then delegates to GroupService,
    // which re-resolves membership and runs GroupPolicy before any mutation. The
    // collection routes (/ create, /join) are declared BEFORE the /{group} routes
    // so a literal segment ('join') never gets captured as a numeric id; {group}
    // and {user} are constrained to integers (explicit findOrFail, clean 404).
    Route::prefix('groups')->group(function () {
        Route::post('/', [GroupController::class, 'store']);
        Route::post('/join', [GroupController::class, 'joinViaInvite']);

        // Collection reads — declared BEFORE /{group} (the literal 'public' must
        // not be captured as an id; index serves the caller's own groups).
        Route::get('/', [GroupController::class, 'index']);
        Route::get('/public', [GroupController::class, 'publicIndex']);

        Route::get('/{group}', [GroupController::class, 'show'])->whereNumber('group');
        Route::match(['put', 'patch'], '/{group}', [GroupController::class, 'update'])->whereNumber('group');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->whereNumber('group');

        // Group message send: same offline-first contract as the 1:1 path. The
        // 'idempotency' middleware gives the fast-path dedup of outbox retries (it
        // falls through harmlessly for the group route, which carries no peer
        // user_id — the unique index uq_msg_room_client(chat_room_id, client_uuid)
        // remains the hard guarantee). The persist + server_seq + group fan-out
        // all run through the shared MessageService::handleMessage fork.
        Route::post('/{group}/messages', [GroupController::class, 'sendMessage'])
            ->whereNumber('group')
            ->middleware('idempotency');

        // Delete one or more group messages for everyone. Mirrors the 1:1
        // delete-for-all contract (`id` array) but is membership/ownership gated:
        // a member may delete only their OWN messages; owner/admin may delete
        // anyone's (GroupPolicy::deleteOthersMessage). Soft-deletes + fans out a
        // `delete-message` signal to every member (same fan-out as group sends).
        Route::post('/{group}/messages/delete', [GroupController::class, 'deleteMessages'])
            ->whereNumber('group');

        Route::get('/{group}/members', [GroupController::class, 'listMembers'])->whereNumber('group');
        Route::post('/{group}/members', [GroupController::class, 'addMembers'])->whereNumber('group');
        Route::delete('/{group}/members/{user}', [GroupController::class, 'removeMember'])
            ->whereNumber('group')->whereNumber('user');
        Route::post('/{group}/members/{user}/mute', [GroupController::class, 'muteMember'])
            ->whereNumber('group')->whereNumber('user');
        Route::post('/{group}/members/{user}/promote', [GroupController::class, 'promote'])
            ->whereNumber('group')->whereNumber('user');
        Route::post('/{group}/members/{user}/demote', [GroupController::class, 'demote'])
            ->whereNumber('group')->whereNumber('user');

        Route::post('/{group}/join', [GroupController::class, 'joinPublic'])->whereNumber('group');
        Route::post('/{group}/leave', [GroupController::class, 'leave'])->whereNumber('group');
        Route::post('/{group}/transfer-ownership', [GroupController::class, 'transferOwnership'])->whereNumber('group');
        Route::post('/{group}/read', [GroupController::class, 'markRead'])->whereNumber('group');
    });
});

