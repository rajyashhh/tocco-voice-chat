<?php

namespace Modules\Chat\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Exceptions\GroupException;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\GroupMemberResource;
use Modules\Chat\Http\Resources\GroupResource;
use Modules\Chat\Http\Services\ChatService;
use Modules\Chat\Http\Services\MessageService;
use Modules\Chat\Http\Services\GroupService;

/**
 * HTTP surface for group chat (plan §5). Thin by design: every endpoint
 * validates input, then delegates to GroupService, which is the real guard —
 * GroupService resolves the caller's membership and runs it through GroupPolicy
 * before any state change, raising a GroupException (with a stable HTTP status)
 * on any authorization/precondition failure. The controller never re-implements
 * that authority; it only translates a GroupException into the project's JSON
 * error contract and shapes the success response via Common::apiResponse.
 *
 * Route-model resolution is explicit (ChatGroup::findOrFail) rather than implicit
 * module binding, and the {group}/{user} segments are constrained to integers at
 * the route layer, so a non-numeric or unknown id is a clean 404 — never a 500.
 *
 * The back end stays a two-layer guard: GroupService enforces the policy, and the
 * read endpoints (show/listMembers/markRead) additionally require the caller to be
 * a readable member of the room before exposing anything.
 */
class GroupController extends Controller
{
    public function __construct(
        private GroupService $groups,
        private MessageService $messages,
        private ChatService $chat,
    ) {
    }

    /**
     * Statuses that may still READ a group (active or muted — muting only blocks
     * posting, not reading; banned/left lose access entirely).
     *
     * @var array<int, string>
     */
    private const READ_ALLOWED_STATUSES = ['active', 'muted'];

    /**
     * POST /api/groups
     * Create a group. The caller becomes the owner.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'min:1', 'max:255'],
            'description'      => ['sometimes', 'nullable', 'string', 'max:2000'],
            'avatar'           => $this->avatarRules($request),
            'privacy'          => ['sometimes', Rule::in(['public', 'private'])],
            'join_policy'      => ['sometimes', Rule::in(['open', 'invite_only', 'approval'])],
            'max_members'      => ['sometimes', 'integer', 'min:2', 'max:100000'],
            'only_admins_post' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->storeAvatar($request->file('avatar'));
        }

        return $this->guard(function () use ($request, $data) {
            $group = $this->groups->createGroup($request->user(), $data);

            return $this->respondGroup($request, $group, 'group created', 201);
        });
    }

    /**
     * Validation rules for the `avatar` field. The app uploads an image file;
     * web/idempotent callers may send a pre-resolved storage-path string. Split
     * by shape so a file upload passes instead of failing the `string` rule with
     * "avatar must be a string" (the cause of the create-with-photo rejection).
     *
     * @return array<int, mixed>
     */
    private function avatarRules(Request $request): array
    {
        return $request->hasFile('avatar')
            ? ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120']
            : ['sometimes', 'nullable', 'string', 'max:2048'];
    }

    /**
     * Persist a group avatar to the shared image bucket via Common::upload (the
     * same helper/disk that backs chat media) and return its relative storage
     * path — the value stored on chat_groups.avatar. The client resolves the
     * full URL from the storage base, identical to chat attachments.
     */
    private function storeAvatar(\Illuminate\Http\UploadedFile $file): string
    {
        return Common::upload(
            'group_avatars/' . env('APP_ENV'),
            $file,
            null,
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        );
    }

    /**
     * GET /api/groups
     * The caller's own groups (readable membership), newest first. Serves as the
     * explicit refresh / fallback source for the groups list (the drift list is
     * primary). Injects each row's membership so GroupResource emits my_role /
     * unread_count without a per-row query.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) $request->integer('per_page', 50);
        $userId  = (int) $request->user()->id;

        $memberships = ChatRoomMember::query()
            ->where('user_id', $userId)
            ->whereIn('status', self::READ_ALLOWED_STATUSES)
            ->get()
            ->keyBy('chat_room_id');

        $groups = ChatGroup::query()
            ->whereIn('chat_room_id', $memberships->keys()->all() ?: [0])
            ->with('chatRoom')
            ->orderByDesc('id')
            ->paginate($perPage);

        $groups->getCollection()->each(function (ChatGroup $g) use ($memberships) {
            $g->setAttribute('my_membership', $memberships->get($g->chat_room_id));
        });

        return Common::apiResponse(
            true,
            __('success'),
            GroupResource::collection($groups),
            200
        );
    }

    /**
     * GET /api/groups/public
     * Discoverable PUBLIC groups (privacy=public). Anyone may browse; the actual
     * join still respects each group's join_policy (open = join now, approval =
     * request, invite_only = hidden from self-join) at the join endpoints. The
     * caller's membership (if any) is attached so the UI shows "open" vs "join".
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'q'        => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $perPage = (int) $request->integer('per_page', 30);
        $userId  = (int) $request->user()->id;
        $q       = trim((string) $request->input('q', ''));

        $groups = ChatGroup::query()
            ->where('privacy', 'public')
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->with('chatRoom')
            ->orderByDesc('members_count')
            ->paginate($perPage);

        $roomIds = $groups->getCollection()->pluck('chat_room_id')->all();
        $myMemberships = ChatRoomMember::query()
            ->where('user_id', $userId)
            ->whereIn('chat_room_id', $roomIds ?: [0])
            ->get()
            ->keyBy('chat_room_id');

        $groups->getCollection()->each(function (ChatGroup $g) use ($myMemberships) {
            $g->setAttribute('my_membership', $myMemberships->get($g->chat_room_id));
        });

        return Common::apiResponse(
            true,
            __('success'),
            GroupResource::collection($groups),
            200
        );
    }

    /**
     * GET /api/groups/{group}
     * Show a single group. Readable members only.
     */
    public function show(Request $request, int $group): JsonResponse
    {
        $model      = $this->findGroup($group);
        $membership = $this->requireReadableMembership($model, (int) $request->user()->id);

        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        return $this->respondGroup($request, $model, 'success', 200, $membership);
    }

    /**
     * PUT/PATCH /api/groups/{group}
     * Update group metadata (owner/admin — enforced in the service).
     */
    public function update(Request $request, int $group): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['sometimes', 'string', 'min:1', 'max:255'],
            'description'      => ['sometimes', 'nullable', 'string', 'max:2000'],
            'avatar'           => $this->avatarRules($request),
            'privacy'          => ['sometimes', Rule::in(['public', 'private'])],
            'join_policy'      => ['sometimes', Rule::in(['open', 'invite_only', 'approval'])],
            'max_members'      => ['sometimes', 'integer', 'min:2', 'max:100000'],
            'only_admins_post' => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->storeAvatar($request->file('avatar'));
        }

        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model, $data) {
            $updated = $this->groups->updateGroupMeta($request->user(), $model, $data);

            // Fan out the updated metadata to every member so connected clients
            // refresh the header / chats-list row live (no cold start). Routed
            // through the unified chat fan-out policy (dedicated realtimeFanout
            // queue, inline under sync) with the active recipients SNAPSHOTTED here
            // (actor excluded) so delivery is consistent as of the edit and the job
            // never re-reads the live member table per chunk. NON-sensitive meta
            // only (never invite_token).
            $meta = [
                'id'               => (int) $updated->id,
                'name'             => $updated->name,
                'description'      => $updated->description,
                'avatar'           => $updated->avatar,
                'privacy'          => $updated->privacy,
                'join_policy'      => $updated->join_policy,
                'only_admins_post' => (bool) $updated->only_admins_post,
                'max_members'      => (int) $updated->max_members,
                'members_count'    => (int) $updated->members_count,
            ];
            $recipientIds = $this->groups->activeMemberIds($updated, (int) $request->user()->id);

            dispatchChatFanOut(new \Modules\Chat\Jobs\BroadcastGroupUpdated(
                (int) $updated->chat_room_id,
                $meta,
                (int) $request->user()->id,
                recipientIds: $recipientIds,
            ));

            return $this->respondGroup($request, $updated, 'group updated', 200);
        });
    }

    /**
     * DELETE /api/groups/{group}
     * Soft-delete the group (owner only — enforced in the service).
     */
    public function destroy(Request $request, int $group): JsonResponse
    {
        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model) {
            $this->groups->deleteGroup($request->user(), $model);

            return Common::apiResponse(true, __('success'), null, 200);
        });
    }

    /**
     * POST /api/groups/{group}/members
     * Add one or more members (owner/admin — enforced in the service).
     */
    public function addMembers(Request $request, int $group): JsonResponse
    {
        $data = $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'min:1'],
        ]);

        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model, $data) {
            $added = $this->groups->addMembers($request->user(), $model, $data['user_ids']);

            return Common::apiResponse(true, __('success'), ['added' => $added], 200);
        });
    }

    /**
     * DELETE /api/groups/{group}/members/{user}
     * Kick a member (owner/admin — enforced in the service).
     */
    public function removeMember(Request $request, int $group, int $user): JsonResponse
    {
        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model, $user) {
            $this->groups->removeMember($request->user(), $model, $user);

            return Common::apiResponse(true, __('success'), null, 200);
        });
    }

    /**
     * POST /api/groups/{group}/members/{user}/mute
     * Mute a member until an optional timestamp (owner/admin — enforced in service).
     */
    public function muteMember(Request $request, int $group, int $user): JsonResponse
    {
        $data = $request->validate([
            'muted_until' => ['sometimes', 'nullable', 'date'],
        ]);

        $model = $this->findGroup($group);
        $until = array_key_exists('muted_until', $data) && $data['muted_until'] !== null
            ? new \DateTimeImmutable($data['muted_until'])
            : null;

        return $this->guard(function () use ($request, $model, $user, $until) {
            $this->groups->muteMember($request->user(), $model, $user, $until);

            return Common::apiResponse(true, __('success'), null, 200);
        });
    }

    /**
     * POST /api/groups/{group}/members/{user}/promote
     * Promote a member to admin (owner only — enforced in the service).
     */
    public function promote(Request $request, int $group, int $user): JsonResponse
    {
        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model, $user) {
            $this->groups->promote($request->user(), $model, $user);

            return Common::apiResponse(true, __('success'), null, 200);
        });
    }

    /**
     * POST /api/groups/{group}/members/{user}/demote
     * Demote an admin back to member (owner only — enforced in the service).
     */
    public function demote(Request $request, int $group, int $user): JsonResponse
    {
        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model, $user) {
            $this->groups->demote($request->user(), $model, $user);

            return Common::apiResponse(true, __('success'), null, 200);
        });
    }

    /**
     * POST /api/groups/{group}/leave
     * Leave the group voluntarily (owner must transfer/delete first).
     */
    public function leave(Request $request, int $group): JsonResponse
    {
        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model) {
            $this->groups->leave($request->user(), $model);

            return Common::apiResponse(true, __('success'), null, 200);
        });
    }

    /**
     * POST /api/groups/{group}/transfer-ownership
     * Transfer ownership to an active member (owner only — enforced in service).
     */
    public function transferOwnership(Request $request, int $group): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
        ]);

        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model, $data) {
            $this->groups->transferOwnership($request->user(), $model, (int) $data['user_id']);

            return Common::apiResponse(true, __('success'), null, 200);
        });
    }

    /**
     * POST /api/groups/{group}/messages
     * Send a message into the group's unified chat room. This is the group twin of
     * the 1:1 ChatMessagesController::store: it persists the row, then hands it to
     * the SAME MessageService::handleMessage fork — which runs the §5.2 posting gate
     * (GroupService::assertCanPost), allocates the per-room server_seq and dispatches
     * BroadcastGroupMessage. The 1:1 store() and its path are never touched.
     *
     * Authorization is the back end's job: assertCanPost (inside handleMessage)
     * rejects a non-member / muted / only_admins_post sender with a GroupException,
     * which guard() translates to its stable status (403) before any fan-out.
     *
     * Idempotency mirrors the 1:1 contract: client_uuid is the Idempotency-Key
     * header, persisted at INSERT so uq_msg_room_client(chat_room_id, client_uuid)
     * is the hard dedup guarantee. A repeat key returns the already-stored message.
     */
    public function sendMessage(Request $request, int $group): JsonResponse
    {
        $data = $request->validate([
            'message'  => ['nullable', 'string'],
            'type'     => ['sometimes', 'nullable', 'string', 'max:20'],
            'reply_to' => ['nullable', 'integer', 'exists:chat_messages,id'],
            'file'     => ['sometimes', 'array'],
        ]);

        $model = $this->findGroup($group);

        $room = ChatRoom::find($model->chat_room_id);
        if (!$room || ($room->type ?? null) !== 'group') {
            return Common::apiResponse(false, __('not found'), null, 404);
        }

        $user = $request->user();

        // File-type guard, identical to the 1:1 send path.
        if ($request->hasFile('file')) {
            $validExtensions = ['jpeg', 'jpg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'pdf'];
            foreach ((array) $request->file('file') as $file) {
                if (!in_array($file->getClientOriginalExtension(), $validExtensions, true)) {
                    return Common::apiResponse(false, __("File doesn't match our records"), null, 422);
                }
            }
        }

        // A reply must target a message that lives in THIS group's room — never let
        // a client stitch a cross-room reply. We reuse the existing reply mechanism
        // (handleMessage reads $request->message_id), so map reply_to -> message_id.
        $replyToId = $data['reply_to'] ?? null;
        if ($replyToId !== null) {
            $belongs = ChatMessage::where('id', $replyToId)
                ->where('chat_room_id', $room->id)
                ->exists();
            if (!$belongs) {
                return Common::apiResponse(false, __('not found'), null, 422);
            }
        }
        $request->merge(['message_id' => $replyToId]);

        // Idempotency-Key (= client_uuid) persisted at INSERT so the unique index is
        // the gatekeeper. NULL for legacy clients (NULLs stay distinct).
        $clientUuid = trim((string) $request->header('Idempotency-Key', '')) ?: null;

        // Fast-path dedup: the shared 'idempotency' middleware cannot resolve a group
        // room (no peer user_id), so a repeat retry is matched here against the
        // unique key and the stored message is returned in the same success shape.
        if ($clientUuid !== null) {
            $existing = ChatMessage::where('chat_room_id', $room->id)
                ->where('client_uuid', $clientUuid)
                ->first();
            if ($existing) {
                return Common::apiResponse(true, __('success'), new ChatMessageResource($existing), 200);
            }
        }

        // handleMessage is the shared fork: for a type='group' room it runs
        // assertCanPost (§5.2 gate) -> server_seq -> dispatchBroadcast(group).
        // We pre-gate with the SAME assertCanPost here so a forbidden send (non-
        // member / muted / only_admins_post) is rejected BEFORE any row is written
        // — no orphan message in the group timeline. handleMessage re-checks (a cheap
        // membership read + policy), keeping the shared fork the single authority.
        // guard() maps the GroupException to its stable HTTP status.
        return $this->guard(function () use ($request, $model, $room, $user, $data, $clientUuid) {
            $this->groups->assertCanPost($user, $model);

            $message = $this->chat->createChatMessage([
                'chat_room_id' => $room->id,
                'user_id'      => $user->id,
                'message'      => $data['message'] ?? null,
                'type'         => $data['type'] ?? 'message',
                'client_uuid'  => $clientUuid,
            ]);

            $this->messages->handleFileUpload($request, $room, $message, $user);

            $response = $this->messages->handleMessage($request, $message, $user, null, $room);

            return Common::apiResponse(true, __('success'), $response['message_resource'], 201);
        });
    }

    /**
     * POST /api/groups/{group}/messages/delete
     * Delete one or more group messages for everyone. Same `id` array contract as
     * the 1:1 delete; the service is the guard (own-message for members,
     * anyone's for owner/admin) and returns the ids actually soft-deleted, which
     * are fanned out as a `delete-message` signal to the room's members so every
     * open client hides them live (twin of the 1:1 DeleteMessage broadcast).
     */
    public function deleteMessages(Request $request, int $group): JsonResponse
    {
        $data = $request->validate([
            'id'   => ['required', 'array', 'min:1'],
            'id.*' => ['integer', 'min:1'],
        ]);

        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model, $data) {
            $deleted = $this->groups->deleteGroupMessages(
                $request->user(),
                $model,
                $data['id'],
            );

            if (!empty($deleted)) {
                // Fan out off the request through the unified chat fan-out policy
                // (dispatchChatFanOut -> dedicated realtimeFanout queue), mirroring
                // the group send/update paths, so the write returns immediately and
                // the broadcast never pins the request worker. The active recipient
                // set is SNAPSHOTTED here (deleter excluded) so delivery is consistent
                // as of the delete and the job does not re-read the live member table.
                $recipientIds = $this->groups->activeMemberIds($model, (int) $request->user()->id);

                dispatchChatFanOut(new \Modules\Chat\Jobs\BroadcastGroupDelete(
                    (int) $model->chat_room_id,
                    $deleted,
                    (int) $request->user()->id,
                    recipientIds: $recipientIds,
                ));
            }

            return Common::apiResponse(true, __('success'), ['deleted' => $deleted], 200);
        });
    }

    /**
     * GET /api/groups/{group}/members
     * Paginated list of active members. Readable members only.
     */
    public function listMembers(Request $request, int $group): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $model      = $this->findGroup($group);
        $membership = $this->requireReadableMembership($model, (int) $request->user()->id);

        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        $perPage = (int) $request->integer('per_page', 50);

        // Eager-load user.profile (not just user): GroupMemberResource renders
        // $user->profile?->avatar, so loading only `user` leaves the profile
        // lazy and fires one extra query per paginated row (N+1). `user.profile`
        // resolves the avatar for the whole page in a single batched query.
        $members = ChatRoomMember::query()
            ->where('chat_room_id', $model->chat_room_id)
            ->where('status', 'active')
            ->with('user.profile')
            ->orderByRaw("FIELD(role, 'owner', 'admin', 'member')")
            ->orderBy('joined_at')
            ->paginate($perPage);

        return Common::apiResponse(
            true,
            __('success'),
            GroupMemberResource::collection($members),
            200
        );
    }

    /**
     * POST /api/groups/join
     * Join via an invite token (open/invite_only groups — enforced in service).
     */
    public function joinViaInvite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:1', 'max:64'],
        ]);

        return $this->guard(function () use ($request, $data) {
            $group = $this->groups->joinViaInvite($request->user(), $data['token']);

            return $this->respondGroup($request, $group->load('chatRoom'), 'joined', 200);
        });
    }

    /**
     * POST /api/groups/{group}/join
     * Join a PUBLIC group by id (discovery/browse, chat rebuild §4). Privacy +
     * join_policy are enforced in the service. Returns the joined group.
     */
    public function joinPublic(Request $request, int $group): JsonResponse
    {
        $model = $this->findGroup($group);

        return $this->guard(function () use ($request, $model) {
            $joined = $this->groups->joinPublic($request->user(), $model);

            return $this->respondGroup($request, $joined->load('chatRoom'), 'joined', 200);
        });
    }

    /**
     * POST /api/groups/{group}/read
     * Advance the caller's read cursor (monotonic). Membership-gated.
     */
    public function markRead(Request $request, int $group): JsonResponse
    {
        $data = $request->validate([
            'last_read_seq' => ['required', 'integer', 'min:0'],
        ]);

        $model      = $this->findGroup($group);
        $membership = $this->requireReadableMembership($model, (int) $request->user()->id);

        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        $seq = (int) $data['last_read_seq'];

        // Monotonic: never move the cursor backwards (out-of-order client calls,
        // a stale tab) — GREATEST keeps it at the high-water mark. The seq is a
        // bound parameter (not concatenated) so the SET expression is injection-safe.
        DB::update(
            'UPDATE chat_room_members
                SET last_read_seq = GREATEST(CAST(last_read_seq AS SIGNED), ?),
                    updated_at = ?
              WHERE chat_room_id = ? AND user_id = ?',
            [$seq, now(), $model->chat_room_id, $request->user()->id]
        );

        $newSeq = max((int) $membership->last_read_seq, $seq);

        return Common::apiResponse(true, __('success'), ['last_read_seq' => $newSeq], 200);
    }

    // --- internals --------------------------------------------------------

    /**
     * Resolve a live group explicitly (no implicit binding). chat_groups uses soft
     * deletes, so a deleted group is a clean 404 here. Eager-loads chatRoom for the
     * authoritative last_seq used by GroupResource.
     */
    private function findGroup(int $id): ChatGroup
    {
        return ChatGroup::with('chatRoom')->findOrFail($id);
    }

    /**
     * Gate a read endpoint on the caller being a readable member of the group.
     * Returns the membership row on success, or a 403 JsonResponse the caller
     * should return as-is. The service is still the guard for writes; this only
     * protects the read surface (show/listMembers/markRead) from non-members.
     */
    private function requireReadableMembership(ChatGroup $group, int $userId): ChatRoomMember|JsonResponse
    {
        $membership = ChatRoomMember::query()
            ->where('chat_room_id', $group->chat_room_id)
            ->where('user_id', $userId)
            ->first();

        if (!$membership || !in_array($membership->status, self::READ_ALLOWED_STATUSES, true)) {
            return Common::apiResponse(false, __('permission denied'), null, 403);
        }

        return $membership;
    }

    /**
     * Wrap up a single group into the standard envelope, injecting the caller's
     * membership so GroupResource can emit my_role / unread_count / invite_token
     * without a per-row query. When the membership is not supplied (e.g. right
     * after create/join) it is resolved once here.
     */
    private function respondGroup(
        Request $request,
        ChatGroup $group,
        string $message,
        int $status,
        ?ChatRoomMember $membership = null
    ): JsonResponse {
        if (!$group->relationLoaded('chatRoom')) {
            $group->load('chatRoom');
        }

        $membership ??= ChatRoomMember::query()
            ->where('chat_room_id', $group->chat_room_id)
            ->where('user_id', $request->user()->id)
            ->first();

        $group->setAttribute('my_membership', $membership);

        return Common::apiResponse(true, $message, new GroupResource($group), $status);
    }

    /**
     * Run a write action and translate a GroupException into the project's JSON
     * error contract with the exception's stable HTTP status. The back end never
     * lets a forbidden/precondition failure look like a success.
     *
     * @param  callable():JsonResponse  $action
     */
    private function guard(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (GroupException $e) {
            return Common::apiResponse(false, $e->getMessage(), null, $e->getStatus());
        }
    }
}
