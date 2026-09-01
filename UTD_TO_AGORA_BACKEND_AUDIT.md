# Backend Audit: UTD-Stream → Agora RTC Migration

**Date:** 2026-08-24  
**Scope:** Backend only (Laravel/PHP) — no Flutter/client changes in this audit  
**Status:** Research-only — no code modifications

---

## Table of Contents

1. [UTD Dependency / File Map](#1-utd-dependency--file-map)
2. [Current Audio-Room Architecture & Flow](#2-current-audio-room-architecture--flow)
3. [UTD-Specific vs Reusable Logic](#3-utd-specific-vs-reusable-logic)
4. [Required Backend Changes for Agora](#4-required-backend-changes-for-agora)
5. [Risks & Gaps](#5-risks--gaps)
6. [Recommended Implementation Order](#6-recommended-implementation-order)

---

## 1. UTD Dependency / File Map

### 1.1 Core UTD-Stream Trait (THE central integration point)

| File | Purpose | UTD-specific? |
|------|---------|---------------|
| `app/Traits/HelperTraits/UtdStreamTrait.php` | All HTTP calls to the UTD Stream engine (token, rooms, participants, calls, bans, send-data). Circuit breaker, retry logic. | **YES — replace entirely** |

### 1.2 Controllers

| File | Purpose | UTD-specific? |
|------|---------|---------------|
| `app/Http/Controllers/Api/V1/UtdStreamController.php` | Main API controller: token minting, room CRUD, participant ops, calls, bans, and 16 internal webhook handlers (room_started/finished, participant_joined/left, track_published/unpublished, 7 call events, 2 presence events, 1 messaging event). | **YES — replace entirely** |
| `app/Http/Controllers/Api/V1/UtdStreamWebhookController.php` | External webhook handler for UTD-Stream callbacks. Routes: `room_started`, `room_finished`, `participant_joined`, `participant_left`, `track_published`, `track_unpublished`. Drives room occupancy + live_times. | **YES — replace entirely** |
| `app/Http/Controllers/VersionController.php` (lines 356-374) | Delivers `utd_stream_app_id`, `utd_stream_app_secret`, `utd_stream_app_key`, `utd_stream_host` to the Flutter app via `/config/settings`. | **YES — rename fields for Agora** |
| `app/Admin/Controllers/SettingController.php` | Admin panel settings page. Reads/writes `utd_stream_*` config keys. | **YES — rename fields for Agora** |
| `app/Http/Controllers/Api/V1/ConfigController.php` (line 258+) | `updateConfigAgoraZego` — generic config saver for third-party settings including UTD stream. | **PARTIALLY — generic, but references UTD** |

### 1.3 Routes

| File | Purpose | UTD-specific? |
|------|---------|---------------|
| `routes/utd-stream.php` | All `/stream/*` routes: token, rooms, participants, calls, bans, webhooks, project info. | **YES — replace entirely** |
| `routes/api.php` (lines 62, 83-84, 231-232) | Imports UtdController, registers `utd-stream-webhook`, requires `utd-stream.php`. | **YES — update** |
| `routes/api.php` (lines 108, 110, 118-119) | UTD payment routes (fawry/paymob callbacks). | **NO — payment-related, NOT streaming** |
| `routes/utd.php` | Legacy UTD routes (all commented out). | **NO — dead code** |

### 1.4 Middleware

| File | Purpose | UTD-specific? |
|------|---------|---------------|
| `app/Http/Middleware/VerifyUtdStreamWebhook.php` | HMAC-SHA256 verification of incoming UTD-Stream webhooks via `X-UTD-Stream-Signature` header. Reads `utd_stream_callback_secret`. | **YES — replace with Agora webhook verification** |
| `app/Http/Kernel.php` (line 147) | Registers `verify.utdstream.webhook` middleware alias. | **YES — update** |

### 1.5 Config Files

| File | Purpose | UTD-specific? |
|------|---------|---------------|
| `config/utd_stream.php` | Timeout, retry, backoff settings for the UTD Stream HTTP client. | **YES — replace with Agora config** |
| `config/services.php` (lines 97-100) | `utd_stream.base_url` env setting (default: `https://engine.udt-stream.com/api/v1`). | **YES — replace** |

### 1.6 Env Variables

| Variable | Purpose | Replace with |
|----------|---------|--------------|
| `UTD_STREAM_BASE_URL` | Engine API base URL | `AGORA_*` equivalents |
| `UTD_STREAM_TIMEOUT_SECONDS` | HTTP request timeout | Keep same pattern |
| `UTD_STREAM_SEND_TIMEOUT_SECONDS` | Send-data path timeout | Keep same pattern |
| `UTD_STREAM_SEND_RETRIES` | Retry count for send-data | Keep same pattern |
| `UTD_STREAM_RETRY_BACKOFF_MS` | Backoff between retries | Keep same pattern |

### 1.7 DB Settings (configs table — runtime-editable)

| Key | Purpose |
|-----|---------|
| `utd_stream_base_url` | Engine base URL (admin-editable, overrides env) |
| `utd_stream_app_id` | Voice engine app ID |
| `utd_stream_server_secret` | Voice engine server secret |
| `utd_stream_callback_secret` | Webhook HMAC secret |
| `utd_stream_app_key` | Voice engine app key |
| `sound_library` | Value `4` = UTD-STREAM for audio |
| `video_library` | Value `4` = UTD-STREAM for video |
| `live_library` | Value `4` = UTD-STREAM for live |

### 1.8 Models (UTD-Stream Analytics)

| Model | Table | Purpose | UTD-specific? |
|-------|-------|---------|---------------|
| `StreamingRoomSession` | `streaming_room_sessions` | Records each engine room session (start/finish/duration). | **YES** |
| `StreamingParticipantSession` | `streaming_participant_sessions` | Records participant join/leave per session. | **YES** |
| `StreamingTrack` | `streaming_tracks` | Records audio/video track publish/unpublish events. | **YES** |
| `StreamingAnalytics` | `streaming_analytics` | Daily aggregate stats (sessions, participants, tracks). | **YES** |
| `UserPresenceSession` | `user_presence_sessions` | User online/offline presence tracking. | **YES** |

### 1.9 Migrations

| Migration | Purpose |
|-----------|---------|
| `2026_05_14_064136_create_streaming_room_sessions_table.php` | streaming_room_sessions |
| `2026_05_14_064138_create_streaming_participant_sessions_table.php` | streaming_participant_sessions |
| `2026_05_14_064140_create_streaming_tracks_table.php` | streaming_tracks |
| `2026_05_14_064142_create_streaming_analytics_table.php` | streaming_analytics |
| `2026_05_14_070015_add_total_messages_to_streaming_analytics_table.php` | Alter streaming_analytics |
| `2026_05_14_065807_create_user_presence_sessions_table.php` | user_presence_sessions |

### 1.10 Jobs

| Job | Purpose | UTD-specific? |
|-----|---------|---------------|
| `app/Jobs/PushStreamDataJob.php` | Queues a single send-data frame to UTD-Stream (fire-and-forget room signals). | **YES — replace** |
| `app/Jobs/SendRoomDataJob.php` | Queues multiple data frames to a single room via UTD-Stream. | **YES — replace** |

### 1.11 Console Commands

| Command | Purpose | UTD-specific? |
|---------|---------|---------------|
| `rooms:sync-occupancy` (`SyncRoomOccupancy.php`) | Polls `listRooms()` from the UTD-Stream API every 30s, reconciles `room_visitors` and `rooms.is_live`. | **YES — must replace with Agora equivalent** |
| `app:handling-room-stream-requests` (`HandlingRoomStreamRequests.php`) | Processes Redis-keyed gift/charisma room jobs, sends via `sendToStream`. | **PARTIALLY — send path is UTD, but job processing is reusable** |
| `update-room-user-now:cron` (`UpdateRoomUserNowCron.php`) | No-op; comment says occupancy is now handled by UTD-Stream webhooks + Reconciler. | **NO — dead code** |

### 1.12 Scheduled Tasks (Kernel.php)

| Schedule | Line | Notes |
|----------|------|-------|
| `rooms:sync-occupancy` every 30s | 258 | Runs `SyncRoomOccupancy` command. **UTD-dependent.** |

### 1.13 Traits Used Across the App

| Trait | Used by | UTD-specific? |
|-------|---------|---------------|
| `UtdStreamTrait` | `Common`, `UtdStreamController`, `SyncRoomOccupancy` | **YES — central HTTP client** |
| `RoomDataTrait` | `Common` (via `sendToStream*` shims) | **PARTIALLY — the public API (`sendToStream`, `sendToStream_2/3/4`) is provider-agnostic; only `pushRoomData` internally dispatches `PushStreamDataJob` which calls `streamSendData`** |

### 1.14 Admin Blade Views

| File | Purpose |
|------|---------|
| `resources/views/admin/settings/utd_stream.blade.php` | UTD Stream credentials form (App ID, Server Secret, App Key, Callback Secret, webhook URL, sound/video/live library selectors) |
| `resources/views/admin/settings/utd_stream_webhooks.blade.php` | Webhook events list display |
| `resources/views/admin/settings_new.blade.php` | Reads `utd_stream_*` vars |
| `resources/views/admin/settings/third_party.blade.php` | Includes `utd_stream.blade.php` partial |
| `resources/views/admin/settings/js/settings_js.blade.php` | AJAX save for third-party settings |
| `resources/views/admin/charges_settings.blade.php` | Posts to `update-agora-zego` route |

### 1.15 Seeder References

| Seeder | Keys |
|--------|------|
| `WhiteLabelSettingsSeeder.php` | `utd_client_id`, `utd_secret_key`, `utd_media_analyze_url` (payment/media, NOT streaming) |

### 1.16 Tests

| Test | Purpose |
|------|---------|
| `tests/Unit/Services/UtdStreamTraitShapeTest.php` | Validates config key names used by UtdStreamTrait |
| `tests/Unit/Http/Controllers/UtdStreamWebhookLiveTimeTest.php` | Validates LiveTime write paths in webhook controller |
| `tests/Unit/Http/Controllers/LiveBroadcastGateTest.php` | Validates broadcast gate logic in webhook controller + Reconciler |

### 1.17 Other UTD References (NOT streaming-related — leave untouched)

| Area | Items |
|------|-------|
| Payment | `utd_fawry`, `utd_paymob` (Fawry/Paymob payment gateway configs, callbacks, middleware) |
| Media | `utd_media_analyze_url` (mp4 type detection) |
| Config | `utd_secret_key`, `utd_client_id` (X-Encrypt header validation) |
| Games | `GameProviderSetting` with `provider_code = 'utd'` (leader-cc aggregator) |
| Controllers | `app/Http/Controllers/utd/*` (Admin panel controllers for rooms, bans, charges, etc.) |
| QA Tests | `tests/Feature/UtdQa/*` (business logic regression guards) |

---

## 2. Current Audio-Room Architecture & Flow

### 2.1 Token Generation Flow

```
Flutter App
  → POST /api/{prefix}/stream/token
      body: { room_name, role, service, room_owner_id, seat_count, seat_mode, ... }
  → UtdStreamController::token()
      → reads utd_stream_app_id + utd_stream_server_secret from DB config
      → translates 'service' to engine 'type' (rooms→audio_room, streaming→live_stream)
      → calls UtdStreamTrait::generateStreamToken()
          → POST {engine_base_url}/token
              headers: X-App-Id, X-App-Secret
              body: { identity, room_name, name, role, type, ...extra }
      → returns token to client
```

**Identity contract:** `identity = (string) $user->id` (the users.id primary key).

### 2.2 Room Lifecycle

```
1. HOST opens room
   → Flutter calls POST /stream/token (role=host, service=rooms)
   → Receives token → connects to UTD-Stream engine room (room_name = rooms.id)
   → Engine fires: room_started webhook → UtdStreamController::onRoomStarted()
     → Creates StreamingRoomSession, increments analytics

2. HOST goes live (video)
   → Publishes VIDEO track
   → Engine fires: track_published webhook → handleTrackPublished()
     → If VIDEO + room owner → setBroadcasting(room, true) → room enters lives list
     → If AUDIO → opens LiveTime timer for mic-hours tracking

3. VIEWER enters room
   → Flutter calls POST /stream/token (role=guest/visitor)
   → Engine fires: participant_joined webhook → handleParticipantJoined()
     → RoomOccupancyReconciler::addParticipant() → inserts room_visitors row

4. VIEWER leaves room
   → Engine fires: participant_left webhook → handleParticipantLeft()
     → closeLiveTimer() → UserHandling::calcTime() → updates LiveTime + salary
     → RoomOccupancyReconciler::removeParticipant() → deletes room_visitors row
     → If owner left → setBroadcasting(false)

5. HOST ends broadcast
   → Publishes nothing (camera off)
   → Engine fires: track_unpublished → setBroadcasting(false)
   → Engine fires: room_finished → deactivateRoom()
     → Clears visitors, is_live=0, is_broadcasting=0, persists tap totals

6. Periodic reconciliation (every 30s)
   → rooms:sync-occupancy → SyncRoomOccupancy::handle()
     → listRooms() from engine API
     → reconcileRoomVisitors() per room
     → deactivateStaleRooms() for rooms gone from engine
     → clearStaleNowRoomMarkers() for stale user positions
```

### 2.3 In-Room Data Channel (Real-time signaling)

The `Common::sendToStream*()` shims are the backbone for all in-room real-time events (gifts, mic events, mode changes, kicks, bans, CP, PK, charisma, boom gifts, etc.).

```
Business logic (100+ call sites)
  → Common::sendToStream('SendCustomCommand', roomId, userId, json)
      → RoomDataTrait::sendToStream() → pushRoomData()
          → dispatch(PushStreamDataJob) [queued, fire-and-forget]
              → UtdStreamTrait::streamSendData(roomName, payload)
                  → POST {engine}/rooms/{roomName}/send-data
                      headers: X-App-Id, X-App-Secret
                      body: { data: payload }
```

**Public API surface of RoomDataTrait shims:**
- `Common::sendToStream(Action, RoomId, FromUserId, MessageContent)` — broadcast to room
- `Common::sendToStream_2(Action, RoomId, UserId, UserName, MessageContent)` — with name
- `Common::sendToStream_3(Action, RoomId, UserId)` — send to single user (destination_identities)
- `Common::sendToStream_4(Action, RoomId, FromUserId, ToUserId, MessageContent)` — targeted
- `Common::sendToStream3(Action, RoomId, FromUserId, MessageContents[])` — batch
- `Common::sendToStreamWithArrayOfRooms(...)` — multi-room broadcast

### 2.4 Room Occupancy Reconciliation

```
RoomOccupancyReconciler (service)
  → resolveRoom(roomName) → Room::find((int)roomName)
  → reconcileRoomVisitors(room, identities[]) → atomic add/remove via RoomVisitorRepository
  → addParticipant(room, userId) → per-webhook real-time add
  → removeParticipant(room, userId) → per-webhook real-time remove
  → deactivateRoom(room) → full cleanup (visitors, is_live, is_broadcasting, tap totals, live viewers)
  → setBroadcasting(room, bool) → rooms.is_broadcasting flag
  → syncSocketCount(room, count) → rooms.count_room_socket (legacy list filter)
```

### 2.5 Calls (1-on-1)

The UTD-Stream engine provides a full call API:
- `initiateCall`, `callRinging`, `acceptCall`, `rejectCall`, `callBusy`, `endCall`, `getCall`, `listCalls`
- Webhooks: `call_initiated`, `call_ringing`, `call_accepted`, `call_rejected`, `call_busy`, `call_ended`, `call_missed`
- Currently these webhook handlers are mostly **TODO stubs** (push notification not yet wired).

### 2.6 Presence

- Webhooks: `user_online`, `user_offline`
- Creates `UserPresenceSession` records
- Updates cache: `presence:user:{id}:status`

---

## 3. UTD-Specific vs Reusable Logic

### 3.1 MUST REPLACE (UTD-dependent)

| Component | Why |
|-----------|-----|
| `UtdStreamTrait` | All HTTP calls to engine (token, rooms, participants, calls, bans, send-data) |
| `UtdStreamController` | API facade over UtdStreamTrait |
| `UtdStreamWebhookController` | Webhook handlers (BUT the business logic inside is reusable — see below) |
| `VerifyUtdStreamWebhook` middleware | HMAC verification keyed to UTD callback secret |
| `config/utd_stream.php` | Timeout/retry config |
| `config/services.php` utd_stream block | Base URL |
| `PushStreamDataJob` | Dispatches to `streamSendData()` — internal plumbing changes |
| `SendRoomDataJob` | Same — dispatches to `streamSendData()` |
| `SyncRoomOccupancy` command | Calls `listRooms()` from UTD engine |
| `rooms:sync-occupancy` schedule | Drives SyncRoomOccupancy |
| DB settings: `utd_stream_*` keys | All 5 credential/config keys |
| Config keys: `sound_library`, `video_library`, `live_library` value `4` | Library selector |
| `StreamingRoomSession` model + migration | Analytics tables (can migrate data or recreate) |
| `StreamingParticipantSession` model + migration | Analytics tables |
| `StreamingTrack` model + migration | Analytics tables |
| `StreamingAnalytics` model + migration | Analytics tables |
| `UserPresenceSession` model + migration | Presence tracking |
| Admin views: `utd_stream.blade.php`, `utd_stream_webhooks.blade.php` | Settings UI |
| `VersionController::settings()` fields | `utd_stream_app_id`, `utd_stream_app_secret`, `utd_stream_app_key`, `utd_stream_host` |
| `SettingController` | Reads/writes UTD stream settings |
| Circuit breaker keys in Redis | `utd_stream_cb_*` keys |

### 3.2 REUSABLE (engine-agnostic — keep as-is)

| Component | Why it survives |
|-----------|----------------|
| `RoomOccupancyReconciler` | Pure business logic. Operates on `room_visitors`, `rooms.is_live`, `rooms.is_broadcasting`, `rooms.count_room_socket`. Only the webhook callers that invoke it are UTD-specific. |
| `RoomVisitorRepository` | Atomic insert/delete on `room_visitors`. Provider-agnostic. |
| `EnteranceRoomServices` | Room enter/leave business logic (visitor tracking, CP handling, mic hand, notification dispatch). Uses `sendToStream*` shims but those are just fire-and-forget publishers. |
| `EnteranceController` | Room entry, mic operations, kick, quit — all business logic. |
| `RoomController` | Room CRUD, live rooms, mic modes, end-live, etc. |
| `MicrophoneController` | Mic up/down, mute/unmute, lock/unlock — business logic + `sendToStream`. |
| `UserHandling::calcTime()` | LiveTime calculation — called by webhooks but the math is provider-agnostic. |
| `LiveTime` model | Mic-hours tracking — widely used for salaries, targets, reports. |
| `LiveTapsController` | Tap-hearts aggregation (Redis counter). |
| `HandlingRoomStreamRequests` | Redis gift job processor — the `sendToStream` calls need re-pointing but the job processing is reusable. |
| All `Common::sendToStream*()` call sites (~100+) | The public shim API is provider-agnostic. Only the internals of `RoomDataTrait::pushRoomData` → `PushStreamDataJob` → `streamSendData` need to point at Agora instead. |
| `RoomJobFactory` / `RoomJobInterface` | Gift room job processing — `sendToStream()` method on the interface just needs a new implementation. |
| PK logic, TaskStream, Charisma, LuckyBox, Boom | All business logic that sends data frames via the shims. |
| `SyncRoomOccupancy::clearStaleNowRoomMarkers()` | Pure DB cleanup — reusable if we keep the same identity model. |

### 3.3 SHARED (used by both UTD and non-UTD paths)

| Component | Notes |
|-----------|-------|
| `Common::getConfig()` / `Common::getConf()` | Used everywhere — just the key names change |
| `Cache` keys (`streaming:active_rooms`, `analytics:*`, `presence:*`) | Naming convention — easy to swap |
| `Room` model | Core model — survives. `is_broadcasting` column was added for UTD but is useful generically. |
| `room_visitors` table | Core occupancy table — provider-agnostic |
| `rooms.count_room_socket` | Legacy list filter — kept in sync by Reconciler |

---

## 4. Required Backend Changes for Agora

### 4.1 Agora RTC Token Generation

**What Agora requires (that UTD does differently):**

| Feature | UTD-Stream | Agora RTC |
|---------|-----------|-----------|
| Token generation | Server POSTs to engine API `/token` (engine mints the token) | Backend generates token locally using Agora SDK |
| SDK | None on backend (pure REST API) | `agora/access_token` PHP package or manual HMAC construction |
| App credentials | `app_id` + `server_secret` | `app_id` + `app_certificate` |
| Token TTL | Managed by engine | Backend must set TTL (typically 3600s) |
| Channel concept | `room_name` = `rooms.id` | `channelName` = same concept |
| User identity | `(string) $user->id` | Same — numeric uid |
| Role | `host`, `admin`, `guest`, `audience`, `visitor` | `PUBLISHER` (1) or `SUBSCRIBER` (2) |
| Server API calls | All room/participant ops via REST to engine | Agora provides REST APIs (room management, mute, kick) but many operations are client-side only |

**Implementation required:**

```php
// New: AgoraTokenService.php
// - generateRtcToken(channelName, uid, role, expire)
// - Uses app_id + app_certificate from config
// - Returns token string for the Flutter client
```

**Agora REST API endpoints needed (replacing UTD engine REST calls):**

| UTD-Stream endpoint | Agora equivalent | Notes |
|---------------------|------------------|-------|
| `POST /token` | Local token generation | No REST call needed — generate locally |
| `GET /rooms` | Agora RTM `getChannelMembers` or custom tracking | Agora doesn't have a "list all rooms" API — need custom room registry |
| `GET /rooms/{name}` | Custom tracking | Not natively available |
| `DELETE /rooms/{name}` | Agora `leaveChannel` (client-side) | No server-side room delete |
| `PUT /rooms/{name}/metadata` | Agora RTM channel attributes | Via Agora RTM SDK |
| `POST /rooms/{name}/send-data` | Agora RTM `sendMessageToChannel` or custom signaling | Different API surface |
| `DELETE /rooms/{name}/participants/{id}` | `PATCH /v1/apps/{appid}/channel/{channel}/user/{uid}` (RESTful API) | Agora does support server-side kick |
| `PUT .../mute` | Agora has no server-side mute — must use RTM signaling + client compliance | **GAP** |
| `PUT .../permissions` | Agora has no server-side publish permission — client-side only | **GAP** |
| `PUT .../metadata` | RTM channel attributes | Via RTM |
| POST/DELETE bans | `PATCH /v1/apps/{appid}/channel/{channel}/user/{uid}` (ban) | Agora has ban API |
| Call APIs | No direct equivalent — must implement via RTM signaling | **GAP — full custom implementation needed** |

### 4.2 New Config Keys Required

```php
// config/agora.php
return [
    'app_id'              => env('AGORA_APP_ID', ''),
    'app_certificate'     => env('AGORA_APP_CERTIFICATE', ''),
    'token_expiry'        => env('AGORA_TOKEN_EXPIRY', 3600),
    'customer_id'         => env('AGORA_CUSTOMER_ID', ''),      // REST API auth
    'customer_secret'     => env('AGORA_CUSTOMER_SECRET', ''),  // REST API auth
    'timeout_seconds'     => env('AGORA_TIMEOUT_SECONDS', 10),
    'send_data_retries'   => env('AGORA_SEND_DATA_RETRIES', 1),
    'send_data_timeout'   => env('AGORA_SEND_DATA_TIMEOUT', 6),
    'retry_backoff_ms'    => env('AGORA_RETRY_BACKOFF_MS', 150),
];
```

### 4.3 New Composer Dependency

```bash
composer require agoraio/agora_access_token
// OR manually implement HMAC-based token generation (it's simple)
```

### 4.4 Webhook Architecture Change

**Current:** UTD-Stream engine pushes webhooks TO the Laravel backend (16 event types).  
**Agora:** No equivalent push webhooks for room events. Options:

| Option | Pros | Cons |
|--------|------|------|
| **A. Agora RTM + server-side event handler** | Full event stream | Complex, requires RTM SDK on server |
| **B. Agora RTM server-side SDK events** | Native support | Adds RTM dependency |
| **C. Client-reported events + reconciliation** | Simple | Unreliable (client can crash) |
| **D. Hybrid: client events + periodic polling** | Balanced | Need both paths |

**Recommended: Option D** (hybrid)
- Client reports join/leave/mute events via existing REST API
- Periodic reconciliation via Agora REST API (list channel members)
- Keep `rooms:sync-occupancy` but source data from Agora API instead of UTD

### 4.5 The `sendToStream` Data Channel

This is the **most critical** piece. ~100+ call sites use `Common::sendToStream*()`.

**Options:**

| Approach | Changes needed |
|----------|----------------|
| **A. Replace with Agora RTM** | New `AgoraRtmTrait` implementing same `sendToStream*` public API but using RTM `sendMessage` |
| **B. Use Agora Signaling (RESTful)** | POST to Agora REST API — slower, but no SDK on server |
| **C. Keep a thin message relay** | Use Centrifugo (already in stack) for data channel instead of Agora | 

**Recommended: Option A** — Replace internals of `RoomDataTrait::pushRoomData` to use Agora RTM. The public API surface (`sendToStream*` shims) stays identical, so all 100+ call sites need zero changes.

---

## 5. Risks & Gaps

### 5.1 Critical Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Agora has no server-side mute/unmute** | Moderators cannot forcibly mute a user from the backend | Use RTM signaling: send a "mute" data message, client complies. Non-enforceable. |
| **Agora has no server-side publish permission** | Cannot forbid/resume a user's stream publishing | Same as mute: signal-based, client compliance required |
| **Agora has no "list all active rooms" API** | `SyncRoomOccupancy` (every 30s) cannot poll the engine for all rooms | Must maintain a custom room registry in Redis/DB, or query per-room |
| **Call system has no Agora equivalent** | 1-on-1 calls (voice/video) use UTD-Stream call APIs entirely | Must build custom call signaling via RTM (significant effort) |
| **Ban enforcement moves to client-side** | UTD-Stream enforced bans at token-mint time (403 on join). Agora bans via REST API exist but are channel-scoped only | Must implement Agora REST ban API + client-side verification |
| **Room lifecycle events lost** | `room_started`, `room_finished` webhooks drive analytics and notifications | Must rebuild: client reports events, or Agora RTM presence events |

### 5.2 Medium Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Analytics tables lose data source** | `streaming_*` tables populated by webhooks — need new data source | Repopulate from Agora REST analytics API or client-reported events |
| **Presence tracking changes** | `user_online`/`user_offline` webhooks drive `UserPresenceSession` | Use Agora RTM presence or existing Centrifugo presence |
| **Token generation complexity** | UTD engine handled token minting internally; Agora requires local token generation | Implement `AgoraTokenService` with proper TTL management |
| **Flutter app must change simultaneously** | Client reads `utd_stream_*` fields from `/config/settings` | Coordinate backend + client deploy |
| **Library selector values** | `sound_library=4`, `video_library=4`, `live_library=4` referenced in Flutter | Add Agora value (e.g., `5`) or repurpose existing values |

### 5.3 Low Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Test updates** | `UtdStreamTraitShapeTest`, `UtdStreamWebhookLiveTimeTest`, `LiveBroadcastGateTest` | Rewrite or remove |
| **Admin UI** | `utd_stream.blade.php` settings page | Replace with Agora settings form |
| **Circuit breaker keys** | `utd_stream_cb_*` Redis keys | Rename to `agora_cb_*` |

### 5.4 Key Gaps to Address

1. **No server-side "list all active rooms" in Agora** — must build a custom room registry
2. **No server-side mute/permission enforcement** — signal-based, client compliance required
3. **Call system is 100% UTD-dependent** — needs full reimplementation
4. **Webhook-driven analytics need new data source**
5. **Flutter client changes required** (out of scope but must be coordinated)

---

## 6. Recommended Implementation Order

### Phase 1: Foundation (Backend-Only, No Client Change Yet)

1. **Install Agora PHP dependency** (`agoraio/agora_access_token` or manual HMAC)
2. **Create `config/agora.php`** + `.env` entries
3. **Create `AgoraTokenService`** — local token generation
4. **Create `AgoraRtmTrait`** — server-side RTM client for send-data
5. **Swap `RoomDataTrait::pushRoomData` internals** — point to Agora RTM instead of UTD send-data
6. **Update `PushStreamDataJob`** — use Agora RTM instead of HTTP call
7. **Keep public API** — `sendToStream*` shims unchanged → 100+ call sites unaffected
8. **Build room registry** — Redis-based custom registry to track active rooms (replaces "list all rooms" gap)
9. **Update `SyncRoomOccupancy`** — source data from room registry instead of UTD API

### Phase 2: Token & Auth

10. **Create new `/stream/token` handler** — return Agora token locally
11. **Update `VersionController::settings()`** — serve `agora_app_id`, `agora_token` (not secrets)
12. **Update `VerifyStreamWebhook` middleware** — Agora webhook signature format
13. **Create new webhook handlers** — for Agora RTM events (if available)

### Phase 3: Room Lifecycle

14. **Implement room_started/room_finished** — via client-reported events + registry
15. **Implement participant_joined/participant_left** — via RTM presence + registry
16. **Wire into `RoomOccupancyReconciler`** — same service, new event sources
17. **Implement track_published/unpublished** — via client events or Agora analytics API

### Phase 4: Moderation & Calls

18. **Implement ban API** — Agora REST ban endpoint
19. **Implement mute signaling** — RTM data message (client-enforced)
20. **Implement kick** — Agora REST user removal
21. **Reimplement call system** — full custom via RTM signaling (largest effort)

### Phase 5: Cleanup & Admin

22. **Update admin settings UI** — Agora credentials form
23. **Update DB settings keys** — migration to rename `utd_stream_*` → `agora_*`
24. **Drop UTD analytics tables** (or migrate data)
25. **Remove `UtdStreamTrait`**, `UtdStreamController`, UTD webhook handlers
26. **Update/remove tests**

### Phase 6: Migration & Deploy

27. **Coordinate with Flutter team** — simultaneous deploy
28. **Feature flag** — `live_library` value switch for gradual rollout
29. **Monitor** — new webhook paths, send-data latency, occupancy accuracy

---

## Appendix: Quick Reference — File Count by Action

| Action | Files |
|--------|-------|
| **Delete entirely** | `UtdStreamTrait.php`, `UtdStreamController.php`, `UtdStreamWebhookController.php`, `VerifyUtdStreamWebhook.php`, `config/utd_stream.php`, `PushStreamDataJob.php`, `SendRoomDataJob.php`, 6 migrations, 5 models, `SyncRoomOccupancy.php`, `UpdateRoomUserNowCron.php`, 3 blade views, 3 tests |
| **Rewrite internals** | `RoomDataTrait.php` (pushRoomData), `VersionController.php` (settings fields), `SettingController.php` (settings keys), `Kernel.php` (schedule) |
| **Rename DB keys** | 5 `utd_stream_*` config keys → `agora_*`, 3 library selector values |
| **Keep as-is** | `RoomOccupancyReconciler.php`, `RoomVisitorRepository`, `EnteranceRoomServices.php`, `EnteranceController.php`, `RoomController.php`, `MicrophoneController.php`, `UserHandling.php`, `LiveTime` model, all 100+ `sendToStream` call sites |
| **Create new** | `AgoraTokenService.php`, `AgoraRtmTrait.php`, `config/agora.php`, `AgoraStreamController.php`, Agora webhook handler, Agora settings blade, room registry service |
