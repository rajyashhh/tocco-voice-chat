<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shared room-list cache
    |--------------------------------------------------------------------------
    |
    | When enabled, the hottest endpoint (RoomController::index) caches the
    | heavy serialized room list ONCE for all viewers on the shared-eligible
    | filters (default/popular/trend/last_create/pk/party/recently/festival),
    | instead of one cache entry per user. The only genuinely per-viewer field
    | (is_lucky_box) is overlaid per request on a deep clone of the cached
    | payload. Per-user filters (boss/interested/following/friends/nearby)
    | always use the legacy per-user key regardless of this flag.
    |
    | Flip to false to instantly revert to the legacy per-user key without a
    | redeploy.
    |
    */

    'shared_cache_enabled' => env('ROOMS_SHARED_CACHE_ENABLED', true),

];
