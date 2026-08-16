<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Entities\MessageAlbum;
use Modules\Chat\Entities\React;

/**
 * Sync-oriented message representation for the offline-first client.
 *
 * Unlike ChatMessageResource (UI-facing, localized relative timestamps), this
 * resource is the canonical wire shape the drift layer upserts by:
 *  - ordered/identified by server_seq + client_uuid (never id),
 *  - carries the offline-sync columns (kind/system_event, reply, edit/read state),
 *  - emits absolute ISO-8601 server timestamps so the client owns its own
 *    locale formatting.
 *
 * Relations (reacts, albums, replyTo) are expected to be eager-loaded by the
 * controller to avoid N+1 across a page of messages.
 */
class SyncMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => (int) $this->id,
            'room_id'        => (int) $this->chat_room_id,
            'server_seq'     => $this->server_seq !== null ? (int) $this->server_seq : null,
            'client_uuid'    => $this->client_uuid,
            'sender_id'      => $this->user_id !== null ? (int) $this->user_id : null,
            'kind'           => $this->kind ?? 'user',
            'system_event'   => $this->system_event,
            'system_meta'    => $this->system_meta,
            'type'           => $this->type,
            'message'        => $this->message,
            'duration'       => $this->duration ?? '',
            'status'         => $this->status,
            'reply_to_id'    => $this->reply_to_id !== null ? (int) $this->reply_to_id : null,
            'edited_at'      => optional($this->edited_at)->toIso8601String(),
            'delivered_at'   => optional($this->delivered_at)->toIso8601String(),
            'read_at'        => optional($this->read_at)->toIso8601String(),
            'sender_deleted'   => (bool) $this->user_1_deleted,
            'receiver_deleted' => (bool) $this->user_2_deleted,
            'reacts'         => $this->mapReacts(),
            'albums'         => $this->mapAlbums(),
            // created_at/updated_at are pre-formatted, tz-adjusted strings via the
            // model's TimestampsWithTimezone accessor — emit as-is (not Carbon).
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }

    private function mapReacts(): ?array
    {
        $reacts = $this->relationLoaded('reacts')
            ? $this->reacts
            : React::where('chat_message_id', $this->id)->get();

        if ($reacts->isEmpty()) {
            return null;
        }

        return $reacts->map(fn ($react) => [
            'id'      => (int) $react->id,
            'user_id' => (int) $react->user_id,
            'react'   => $react->react,
        ])->all();
    }

    private function mapAlbums(): ?array
    {
        $albums = $this->relationLoaded('albums')
            ? $this->albums
            : MessageAlbum::where('chat_message_id', $this->id)->get();

        if ($albums->isEmpty()) {
            return null;
        }

        return $albums->map(fn ($album) => [
            'id'       => (int) $album->id,
            'user_id'  => (int) $album->user_id,
            'file'     => $album->file,
            'frame'    => $album->frame ?: null,
            'type'     => $album->type,
            'duration' => $this->duration ?? '',
        ])->all();
    }
}
