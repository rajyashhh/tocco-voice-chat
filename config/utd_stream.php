<?php

return [
    'timeout_seconds' => env('UTD_STREAM_TIMEOUT_SECONDS', 10),

    // send-data path only: short timeout + a single extra attempt bounds the
    // worst-case worker hold to ~2 x 6s (vs 3 x 10s with the shared timeout).
    'send_data_timeout_seconds' => env('UTD_STREAM_SEND_TIMEOUT_SECONDS', 6),
    'send_data_retries' => env('UTD_STREAM_SEND_RETRIES', 1),
    'retry_backoff_ms' => env('UTD_STREAM_RETRY_BACKOFF_MS', 150),
];
