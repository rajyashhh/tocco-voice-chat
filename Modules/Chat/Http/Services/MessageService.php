<?php

namespace Modules\Chat\Http\Services;

use Illuminate\Http\Request;
use App\Models\User;
use App\Helpers\Common;
use App\Jobs\SendFirebaseNotificationJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Entities\ChatGroup;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom as EntitiesChatRoom;
use Modules\Chat\Entities\ChatRoomMember;
use Modules\Chat\Http\Repositories\MessageAlbumRepository;
use Modules\Chat\Http\Repositories\MessageRepository;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\ChatRoomResource;
use Modules\Chat\Jobs\BroadcastChatMessage;
use Modules\Chat\Jobs\BroadcastGroupMessage;
use Modules\Chat\Traits\FfmpegTrait;
use Modules\Public\Events\UnreadCounterIndividual;

class MessageService
{
    use FfmpegTrait;

    protected $messageAlbumRepository;

    public function __construct(
        MessageAlbumRepository $messageAlbumRepository,
        public MessageRepository $messageRepo,
        protected NextServerSeqService $seq,
        protected GroupService $groupService,
    ) {
        $this->messageAlbumRepository = $messageAlbumRepository;
    }


    public function handleFileUpload(Request $request, $chatRoom, $message, $user)
    {
        if ($request->hasFile('file')) {
            $files = $request->file('file');
            $validExtensions = ['jpeg', 'jpg', 'png', 'gif', 'mp4', 'mp3', 'wav', 'pdf'];
//            $count = count($files);

            if (!is_array($files)) {
                $this->processSingleFile($files, $validExtensions, $chatRoom, $message, $user);
            } else {
                $this->processMultipleFiles($files, $validExtensions, $chatRoom, $message, $user);
            }

        } elseif (is_string($request->file) && $request->file !== '') {
            // Offline-first (outbox) media: the client pre-uploaded the bytes to
            // object storage (pre-signed PUT) and now POSTs the resolved object
            // name/url as a STRING `file` plus the declared `type`. No re-upload
            // here — store the reference directly as the album file, mirroring the
            // existing pre-uploaded VIDEO branch (processVideoFile string path),
            // now generalized to image/voice/video so all 1:1 media rides the same
            // single send path.
            $this->processPreUploadedMedia($request->file, $request->type, $chatRoom, $message, $user, $request->duration);
        } elseif ($request->video_name) {

            $this->processVideoFile($request->video_name, $chatRoom, $message, $user, $request->duration);
        }
    }

    /**
     * Persist a media message whose bytes were already uploaded by the client
     * (offline-first outbox path). [$fileRef] is the storage object name or url;
     * [$wireType] is the client's content type ('image'|'audio'|'video'). The
     * reference is stored verbatim as the album file (the client resolves it via
     * its storage base url), and the message type is set to the legacy glyph the
     * UI switches on. Video reuses processVideoFile so its thumbnail/frame is
     * extracted exactly as the legacy pre-uploaded video path did.
     */
    private function processPreUploadedMedia($fileRef, $wireType, $chatRoom, $message, $user, $duration = null)
    {
        if ($wireType === 'video') {
            $this->processVideoFile($fileRef, $chatRoom, $message, $user, $duration);
            return;
        }

        $albumType = $wireType === 'image' ? 'img' : 'voice';
        $this->messageAlbumRepository->createAlbum($chatRoom, $message, $user, $fileRef, $fileRef, $albumType);

        $message->type = $albumType;
        $message->message = null;
        $message->update();
    }

    private function processSingleFile($file, $validExtensions, $chatRoom, $message, $user, $duration = null)
    {
        $extension = $file->getClientOriginalExtension();
        if (!$this->isValidExtension($extension, $validExtensions)) {
            return response()->json(['status' => 404, 'message' => "Invalid file type"], 404);
        }

        if (in_array($extension, ['jpeg', 'jpg', 'png'])) {
            $this->processImageFile($file, $chatRoom, $message, $user);
        } elseif ($extension == 'gif') {
            $this->processGifFile($file, $chatRoom, $message, $user);
        } elseif ($extension == 'mp4' || is_string($file)) {
            $this->processVideoFile($file, $chatRoom, $message, $user, $duration);
        } elseif (in_array($extension, ['mp3', 'wav', 'm4a', 'aac'])) {
            $this->processAudioFile($file, $chatRoom, $message, $user);
        } elseif ($extension == 'pdf') {
            $this->processPdfFile($file, $chatRoom, $message, $user);
        }
    }

    private function processMultipleFiles($files, $validExtensions, $chatRoom, $message, $user)
    {
        $message->type = 'album';
        $message->update();

        foreach ($files as $file) {
            $extension = $file->getClientOriginalExtension();
            if ($this->isValidExtension($extension, $validExtensions)) {
                $this->processSingleFile($file, $validExtensions, $chatRoom, $message, $user);
            }
        }
    }

    private function isValidExtension($extension, $validExtensions)
    {
        return in_array($extension, $validExtensions);
    }

    private function processImageFile($file, $chatRoom, $message, $user)
    {
        // Sniffed MIME (Common::upload uses getMimeType()) can disagree with the
        // client extension we gated on above: an image picked/renamed as .jpg/.png
        // may content-sniff to a different type. Pass the exact image MIMEs
        // Common::upload supports so a mismatch here is treated as a clean skip
        // (no album row, type stays as-is) instead of throwing an uncaught
        // 'Invalid file type' that the global Handler logs at ERROR for api/Chat-Message.
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        try {
            $file_name = Common::upload('Chat_' . env('APP_ENV') . '/chat_' . $chatRoom->id, $file, null, $imageMimes);
        } catch (\Throwable $e) {
            Log::channel('chat')->warning('Chat image upload skipped (unsupported image MIME)', [
                'msg' => $e->getMessage(),
                'mime' => method_exists($file, 'getMimeType') ? $file->getMimeType() : null,
                'extension' => method_exists($file, 'getClientOriginalExtension') ? $file->getClientOriginalExtension() : null,
                'chat_room_id' => $chatRoom->id ?? null,
                'message_id' => $message->id ?? null,
            ]);
            return;
        }
        $this->messageAlbumRepository->createAlbum($chatRoom, $message, $user, $file, $file_name, 'img');

        $message->type = 'img';
        $message->update();
    }

    private function processGifFile($file, $chatRoom, $message, $user)
    {
        $fileName = Common::upload('Chat_' . env('APP_ENV') . '/chat_' . $chatRoom->id, $file);
        $this->messageAlbumRepository->createAlbum($chatRoom, $message, $user, $file, $fileName, 'gif');

        $message->type = 'gif';
        $message->message = null;
        $message->update();
    }

    private function processVideoFile($file, $chatRoom, $message, $user, $duration = null)
    {
        if (!is_string($file)) {
            $videoMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska', 'video/webm', 'video/3gpp', 'video/3gpp2', 'video/mpeg'];
            $file_name = Common::upload('Chat_' . env('APP_ENV') . '/chat_' . $chatRoom->id, $file, null, $videoMimes, 200);
        } else {
            $file_name = $file;
        }

        $name = pathinfo($file_name, PATHINFO_FILENAME);
        $album = $this->messageAlbumRepository->createAlbum($chatRoom, $message, $user, $file, $file_name, 'video');
        $videoPath = $file_name;
        $thumbnailPath = 'Chat_' . env('APP_ENV') . '/chat_' . $chatRoom->id . '/' . $name . '.jpg';
        // Do NOT swallow FFmpeg failures by returning the message as a string —
        // that masked thumbnail-extraction errors (they vanished with no log and a
        // bogus string return). Log it on the chat channel with context, then
        // rethrow so the request fails loudly and is classified by the Handler.
        try {
            $this->extract_frame($videoPath, $thumbnailPath);
        } catch (\Throwable $e) {
            Log::channel('chat')->error('Chat video thumbnail extraction failed', [
                'msg' => $e->getMessage(),
                'class' => get_class($e),
                'chat_room_id' => $chatRoom->id ?? null,
                'message_id' => $message->id ?? null,
                'video_path' => $videoPath,
            ]);

            throw $e;
        }

        $album->frame = $thumbnailPath;
        $album->save();

        $message->type = 'video';
        $message->message = null;
        $message->duration = $duration;
        $message->update();
    }

    private function processAudioFile($file, $chatRoom, $message, $user)
    {
        $audioMimes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/wave', 'audio/mp4', 'audio/x-m4a', 'audio/m4a', 'audio/aac', 'audio/x-aac', 'audio/ogg', 'application/ogg'];
        $file_name = Common::upload('Chat_' . env('APP_ENV') . '/chat_' . $chatRoom->id, $file, null, $audioMimes);
        $this->messageAlbumRepository->createAlbum($chatRoom, $message, $user, $file, $file_name, 'voice');

        $message->type = 'voice';
        $message->message = null;
        $message->update();
    }

    private function processPdfFile($file, $chatRoom, $message, $user)
    {
        $file_name = Common::upload('Chat_' . env('APP_ENV') . '/chat_' . $chatRoom->id, $file, null, ['application/pdf']);
        $this->messageAlbumRepository->createAlbum($chatRoom, $message, $user, $file, $file_name, 'file');

        $message->type = 'file';
        $message->message = null;
        $message->update();
    }

    public function handleMessage($request, $message, $user, $user2, $chatRoom)
    {
        // Group rooms fork the send path. A group is a chat_rooms row (type='group')
        // backed by a chat_groups row; there is no $user2 (no 1:1 peer). Before the
        // message earns its server_seq we run the §5.2 posting gate
        // (GroupService::assertCanPost) — it throws for non-members, banned, muted,
        // or only_admins_post when the sender is a plain member — so a forbidden
        // send never reaches the timeline or the fan-out. 1:1 rooms skip this
        // entirely and behave exactly as before (no regression).
        $group = $this->resolveGroup($chatRoom);

        if ($group) {
            $this->groupService->assertCanPost($user, $group);
        }

        // Update message status based on user conditions
        $this->updateMessageStatus($message, $user2, $chatRoom);

        // Handle message reply
        if ($request->message_id) {
            $this->messageRepo->createMessageReplay($message->id, $request->message_id);
        }

        // Assign the atomic per-room sequence + idempotency key and advance the
        // room's denormalized "last message" pointers in one short transaction,
        // before anything is broadcast. server_seq drives client ordering and
        // Centrifugo recovery; client_uuid (the Idempotency-Key) backs dedup of
        // offline retries. NextServerSeqService already bumps chat_rooms.last_seq.
        $this->assignSequenceAndDenormalize($message, $chatRoom, $request);

        // Build the broadcast payloads here, in the request, so resources render
        // with the correct request/locale/auth context, then hand the fan-out to
        // a queued job so the broadcast never blocks the sender's response.
        // Eager-load the sender (+ profile) on the SINGLE found message only, so
        // the broadcast payload can carry the sender's name + avatar for the
        // foreground in-app banner / new-conversation list row. Loaded here (not
        // in the repo) to keep history/list queries that reuse the repo N+1-free.
        $broadcastMessage = $this->messageRepo->findMessageById($message->id);
        $broadcastMessage->loadMissing('user.profile');
        $messageResource = new ChatMessageResource($broadcastMessage);
        $roomResource = new ChatRoomResource($chatRoom);

        $this->dispatchBroadcast($chatRoom, $user, $user2, $messageResource, $roomResource, $group);

        // Return the message and chat room resources
        return [
            'message_resource' => $messageResource,
            'room_resource' => $roomResource,
        ];
    }

    /**
     * Allocate the message's server_seq and refresh the room's last_message
     * pointers atomically. Kept deliberately short (single UPDATE for the seq,
     * two targeted updates) so it never holds a long lock — the FairLuck 504
     * lesson. last_seq itself is advanced inside NextServerSeqService.
     *
     * client_uuid is now written at INSERT in the controller's createChatMessage
     * call so the unique index enforces dedup on the insert itself. The
     * assignment below stays only as a defensive fallback for any future caller
     * that persists a message without the header before reaching this method; in
     * the normal store() flow $message->client_uuid is already set, so it is a
     * no-op and never re-writes the column under the index.
     */
    private function assignSequenceAndDenormalize(ChatMessage $message, EntitiesChatRoom $chatRoom, $request): void
    {
        $clientUuid = trim((string) $request->header('Idempotency-Key', '')) ?: null;

        DB::transaction(function () use ($message, $chatRoom, $clientUuid) {
            $serverSeq = $this->seq->next($chatRoom->id);

            $message->server_seq = $serverSeq;
            if ($clientUuid !== null && $message->client_uuid === null) {
                $message->client_uuid = $clientUuid; // fallback only; see docblock
            }
            $message->save();

            $chatRoom->forceFill([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at ?? now(),
            ])->save();

            // Advance the SENDER's own read cursor to the message they just sent.
            // NextServerSeqService bumps chat_rooms.last_seq on every send, but
            // nothing moved the sender's chat_room_members.last_read_seq — so both
            // SyncRoomResource and GroupResource (unread = last_seq - my_last_read_seq)
            // over-counted the user's OWN last message as unread. Kept inside the SAME
            // short transaction: ensure the member row exists, then one indexed,
            // parameterized GREATEST UPDATE (monotonic — never moves the cursor back).
            $this->advanceSenderReadCursor($chatRoom->id, (int) $message->user_id, $serverSeq);
        });
    }

    /**
     * Move the sender's own chat_room_members.last_read_seq forward to the seq of
     * the message they just sent, so the unread badge never counts their own last
     * message (the resources derive unread = last_seq - my_last_read_seq).
     *
     * Mirrors ChatRoomController::markRead exactly: ensure the member row exists
     * (legacy 1:1 rooms predate chat_room_members; a missing row makes the GREATEST
     * UPDATE a silent no-op), then one indexed, monotonic, parameterized UPDATE
     * (GREATEST so out-of-order/late writes never rewind the cursor; $serverSeq is a
     * bound parameter, not concatenated). Runs inside the caller's hot transaction,
     * so it stays a single keyed write — no extra locks, no 504 risk.
     */
    private function advanceSenderReadCursor(int $roomId, int $senderId, int $serverSeq): void
    {
        ChatRoomMember::firstOrCreate(
            ['chat_room_id' => $roomId, 'user_id' => $senderId],
            ['status' => 'active', 'last_read_seq' => 0]
        );

        DB::update(
            'UPDATE chat_room_members
                SET last_read_seq = GREATEST(CAST(last_read_seq AS SIGNED), ?),
                    updated_at = ?
              WHERE chat_room_id = ? AND user_id = ?',
            [$serverSeq, now(), $roomId, $senderId]
        );
    }

    /**
     * Run the realtime fan-out AFTER the HTTP response is flushed to the sender,
     * in-process — not on the heavy redis queue.
     *
     * Why off the heavy queue: the 1:1 publish was the only path that delivers a
     * message live to the peer, and it depended on a heavy1/heavy2/heavy3 worker
     * being up AND broadcasting.default resolving to centrifugo. When either was
     * unmet the queued job never ran (or published to the null driver), so the
     * message persisted, showed up on reopen, but never arrived live. The publish
     * itself is synchronous and fast (Centrifugo HTTP, 3s timeout) and the job's
     * handle() already forces broadcastNow=true, so there is nothing to gain from
     * the extra queue hop and a hard dependency to lose.
     *
     * afterResponse() keeps the original design goal intact: the sender's response
     * is flushed first, so the broadcast never blocks the send latency — but it now
     * always runs in the same PHP worker right after, with no worker dependency.
     * (Same pattern as CommunityController's deferred watermark write.)
     *
     * Under the sync queue (tests/CLI, queue.default='sync') there is no HTTP
     * kernel to flush the afterResponse callbacks, so the job is run INLINE in that
     * case — preserving the existing send-path tests that assert the three events
     * fire exactly once within the call (Event::fake).
     *
     * 1:1 rooms fan out with BroadcastChatMessage (bounded — a single publish to
     * the peer) and stay on afterResponse: the work is tiny and never pins the
     * worker. GROUP rooms fan out with BroadcastGroupMessage, whose per-chunk
     * Centrifugo + FCM work is UNBOUNDED in the member count — running that inline
     * on afterResponse pins the Octane worker for the whole loop (a 50k-member group
     * = up to minutes on one worker). So the group branch is routed through the
     * unified chat fan-out policy (dispatchChatFanOut -> dedicated realtimeFanout
     * queue), with the active recipient set SNAPSHOTTED here (sender excluded) so
     * delivery is consistent as of the send and the job no longer re-reads the live
     * member table per chunk. Both branches stay INLINE under the sync queue
     * (tests/CLI) so the send-path tests still see the events fire within the call.
     */
    private function dispatchBroadcast(
        EntitiesChatRoom $chatRoom,
        User $sender,
        ?User $user2,
        ChatMessageResource $messageResource,
        ChatRoomResource $roomResource,
        ?ChatGroup $group = null,
    ): void {
        $messagePayload = (array) $messageResource->toResponse(request())->getData()->data;

        if ($group) {
            $recipientIds = $this->groupService->activeMemberIds($group, $sender->id);

            dispatchChatFanOut(new BroadcastGroupMessage(
                $chatRoom->id,
                $messagePayload,
                $sender->id,
                recipientIds: $recipientIds,
            ));

            return;
        }

        $roomPayload = (array) $roomResource->toResponse(request())->getData()->data;
        $job = new BroadcastChatMessage($chatRoom->id, $sender->id, $user2?->id, $messagePayload, $roomPayload);

        if (config('queue.default') === 'sync') {
            $job->handle();

            return;
        }

        dispatch(function () use ($job) {
            $job->handle();
        })->afterResponse();
    }

    /**
     * Resolve the ChatGroup backing a room, or null for a 1:1 room. A group is the
     * chat_rooms row whose type='group'; the group record is looked up by
     * chat_room_id (rather than via a relation) so this works whichever ChatRoom
     * model the caller passed. Returns null for every non-group room, which is what
     * keeps the 1:1 send path on its original branch with zero behaviour change.
     */
    private function resolveGroup($chatRoom): ?ChatGroup
    {
        if (($chatRoom->type ?? null) !== 'group') {
            return null;
        }

        return ChatGroup::where('chat_room_id', $chatRoom->id)->first();
    }

    private function updateMessageStatus(ChatMessage $message, ?User $user2, EntitiesChatRoom $chatRoom)
    {
        if (!$user2) {
            return;
        }

        if ($user2->online == 1) {
            $condition = ($user2->current_room_chat == $chatRoom->id);

            $status = $condition ? 'seen' : 'received';
            $this->messageRepo->updateMessageStatus($message, $status);
            if (!$condition) {
                event(new UnreadCounterIndividual('message', $user2, 1));
            }
        }
    }

    public function sendNotification(User $user2, ChatMessage $message)
    {
        if ($user2->is_logout != 1) {
            $notificationId = $this->messageRepo->getUserNotificationId($user2->id);
            if ($notificationId === null) {
                return;
            }

            // Push the FCM call onto the notification queue instead of sending it
            // synchronously inside the send request. The single-recipient path of
            // Common::send_firebase_notification posts to FCM v1 inline (~300ms),
            // which is the bulk of the 1:1 send latency; SendFirebaseNotificationJob
            // is the exact same sender the multi-recipient path already queues, so
            // the delivered notification is unchanged — it just no longer blocks
            // the sender's response.
            SendFirebaseNotificationJob::dispatch(
                tokens: [$notificationId],
                title: $message->user->name,
                body: $message->message,
                messageType: $message->type ?? 'text',
                user: $message->user,
            )->onQueue('notification_heavy');
        }
    }
}
