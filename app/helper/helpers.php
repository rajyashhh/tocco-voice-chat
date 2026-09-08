<?php

use App\Models\Country;
use Carbon\Carbon;
use App\Helpers\Common;
use Encore\Admin\Admin;
use App\Classes\AppSetting;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use App\Models\MonthlyDiamondReceive;
use Illuminate\Support\Facades\Redis;
use Modules\UsersWallet\Entities\UserWallet;

use Illuminate\Validation\ValidationException;
use Modules\Reals\Http\Services\FfmpegService;


const LUCKY_REDIS_KEY = "thresholds_lucky_prices";
const PK_IMAGE = 'custom_image/pk.png';
const BaCKGROUND_IMAGE_MODE_8 = 'arab.jpeg';
const CINEMA_IMAGE = 'custom_image/back-black.png';
const GAME_COINS_PLAY = 'game_coins_play_#';




function translate($typeArray)
{
    $arr = [];
    foreach ($typeArray as $key => $type) {
        $arr[$key] = __($type);
    }
    return $arr;
}

function translateCategory($typeArray)
{
    $arr = [];
    foreach ($typeArray as $key => $type) {
        $arr[$type] = __($type);
    }
    return $arr;
}


function generateSignature($nonce, $appKey, $timestamp)
{
    $data = sprintf("%s%s%d", $nonce, $appKey, $timestamp);
    return md5($data);
}

function generatesignatureNonce()
{
    $tempByte = random_bytes(8);
    $signatureNonce = bin2hex($tempByte);
    return $signatureNonce;
}

function getNonce()
{
    return Str::random(16);
}

if (!function_exists('check')) {
    function check()
    {
        $guards = array_keys(config('auth.guards'));

        foreach ($guards as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard);
            }
        }
    }
}
if (!function_exists('convertArabicToEnglishNumbers')) {
    function convertArabicToEnglishNumbers($input)
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($arabic, $english, $input);
    }
}
if (!function_exists('calculateUserUsd')) {
    function calculateUserUsd($diamonds, $value)
    {
        $coins = Common::getMaxCoins() ?? 1;

        $endFormatted = $diamonds / $coins;
        $endFormatted = is_numeric($endFormatted) ? floatval($endFormatted) : 0;
        $value = is_numeric($value) ? floatval($value) : 0;

        $userUsd = ($endFormatted * $value) / 100;

        return common::roundToTwoDecimalPlaces($userUsd);
    }
}

if (!function_exists('decryptToArray')) {
    function decryptToArray(string $encrypted, $key): array
    {
        $iv = substr($key, 0, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

        if ($decrypted === false) {
            return [];
        }

        $result = json_decode($decrypted, true);

        return is_array($result) ? $result : [];
    }
}

if (!function_exists('checkStoredProcedureExists')) {
    function checkStoredProcedureExists($procedureName)
    {
        $databaseName = config('database.connections.mysql.database'); // Get the database name from the environment file

        $result = \DB::select(
            'SELECT COUNT(*) as count
        FROM information_schema.ROUTINES
        WHERE ROUTINE_TYPE = ?
        AND ROUTINE_SCHEMA = ?
        AND ROUTINE_NAME = ?',
            ['PROCEDURE', $databaseName, $procedureName]
        );

        return $result[0]->count > 0;
    }


    function generatesignatureNonces()
    {
        $tempByte = random_bytes(8);
        $signatureNonce = bin2hex($tempByte);
        return $signatureNonce;
    }

    function generateSignatures($nonce, $appKey, $timestamp)
    {
        $data = sprintf("%s%s%d", $nonce, $appKey, $timestamp);
        return md5($data);
    }
}

if (!function_exists('upload')) {
    function upload($file): ?string
    {
        $extension = $file->getClientOriginalExtension();
        $uniqueFileName = Str::random(20) . '_' . uniqid() . '.' . $extension;
        $file->storeAs('videos', $uniqueFileName, 'gcs');
        return 'videos' . DIRECTORY_SEPARATOR . $uniqueFileName;
    }
}


if (!function_exists('deleteFile')) {
    function deleteFile($path): ?string
    {
        return Storage::disk('gcs')->delete($path);
    }
}


if (!function_exists('uploadMonthlyDiamondReceive')) {
    function uploadMonthlyDiamondReceive($user_id, $monthlyDiamondValue)
    {
        $date = \Carbon\Carbon::now(getTimezone());
        MonthlyDiamondReceive::updateOrCreate(
            [
                'user_id' => $user_id,
                'month' => $date->month,
                'year' => $date->year,
            ],
            [
                'monthly_diamond_received' => $monthlyDiamondValue,
            ]
        );
    }
}

if (!function_exists('incrementMonthlyDiamond')) {
    /**
     * Increment monthly diamond received for a user (Race Condition Safe)
     *
     * Uses atomic UPSERT to prevent race conditions when multiple Lucky Gifts
     * are sent to the same user concurrently.
     *
     * @param int $user_id
     * @param float|int $value
     * @return void
     */
    function incrementMonthlyDiamond($user_id, $value)
    {
        $date = \Carbon\Carbon::now(getTimezone());

        \DB::statement("
            INSERT INTO monthly_diamond_receives
                (user_id, month, year, monthly_diamond_received, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                monthly_diamond_received = monthly_diamond_received + VALUES(monthly_diamond_received),
                updated_at = NOW()
        ", [$user_id, $date->month, $date->year, $value]);
    }
}

if (!function_exists('human_file_size')) {
    function human_file_size($bytes, $decimals = 2)
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}

if (!function_exists('get_file_details')) {

    if (!function_exists('numToString')) {
        function numToString($number)
        {
            $units = ['', 'K', 'M', 'B', 'T', 'D', 'E', 'F'];
            for ($i = 0; $number >= 1000; $i++) {
                $number /= 1000;
            }
            return round($number, 2) . $units[$i];
        }
    }

    if (!function_exists('numToStringNew')) {
        function numToStringNew($number)
        {
            // Handle null/empty
            if ($number === null || $number === '') {
                return '0';
            }

            // If it's already a formatted string like "2K", "1.5M", return as-is
            if (is_string($number) && !is_numeric($number)) {
                // Check if it matches a formatted pattern (number + unit suffix)
                if (preg_match('/^[\d.]+[KMBТDEF]$/i', $number)) {
                    return $number;
                }
                return (string) $number;
            }

            // Convert string numbers like '1233' to float
            $number = (float) $number;

            $units = ['', 'K', 'M', 'B', 'T', 'D', 'E', 'F'];
            for ($i = 0; $number >= 1000 && $i < count($units) - 1; $i++) {
                $number /= 1000;
            }
            $number = floor($number * 10) / 10;
            return $number . $units[$i];
        }
    }

    if (!function_exists('dispatchJobToQueue')) {
        function dispatchJobToQueue($job, $queueName = 'database')
        {
            $connection = config('queue.default');
            $queueNames = config('queue.connections.' . $queueName . '.queue');

            // Pick the target queue with a cheap, stateless strategy instead of
            // probing each queue's depth on the hot path. The old depth probe ran
            // Queue::size() per queue — on redis that is LLEN + 2×ZCARD each, i.e.
            // 9 Redis round-trips per dispatch for a 3-queue pool — pure overhead
            // that scaled with dispatch volume and added latency to every producer.
            // A uniform random pick converges to even distribution across many
            // dispatches with zero Redis reads. A single configured queue (string)
            // or empty config falls back to the connection's own default queue.
            if (is_array($queueNames)) {
                // explode(',', '') yields [''] (one empty entry), and a stray comma
                // yields blanks too — pushing onto an empty-string queue silently
                // strands jobs on a queue no worker listens to. Drop blanks; if the
                // override left nothing usable, fall back to the connection's own
                // default queue rather than an empty name.
                $queueNames = array_values(array_filter(
                    array_map('trim', $queueNames),
                    fn ($name) => $name !== ''
                ));
                $selectedQueue = empty($queueNames) ? null : $queueNames[array_rand($queueNames)];
            } else {
                $selectedQueue = is_string($queueNames) && trim($queueNames) === '' ? null : $queueNames;
            }

            \Illuminate\Support\Facades\Queue::connection($connection)->pushOn($selectedQueue, $job);
        }
    }

    if (!function_exists('dispatchChatFanOut')) {
        /**
         * Single delivery policy for chat realtime fan-out jobs (group message,
         * system event, group update, group delete).
         *
         * One seam so every chat fan-out is routed identically and is isolated from
         * the shared heavyProcessing pool: it dispatches onto the dedicated
         * `realtimeFanout` connection (config/queue.php). Under the sync queue
         * (tests/CLI, queue.default='sync') there is no worker, so the job is run
         * INLINE — preserving the send-path tests that assert events fire within the
         * call. Outside sync it never runs on the request worker.
         */
        function dispatchChatFanOut($job): void
        {
            if (config('queue.default') === 'sync') {
                $job->handle();

                return;
            }

            dispatchJobToQueue($job, 'realtimeFanout');
        }
    }

    if (!function_exists('getLeastBusyQueue')) {
        function getLeastBusyQueue($queueConnection = 'database')
        {
            // Was: probe Queue::size() for EVERY queue in the pool on each call —
            // LLEN + 2×ZCARD per queue on redis (~9 round-trips for a 3-queue pool).
            // gift-send calls this 3× inside its DB transaction (~27 redis
            // round-trips per gift) — a direct tail-latency source. Workers consume
            // the whole pool, so a stateless uniform-random pick converges to the
            // same distribution with ZERO Redis reads (same strategy as
            // dispatchJobToQueue). Call sites are unchanged: still returns one
            // queue name from the configured pool.
            $queueNames = config("queue.connections.$queueConnection.queue");

            if (is_array($queueNames)) {
                $queueNames = array_values(array_filter(
                    array_map('trim', $queueNames),
                    fn ($name) => $name !== ''
                ));

                return empty($queueNames) ? 'default' : $queueNames[array_rand($queueNames)];
            }

            return is_string($queueNames) && trim($queueNames) !== '' ? $queueNames : 'default';
        }
    }

    if (!function_exists('settings')) {

        function settings(): AppSetting
        {
            // Resolve a REQUEST-SCOPED AppSetting so settings.json is read from
            // disk once per request (the resource calls settings() ~6 times) yet
            // re-read fresh on the next request. Octane flushes scoped instances
            // between requests, so runtime toggles (stop_luckyGift, version
            // bumps via VersionController) are never served stale across requests.
            // set()/remove() re-read the live file under a lock, so writes are
            // always based on fresh on-disk content regardless of this memo.
            return app(AppSetting::class);
        }
    }
    if (!function_exists('gridStyles')) {
        function gridStyles(): string
        {
            return '
            /* ═══════════════════════════════════════════
               USERS GRID — Clean Modern UI
               ═══════════════════════════════════════════ */

            /* ── Table Styles ── */
            .grid-table {
                border-collapse: separate !important;
                border-spacing: 0 !important;
            }
            .grid-table > thead > tr > th {
                background: #f8fafc !important;
                color: #475569 !important;
                font-weight: 700 !important;
                font-size: 12px !important;
                text-transform: uppercase !important;
                letter-spacing: 0.8px !important;
                padding: 14px 16px !important;
                border-bottom: 2px solid #e2e8f0 !important;
                white-space: nowrap;
            }
            .grid-table > tbody > tr {
                transition: background 0.2s ease;
            }
            .grid-table > tbody > tr > td {
                padding: 12px 16px !important;
                vertical-align: middle !important;
                border-bottom: 1px solid #f1f5f9 !important;
            }
            .grid-table > tbody > tr:nth-child(even) > td {
                background: #fafbfd;
            }
            .grid-table > tbody > tr:hover > td {
                background: #f0f4ff !important;
            }

            /* ── ID Badge ── */
            .ug-id-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 44px;
                padding: 4px 10px;
                background: #eef2ff;
                color: #4338ca;
                font-weight: 700;
                font-size: 12px;
                border-radius: 6px;
                letter-spacing: 0.3px;
                border: 1px solid #c7d2fe;
            }

            /* ── Coins ── */
            .ug-coins {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 6px 14px;
                background: #fffbeb;
                border: 1px solid #fde68a;
                border-radius: 20px;
                font-size: 13px;
            }

            /* ── No Agency Label ── */
            .ug-no-agency {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #94a3b8;
                font-size: 12px;
                font-style: italic;
            }

            /* ── Agency Card Enhancement ── */
            .ug-agency-card {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px 12px;
                border-radius: 10px;
                background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
                border: 1px solid #e8ecf3;
                text-decoration: none;
                color: inherit;
                transition: all 0.25s ease;
            }
            .ug-agency-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.07);
                border-color: #667eea;
                text-decoration: none;
                color: inherit;
            }
            .ug-agency-avatar {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                object-fit: cover;
                border: 2px solid #e0e5f0;
            }
            .ug-agency-card:hover .ug-agency-avatar {
                border-color: #667eea;
            }
            .ug-agency-name {
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }
            .ug-agency-id {
                font-size: 11px;
                color: #94a3b8;
                font-family: monospace;
            }

            /* ── Device Button ── */
            .ug-device-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 7px 16px;
                border-radius: 20px;
                border: none;
                font-weight: 700;
                font-size: 13px;
                cursor: pointer;
                transition: all 0.25s ease;
                box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            }
            .ug-device-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 14px rgba(0,0,0,0.15);
            }
            .ug-device-ok {
                background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                color: #065f46;
            }
            .ug-device-ok:hover {
                background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
            }
            .ug-device-warn {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                color: #92400e;
            }
            .ug-device-warn:hover {
                background: linear-gradient(135deg, #fde68a 0%, #fbbf24 100%);
            }
            .ug-device-count {
                font-size: 14px;
                font-weight: 800;
            }

            /* ── Version Chips ── */
            .ug-versions {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .ug-version-chip {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 4px 10px;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                font-size: 11px;
                color: #475569;
                font-weight: 600;
                font-family: monospace;
                white-space: nowrap;
                transition: all 0.2s;
            }
            .ug-version-chip:hover {
                background: #e2e8f0;
                border-color: #cbd5e1;
            }

            /* ── Action Buttons ── */
            .grid-row-actions .btn {
                border-radius: 8px !important;
                margin: 1px !important;
                padding: 4px 8px !important;
                font-size: 12px !important;
                transition: all 0.2s !important;
            }
            .grid-row-actions .btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 3px 8px rgba(0,0,0,0.12);
            }

            /* ── Pagination ── */
            .box-footer .pagination > li > a,
            .box-footer .pagination > li > span {
                border-radius: 8px !important;
                margin: 0 2px !important;
                border: 1px solid #e2e8f0 !important;
                color: #475569;
                font-weight: 600;
                transition: all 0.2s;
            }
            .box-footer .pagination > .active > a,
            .box-footer .pagination > .active > span {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                border-color: transparent !important;
                color: #fff !important;
            }
            .box-footer .pagination > li > a:hover {
                background: #f1f5f9 !important;
                border-color: #667eea !important;
                color: #667eea !important;
            }

            /* ── Quick Search ── */
            .quick-search .form-control {
                border-radius: 10px !important;
                border: 2px solid #e2e8f0 !important;
                padding: 8px 16px !important;
                transition: border-color 0.3s;
            }
            .quick-search .form-control:focus {
                border-color: #667eea !important;
                box-shadow: 0 0 0 3px rgba(102,126,234,0.15) !important;
            }

            /* ── Modal Styles ── */
            .modal-dialog {
                max-width: 90%;
            }
            .modal-content {
                border: none !important;
                border-radius: 16px !important;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2) !important;
                overflow: hidden;
            }
            .modal-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: #fff !important;
                border-bottom: none !important;
                padding: 18px 24px !important;
            }
            .modal-header .modal-title {
                color: #fff !important;
                font-weight: 700 !important;
            }
            .modal-header .close {
                color: #fff !important;
                opacity: 0.8 !important;
                text-shadow: none !important;
                font-size: 28px !important;
            }
            .modal-header .close:hover {
                opacity: 1 !important;
            }
            .modal-body {
                max-height: 70vh !important;
                overflow-y: auto !important;
                padding: 24px !important;
            }

            /* ── Loader ── */
            .ug-loader {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 40px;
                gap: 12px;
                color: #667eea;
            }
            .ug-loader p {
                color: #94a3b8;
                font-size: 14px;
                margin: 0;
            }

            /* ── Responsive ── */
            @media (max-width: 1200px) {
                .auc-name { max-width: 120px; }
            }

            /* ── Smooth Scrollbar ── */
            .modal-body::-webkit-scrollbar { width: 6px; }
            .modal-body::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
            .modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
            .modal-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

            /* ── Button Circle ── */
            .btn-circle {
                width: 30px;
                height: 30px;
                font-size: 15px;
                border-radius: 50%;
                text-align: center;
            }
        ';
        }
    }

    if (!function_exists('getOfficialMessage')) {

        function getOfficialMessage($message): string
        {
            //api.welcome#name-محمد#app_name-Laravel
            return '';
        }
    }
    if (!function_exists('getImagePath')) {

        function getImagePath(?string $path = null): ?string
        {
            return ($path === null || trim($path) === '') ? null : getDriverUrl() . '/' . $path;
        }
    }

    if (!function_exists('adminAvatarTag')) {

        /**
         * Render an admin avatar as an <img> when a real avatar is uploaded,
         * otherwise a WhatsApp/Gmail-style colored circle with the first initial.
         * Presentation only — no data is written. $extraClass/$extraStyle let the
         * caller size it to match the surrounding markup (navbar vs. dropdown).
         */
        function adminAvatarTag(?string $imageUrl, ?string $name, ?string $username = null, string $extraClass = '', string $extraStyle = ''): string
        {
            // A "real" avatar is any resolvable URL that is NOT the packaged default.
            $isDefault = $imageUrl === null
                || $imageUrl === ''
                || str_contains($imageUrl, 'logo_sphinx')
                || str_contains($imageUrl, 'default-avatar')
                || str_contains($imageUrl, 'user2-160x160')
                || str_ends_with(rtrim($imageUrl, '/'), '/'); // getImagePath(null-ish) => ".../"

            if (!$isDefault) {
                return '<img src="' . e($imageUrl) . '" class="' . e(trim($extraClass)) . '" alt="User Image"'
                    . ($extraStyle ? ' style="' . e($extraStyle) . '"' : '') . '>';
            }

            $label = trim((string) ($name ?: $username ?: ''));
            $initial = $label !== '' ? mb_strtoupper(mb_substr($label, 0, 1)) : '?';

            // Stable per-name color (deterministic hue from a hash).
            $palette = ['#2563eb', '#7c3aed', '#db2777', '#dc2626', '#ea580c',
                        '#d97706', '#16a34a', '#0891b2', '#4f46e5', '#0d9488'];
            $bg = $palette[abs(crc32($label !== '' ? $label : 'default')) % count($palette)];

            return '<span class="letter-avatar ' . e(trim($extraClass)) . '" '
                . 'style="background:' . $bg . ';' . e($extraStyle) . '">' . e($initial) . '</span>';
        }
    }

    if (!function_exists('isImageExists')) {

        // function isImageExists($url)
        // {
        //     if (empty($url)) {
        //         return false; // Prevent empty path error
        //     }

        //     $context = stream_context_create([
        //         'http' => ['timeout' => 2] // Set a 2-second timeout
        //     ]);
        //     $headers = @get_headers($url, 1, $context);
        //     return $headers && strpos($headers[0], '200') !== false;
        // }

        function isImageExists($url)
        {
            if (empty($url)) {
                return false;
            }

            return !empty(trim($url));
        }
    }

    if (!function_exists('isValidExternalUrl')) {
        function isValidExternalUrl(string $url): bool
        {
            $parsed = parse_url($url);
            if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
                return false;
            }

            if (!in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
                return false;
            }

            $ip = gethostbyname($parsed['host']);
            if ($ip === $parsed['host']) {
                return false;
            }

            return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }
    }

    if (!function_exists('httpImage')) {
        function httpImage($image)
        {
            // Security: Validate URL against SSRF attacks
            $validation = \App\Helpers\UrlValidator::validateUrl($image, true);

            if (!$validation['valid']) {
                \Log::warning('SSRF attempt blocked in httpImage()', [
                    'url' => $image,
                    'error' => $validation['error'],
                    'ip' => request()->ip()
                ]);

                throw new \Exception('Invalid image URL: ' . $validation['error']);
            }

            // Only allow HTTPS for external images
            if (!str_starts_with($image, 'https://')) {
                throw new \Exception('Only HTTPS URLs are allowed for external images');
            }

            try {
                // Set timeout and size limits
                $response = http::timeout(10)
                    ->withOptions([
                        'verify' => true, // Verify SSL certificates
                        'allow_redirects' => [
                            'max' => 2, // Limit redirects to prevent redirect-based SSRF
                            'strict' => true
                        ]
                    ])
                    ->get($image);

                if (!$response->successful()) {
                    throw new \Exception('Failed to download image: HTTP ' . $response->status());
                }

                // Validate content type is an image
                $contentType = $response->header('Content-Type');
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (!$contentType || !in_array(strtolower($contentType), $allowedTypes)) {
                    throw new \Exception('URL does not return a valid image type');
                }

                // Validate content size (max 10MB)
                $contentLength = $response->header('Content-Length');
                if ($contentLength && $contentLength > 10485760) {
                    throw new \Exception('Image size exceeds 10MB limit');
                }

                $body = $response->body();
                if (strlen($body) > 10485760) {
                    throw new \Exception('Downloaded image exceeds 10MB limit');
                }

                // Generate secure filename
                $hash = hash('sha256', $image . microtime(true));
                $extension = match ($contentType) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };

                $folder = 'images/' . substr($hash, 0, 32) . '.' . $extension;
                Storage::disk(\config('filesystems.default'))->put($folder, $body, [
                    'CacheControl' => 'public, max-age=31536000',
                    'visibility' => 'public'
                ]);

                return $folder;
            } catch (\Exception $e) {
                \Log::error('httpImage() failed', [
                    'url' => $image,
                    'error' => $e->getMessage(),
                    'ip' => request()->ip()
                ]);

                throw new \Exception('Failed to process image URL: ' . $e->getMessage());
            }
        }
    }

    if (!function_exists('getDriverUrl')) {

        function getDriverUrl(): ?string
        {
            return config('filesystems.disks.' . \config('filesystems.default') . '.url');
        }
    }

    if (!function_exists('dispatchRoomsRedis')) {

        function dispatchRoomsRedis(int $roomId, int $userId, $coins = 0, $data = null, string $type = 'charisma'): void
        {

            $uniqueId = microtime(true) . '_' . mt_rand(1000, 9999);
            $key = 'CharismaGift_' . $type . '_' . $userId . '_' . $roomId . '_' . implode($data) . '_' . $uniqueId;
            $data = serialize($data);



            try {

                $values = [
                    'user_id' => $userId,
                    'room_id' => $roomId,
                    'data' => $data,
                    'type' => $type,
                    'coins' => $coins,
                    'created_at' => now(),
                    'updated_at' => now(),

                ];
                \Illuminate\Support\Facades\Redis::set($key, serialize($values));



                //            \Illuminate\Support\Facades\DB::table('room_jobs')->insert($values);
            } catch (Exception $exception) {
                \Illuminate\Support\Facades\Log::channel('charisma_value')->error('[CHARISMA][1-DISPATCH] Redis save FAILED', [
                    'room_id' => $roomId,
                    'user_id' => $userId,
                    'error'   => $exception->getMessage(),
                ]);
            }
        }
    }
}


if (!function_exists('getRoomStatusBadge')) {
    function getRoomStatusBadge($status)
    {
        $badges = [
            1 => '<span class="label label-success">Active</span>',
            0 => '<span class="label label-default">Inactive</span>',
            2 => '<span class="label label-danger">Closed</span>',
            3 => '<span class="label label-warning">Banned</span>',
            4 => '<span class="label label-info">Closed</span>',
        ];

        return $badges[$status] ?? '<span class="label label-default">Unknown</span>';
    }
}

if (!function_exists('isSubdomain')) {

    function isSubdomain($host = null)
    {
        if (!$host)
            $host = \request()->getHost();
        $hostParts = explode('.', $host);

        if (count($hostParts) < 2) {
            return false;
        }
        //        $baseDomain = implode('.', array_slice($hostParts, -2));

        return count($hostParts) > 2;
    }
}
if (!function_exists('adjustColor')) {

    function adjustColor($hex, $rOffset = -30, $gOffset = -90, $bOffset = -60): string
    {
        // Convert the hex color to RGB
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");

        // Apply the offsets and ensure values are within 0-255
        $newR = max(0, min(255, $r - $rOffset));
        $newG = max(0, min(255, $g - $gOffset));
        $newB = max(0, min(255, $b - $bOffset));

        // Convert the new RGB values back to hex format
        return sprintf("#%02x%02x%02x", $newR, $newG, $newB);
    }

    function getInverseColor($hex): string
    {
        // Convert the hex color to RGB
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");

        // Calculate the inverse by subtracting each component from 255
        $inverseR = 255 - $r;
        $inverseG = 255 - $g;
        $inverseB = 255 - $b;

        // Convert the inverted RGB values back to hex format
        return sprintf("#%02x%02x%02x", $inverseR, $inverseG, $inverseB);
    }


    function adjustTextColor($hex, $lightnessFactor = 0.8, $darknessFactor = 0.2)
    {
        // Convert hex color to RGB
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");

        // Calculate brightness (perceived luminance)
        $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        if ($brightness > 0.5) {
            // If color is light, make it darker
            $newR = intval($r * $darknessFactor);
            $newG = intval($g * $darknessFactor);
            $newB = intval($b * $darknessFactor);
        } else {
            // If color is dark, make it lighter
            $newR = intval($r + (255 - $r) * $lightnessFactor);
            $newG = intval($g + (255 - $g) * $lightnessFactor);
            $newB = intval($b + (255 - $b) * $lightnessFactor);
        }

        // Convert back to hex
        return sprintf("#%02x%02x%02x", $newR, $newG, $newB);
    }
    function getLighterColor($hex, $lightness = 0.9)
    {
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");

        // Calculate a lighter color closer to white by increasing each component
        $newR = intval($r + (255 - $r) * $lightness);
        $newG = intval($g + (255 - $g) * $lightness);
        $newB = intval($b + (255 - $b) * $lightness);

        // Convert back to hex
        return sprintf("#%02x%02x%02x", $newR, $newG, $newB);
    }
}


if (!function_exists('isRunningOctane')) {

    function isRunningOctane(): bool
    {
        return \App\Services\OctaneBroadcasterService::isOctane();
    }
}

if (!function_exists('nameRoute')) {
    function nameRoute(string $name): string
    {
        $separators = ['.', '/'];
        $separator = null;
        $requestPath = \Request::path();

        if (\Str::startsWith($requestPath, 'preview')) { //admin.route.prefix,admin.auth.controller
            foreach ($separators as $s) {
                $valuesCount = count(explode($s, $name));
                if ($valuesCount > 1)
                    $separator = $s;
            }
        }

        return $separator ? ('preview' . $separator . $name) : $name;
    }
}
if (!function_exists('getFileExtension')) {
    function getFileExtension($url)
    {
        return pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
    }
}

if (!function_exists('handleShowImageSvga')) {
    function handleShowImageSvga(string $uniqueId, ?string $url, int $width = null, int $height = null, int $borderRadius = 50, string $objectFit = 'cover'): string
    {
        $imageType = getFileExtension($url);

        // SVGA / ZZ animation
        if ($imageType === 'svga' || $imageType === 'zz') {
            $id = 'svga_' . uniqid();
            return "<div class='svga-player' data-url='{$url}' id='{$id}' style='width: {$width}px; height: {$height}px;'></div>";
        }

        // MP4 Video
        if ($imageType === 'mp4') {
            return "
                <video width='{$width}' height='{$height}' controls autoplay muted loop>
                    <source src='{$url}' type='video/mp4'>
                    <source src='{$url}' type='video/webm'>
                    Your browser does not support the video tag.
                </video>
            ";
        }

        // Normal Image
        if ($objectFit !== 'cover') {
            return '<img src="' . e($url) . '" style="width: 100px; height: 100px; object-fit: contain; border-radius: 4px; margin-right: 4px;">';
        }

        return "<img src='{$url}' style='height: {$height}px !important; width: {$width}px !important; border-radius: {$borderRadius}%; object-fit: {$objectFit};' alt='' />";
    }
}



if (!function_exists('handleShowImageWithTypes')) {
    function handleShowImageWithTypes(string $uniqueId, ?string $url, int $width = null, int $height = null, $borderRadius = 50, $objectFit = 'cover'): string
    {
        $imageType = getFileExtension($url);
        if ($imageType == 'svga' || $imageType == 'zz') {
            $model = showSvgaImage($url, $uniqueId);
            if ($objectFit !== 'cover') {
                return "<div class='rtlSvga' id='$model'
            style='width: {$width}px;
                   height: {$height}px;
                   object-fit: {$objectFit};
                   border-radius: {$borderRadius}px;
                   margin-right: 4px;'>
             </div>";
            }
            return "<div class ='rtlSvga' id='$model' style='width: {$width}px !important; height: {$height}px !important;'> </div>";
        } elseif ($imageType == 'mp4') {
            return "
                <video width='$width' height='$height' controls autoplay muted loop>
                    <source src='$url' type='video/mp4'>
                    <source src='$url' type='video/webm'>

                    Your browser does not support the video tag.
                 </video>
                ";
        } elseif ($objectFit !== 'cover') {
            return '<img src="' . e($url) . '" alt="' . '" style="width: 100px; height: 100px; object-fit: contain; border-radius: 4px; margin-right: 4px;">';
        }



        return "<img src='$url' style='height: {$height}px !important; width: {$width}px !important; border-radius: {$borderRadius}%; object-fit: {$objectFit};' alt='' />";
    }
    if (!function_exists('handleShowImageWithSvga')) {
        // function handleShowImageWithSvga(string $uniqueId, ?string $url, int $width = null, int $height = null, $borderRadius = 50, $objectFit = 'cover'): string
        // {
        //     $imageType = getFileExtension($url);
        //     if ($imageType == 'svga' || $imageType == 'zz') {
        //         // Standardize markup to `.svga-player` so the global initializer can detect and initialize it.
        //         $id = 'svga_' . $uniqueId;
        //         $safeUrl = e($url);
        //         $style = "width: {$width}px; height: {$height}px;";
        //         if ($objectFit !== 'cover') {
        //             $style .= " object-fit: {$objectFit}; border-radius: {$borderRadius}px; margin-right: 4px;";
        //         }
        //         return "<div class='svga-player rtlSvga' data-url=\"{$safeUrl}\" id=\"{$id}\" style=\"{$style}\"></div>";
        //     } elseif ($imageType == 'mp4') {
        //         return "
        //         <video width='$width' height='$height' controls autoplay muted loop>
        //             <source src='$url' type='video/mp4'>
        //             <source src='$url' type='video/webm'>

        //             Your browser does not support the video tag.
        //          </video>
        //         ";
        //     } elseif ($objectFit !== 'cover') {
        //         return '<img src="' . e($url) . '" alt="' . '" style="width: 100px; height: 100px; object-fit: contain; border-radius: 4px; margin-right: 4px;">';
        //     }



        //     return "<img src='$url' style='height: {$height}px !important; width: {$width}px !important; border-radius: {$borderRadius}%; object-fit: {$objectFit};' alt='' />";
        // }

        function handleShowImageWithSvga(
            string $uniqueId,
            ?string $url,
            int $width = null,
            int $height = null,
            $borderRadius = 50,
            $objectFit = 'cover'
        ): string {
            $imageType = getFileExtension($url);

            // Detect RTL or LTR dynamically
            $direction = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
            $marginSide = $direction === 'rtl' ? 'margin-left' : 'margin-right';

            if ($imageType == 'svga' || $imageType == 'zz') {
                $id = 'svga_' . $uniqueId;
                $safeUrl = e($url);

                $direction = app()->getLocale() === 'ar' ? 'rtl' : 'ltr';
                $marginSide = $direction === 'rtl' ? 'margin-left' : 'margin-right';

                // Style for table cell alignment
                $style = "width: {$width}px; height: {$height}px;";
                $style .= " display: inline-block;"; // ensures it doesn't stretch the cell
                $style .= " vertical-align: middle;"; // aligns icons in table rows

                $style .= " justify-content: center;";   // horizontal center
                $style .= " align-items: center;";
                if ($objectFit !== 'cover') {
                    $style .= " object-fit: {$objectFit}; border-radius: {$borderRadius}px; {$marginSide}: 4px;";
                }

                // Optional: scale and horizontal flip for RTL
                $scale = 1;
                $flip = $direction === 'rtl' ? 'scaleX(-0.5)' : 'scaleX(1)';
                $style .= " transform: {$flip} scale({$scale});";

                // Add RTL/LTR class for CSS if needed
                $directionClass = $direction === 'rtl' ? 'rtlSvga' : 'ltrSvga';

                return "<div class='svga-player $directionClass' data-url=\"{$safeUrl}\" id=\"{$id}\" style=\"{$style}\"></div>";
            } elseif ($imageType == 'mp4') {
                return "
            <video width='$width' height='$height' controls autoplay muted loop>
                <source src='$url' type='video/mp4'>
                <source src='$url' type='video/webm'>
                Your browser does not support the video tag.
             </video>
        ";
            } elseif ($objectFit !== 'cover') {
                return '<img src="' . e($url) . '" alt="" style="width: 100px; height: 100px; object-fit: contain; border-radius: 4px; ' . $marginSide . ': 4px;">';
            }

            return "<img src='$url' style='height: {$height}px !important; width: {$width}px !important; border-radius: {$borderRadius}%; object-fit: {$objectFit};' alt='' />";
        }
    }
}



if (!function_exists('userType')) {
    function userType($type)
    {
        switch ($type) {
            case 0:
                $userType = __("User");
                break;
            case 1:
                $userType = __("Host");
                break;
            case 2:
                $userType = __("Host Agent");
                break;
            case 3:
                $userType = __("Shipping Agent");
                break;
            case 4:
                $userType = __("Resort & Shipping Agent");
                break;
            case 5:
                $userType = __("Admin");
                break;
            default:
                $userType = $type; // Keep the original value if no match is found
                break;
        }
    }
}

if (!function_exists('convertNumbersToWestern')) {
    function convertNumbersToWestern($string)
    {
        $newNumbers = range(0, 9);

        if (app()->getLocale() == 'hi') {
            $numbers = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        } elseif (app()->getLocale() == 'ar') {
            $numbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        }
        // Arabic (Eastern)


        return str_replace($numbers, $newNumbers, $string);
    }
}
if (!function_exists('showSvgaImage')) {
    function showSvgaImage(?string $url, ?string $uniqueKey): string
    {
        $model = 'this' . $uniqueKey;
        $model2 = 'this2' . $uniqueKey;

        Admin::script("
                    (function initSvga_{$model}() {
                        var el = document.getElementById('$model');
                        if (!el) {
                            // Element not ready, retry after a short delay
                            setTimeout(initSvga_{$model}, 50);
                            return;
                        }

                        try {
                            var $model = new SVGA.Player('#$model');
                            $model.loops = 100;
                            $model.clearsAfterStop = false;

                            var $model2 = new SVGA.Parser('#$model');

                            $model2.load('$url', function(videoItem) {
                                $model.setVideoItem(videoItem);
                                $model.startAnimation();

                                $model.onFinished(function() {
                                    // Code for when the animation finishes
                                });
                            });
                        } catch (error) {
                            console.error('SVGA Error for $model:', error.message);
                        }
                    })();
                ");
        return $model;
    }
}
if (!function_exists('showSvgaImage2')) {
    /**
     * @param string|null $url
     * @return string
     */
    function showSvgaImage2(?string $url, ?string $uniqueKey): string
    {
        $model = 'this' . $uniqueKey;
        $model2 = 'this2' . $uniqueKey;

        Admin::script("
                    (function initSvga2_{$model}() {
                        var el = document.getElementById('$model');
                        if (!el) {
                            // Element not ready, retry after a short delay
                            setTimeout(initSvga2_{$model}, 50);
                            return;
                        }

                        try {
                            var $model = new SVGA.Player('#$model');
                            $model.loops = 100;
                            $model.clearsAfterStop = false;

                            var $model2 = new SVGA.Parser('#$model');

                            $model2.load('$url', function(videoItem) {
                                $model.setVideoItem(videoItem);
                                $model.startAnimation();

                                $model.onFinished(function() {
                                    // Code for when the animation finishes
                                });
                            });
                        } catch (error) {
                            console.error('SVGA Error for $model:', error.message);
                        }
                    })();
                ");
        return $model;
    }

    // Global initializer for SVGA `.svga-player` elements
    Admin::script(
        <<<JS
if (typeof initSvgaPlayers === 'undefined') {
    function initSvgaPlayers(context = document) {
        context.querySelectorAll('.svga-player').forEach(el => {
            if (el.dataset.loaded) return;
            if (!el || !el.id) return; // Skip if element is not valid

            el.dataset.loaded = true;

            try {
                const player = new SVGA.Player(el);
                const parser = new SVGA.Parser(el);

                parser.load(el.dataset.url, videoItem => {
                    player.setVideoItem(videoItem);
                    player.loops = 100;
                    player.clearsAfterStop = false;
                    player.startAnimation();
                });
            } catch (error) {
                console.error('SVGA init error:', error.message);
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof initSvgaPlayers === 'function') initSvgaPlayers();
});

$(document).on('shown.bs.modal pjax:complete', function () {
    if (typeof initSvgaPlayers === 'function') {
        initSvgaPlayers();
        // Run again shortly after to handle cases where modal content is inserted after shown
        setTimeout(() => initSvgaPlayers(), 150);
    }
});

// Also watch for dynamically added `.svga-player` elements (covers Selectable/AJAX insertions)
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(mutations => {
        for (const m of mutations) {
            if (!m.addedNodes || m.addedNodes.length === 0) continue;
            m.addedNodes.forEach(node => {
                try {
                    if (node.nodeType !== 1) return; // element
                    if (node.classList && node.classList.contains('svga-player')) {
                        if (typeof initSvgaPlayers === 'function') initSvgaPlayers(node);
                    }
                    // also check descendants
                    if (node.querySelectorAll) {
                        const found = node.querySelectorAll('.svga-player');
                        if (found.length && typeof initSvgaPlayers === 'function') initSvgaPlayers(node);
                    }
                } catch (e) {
                    console.error('SVGA MutationObserver handler error:', e);
                }
            });
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
}
JS
    );
}
if (!function_exists('checkAgencyFeature')) {
    function checkAgencyFeature()
    {
        $app_feature = \Cache::get('host_agency');
        if (!($app_feature == '1' || $app_feature == 1)) {
            abort(403, __('This feature has not been activated for you'));
            //                return redirect()->back()->send();
        }
    }
}

if (!function_exists('truncateAndTrim')) {
    function truncateAndTrim($number, $decimals = 2)
    {
        $factor = pow(10, $decimals);
        $truncated = floor($number * $factor) / $factor;
        return rtrim(rtrim(number_format($truncated, $decimals, '.', ''), '0'), '.');
    }
}

if (!function_exists('clearAgencyCache')) {
    function clearAgencyCache($agencyId)
    {
        // Legacy V1 pagination-style keys
        $tabs = ['members', 'charges', 'salaries', 'requests', 'targets', 'rate', 'stars', 'heroes', 'giftlog'];

        foreach ($tabs as $tab) {
            for ($i = 1; $i <= 10; $i++) {
                Cache::forget("agency_{$agencyId}_{$tab}_page_{$i}");
            }

            if (in_array($tab, ['rate', 'stars', 'heroes'])) {
                for ($month = 1; $month <= 12; $month++) {
                    $year = date('Y');
                    Cache::forget("agency_{$agencyId}_{$tab}_{$month}_{$year}");
                }
            }
        }

        Cache::forget("agency_{$agencyId}_giftlog");

        // V2 AgencyController cache keys — must be cleared whenever gifts are sent
        $currentYear  = (int) date('Y');
        $currentMonth = (int) date('n');

        // Invalidate current month and the previous month to cover month-boundary edge cases
        $monthsToInvalidate = [
            [$currentYear, $currentMonth],
        ];

        // Add previous month
        $prevMonth = $currentMonth === 1 ? 12 : $currentMonth - 1;
        $prevYear  = $currentMonth === 1 ? $currentYear - 1 : $currentYear;
        $monthsToInvalidate[] = [$prevYear, $prevMonth];

        foreach ($monthsToInvalidate as [$y, $m]) {
            Cache::forget("agency_details_{$agencyId}_{$y}_{$m}");
            Cache::forget("agency_history_{$agencyId}_{$y}_{$m}");
            Cache::forget("agency_target_{$agencyId}_{$y}_{$m}");
            Cache::forget("agency_gifts_{$agencyId}_{$y}_{$m}");
        }
    }
}




if (!function_exists('getTimezone')) {
    function getTimezone()
    {
        $cacheKey = 'timezone';
        return \Cache::rememberForever($cacheKey, function () {
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return 'UTC';
            }
            $setting = \App\Models\Setting::where('key', 'timezone')->first();
            return $setting?->value ?? 'UTC';
        });
    }
}

if (!function_exists('getSettingCash')) {
    function getSettingCash($key)
    {
        return \Cache::rememberForever($key, function () use ($key) {
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return null;
            }
            $setting = \App\Models\Setting::where('key', $key)->first();
            return $setting?->value;
        });
    }
}

if (!function_exists('getCpGiftsStatus')) {
    function getCpGiftsStatus($key)
    {
        return \Cache::rememberForever($key, function () use ($key) {
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return true;
            }
            $setting = \App\Models\Setting::where('key', $key)->first();
            return $setting?->value ?? true;
        });
    }
}

if (!function_exists('getFavIcon')) {
    function getFavIcon(): ?string
    {
        return \Cache::rememberForever('favicon', function () {
            $favIcon = DB::table('settings')->where('key', 'app_fav_icon')->value('value');

            if ($favIcon) {
                return getImagePath($favIcon);
            }

            return asset('images/app-logo.png');
        });
    }
}

if (!function_exists('getAppLogo')) {
    function getAppLogo(): string
    {
        return Cache::rememberForever('appLogo', function () {
            $logo = DB::table('settings')->where('key', 'app_logo')->value('value');

            if ($logo) {
                return getImagePath($logo);
            }

            return asset('images/app-logo.png');
        });
    }
}

if (!function_exists('superadmin_url')) {
    function superadmin_url($path = '', $parameters = [], $secure = null)
    {
        if (\Illuminate\Support\Facades\URL::isValidUrl($path)) {
            return $path;
        }

        $base = trim(config('superadmin.route.prefix', 'superadmin'), '/');

        $secure = $secure ?? (config('superadmin.https') || config('superadmin.secure'));

        if (app()->environment(['production', 'Production'])) {
            return secure_url($base . '/' . trim($path, '/'), $parameters);
        }

        return url($base . '/' . trim($path, '/'), $parameters, $secure);
    }
}
if (!function_exists('dashboardName')) {
    function dashboardName()
    {
        if (request()->is('superadmin/*')) {
            return 'superadmin';
        } elseif (request()->is('areamanager/*')) {
            return 'areamanager';
        } else {
            return 'admin';
        }
    }
}

if (!function_exists('areaManager_url')) {
    function areaManager_url($path = '', $parameters = [], $secure = null)
    {
        if (\Illuminate\Support\Facades\URL::isValidUrl($path)) {
            return $path;
        }

        $base = trim(config('areaManager.route.prefix', 'areaManager'), '/');

        $secure = $secure ?? (config('areaManager.https') || config('areaManager.secure'));

        if (app()->environment(['production', 'Production'])) {
            return secure_url($base . '/' . trim($path, '/'), $parameters);
        }

        return url($base . '/' . trim($path, '/'), $parameters, $secure);
    }
}


if (!function_exists('getToday')) {
    function getToday(): array
    {
        $timezone = getTimezone();
        return [Carbon::now($timezone)->startOfDay()->timezone('UTC'), Carbon::now($timezone)->endOfDay()->timezone('UTC')];
    }
}

if (!function_exists('validateUploadedFileType')) {
    /**
     * @throws ValidationException
     */
    function validateUploadedFileType(UploadedFile $file, $itemId = null): string
    {
        $allowedExtensions = ['svga', 'svg', 'mp4', 'alpha', 'vap', 'png'];
        $ext = strtolower($file->guessExtension());
        $originalExt = strtolower($file->getClientOriginalExtension());

        // SVGA is a ZIP-based binary format — PHP/Symfony MIME detection
        // guesses 'zip' or returns empty.  Always trust the real .svga
        // extension so the file is never misclassified as text/image/other.
        if ($originalExt === 'svga') {
            $ext = 'svga';
        }

        if ($ext === 'zz' && $originalExt === 'svga') {
            $ext = 'svga';
        }

        if ($ext === 'gif' && $originalExt === 'gif') {
            $ext = 'png';
        }

        // White-label: UTD media-analyze endpoint is per-app (config/env). A clone
        // leaves it empty and this optional type-detection step is skipped.
        $analyzeUrl = config('services.utd_media.analyze_url');
        if ($ext === 'mp4' && $itemId && !empty($analyzeUrl)) {
            $urlVideo = upload($file);
            $videoPath = getDriverUrl() . '/' . $urlVideo;
            (new FfmpegService())->extractByDuration($videoPath, $itemId);
            $imagePath = (config('app.env') != 'production' ? '' : 'test-') . "frames/" . $itemId . '.jpg';
            $response = Http::attach(
                'image',
                Storage::disk('gcs')->get($imagePath),
                $itemId . '.jpg'
            )->post($analyzeUrl);
            $responseData = $response->json();
            if ($response->successful() && isset($responseData['data']['video_type'])) {
                $ext = strtolower($responseData['data']['video_type']);
            }
        }

        if (!in_array($ext, $allowedExtensions)) {
            throw ValidationException::withMessages([
                'img' => ['Invalid file type. Allowed extensions are: ' . implode(', ', $allowedExtensions)],
            ]);
        }

        return $ext;
    }
}

if (!function_exists('bd_url')) {
    /**
     * Get BD admin url.
     *
     * @param string $path
     * @param mixed  $parameters
     * @param bool   $secure
     *
     * @return string
     */
    function bd_url($path = '', $parameters = [], $secure = null)
    {
        if (\Illuminate\Support\Facades\URL::isValidUrl($path)) {
            return $path;
        }

        // حدد base path الخاص بوحدة BD
        $base = trim(config('bd.route.prefix', 'bd'), '/');

        /*$secure = $secure ?? (config('bd.https') || config('bd.secure'));

        if (app()->environment(['production', 'Production'])) {
            return secure_url($base . '/' . trim($path, '/'), $parameters);
        }*/
        // Always use HTTPS when APP_URL starts with https://
        return secure_url($base . '/' . trim($path, '/'), $parameters);
    }
}

if (!function_exists('shippingAdmin_url')) {
    /**
     * Get Shipping Super Admin portal url.
     *
     * @param string $path
     * @param mixed  $parameters
     * @param bool   $secure
     *
     * @return string
     */
    function shippingAdmin_url($path = '', $parameters = [], $secure = null)
    {
        if (\Illuminate\Support\Facades\URL::isValidUrl($path)) {
            return $path;
        }

        $base = trim(config('shippingAdmin.route.prefix', 'shippingAdmin'), '/');

        return secure_url($base . '/' . trim($path, '/'), $parameters);
    }
}

if (!function_exists('getGiftPercentage')) {
    /**
     * Get gift percentage by key from cache or DB
     * and return it as decimal out of 10.
     *
     * @param string $key
     * @return float
     */
    function getGiftPercentage(string $key): float
    {
        $cacheKey = "percentage_{$key}";

        $value = Cache::get($cacheKey);

        if ($value === null) {
            $value = \App\Models\Setting::where('key', $key)->value('value');
            if ($value !== null) {
                Cache::put($cacheKey, $value);
            }
        }
        if ($value === null) {
            $value = match ($key) {
                'app_wallet_lucky_gift' => 80,
                'owner_lucky_gift' => 10,
                'host_lucky_gift' => 10,
                default => 0,
            };
        }

        $percentage = round(((float) $value) / 10, 2);


        return $percentage;
    }
}

if (!function_exists('getCountryIdFromLatLong')) {
    function getCountryIdFromLatLong($lat, $lon, $register = true)
    {
        $responseEn = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (my@email.com)',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'addressdetails' => 1,
            'accept-language' => 'en',
        ]);

        if (!$responseEn->ok()) {
            return null;
        }

        $dataEn = $responseEn->json();
        $countryCode = $dataEn['address']['country_code'] ?? null;
        $countryNameEn = $dataEn['address']['country'] ?? null;

        $country = Country::where('iso', $countryCode)->first();
        if ($country) {
            return $country->id;
        }

        if ($register) {
            return null;
        }

        $responseAr = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (my@email.com)',
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'addressdetails' => 1,
            'accept-language' => 'ar',
        ]);

        $countryNameAr = null;
        if ($responseAr->ok()) {
            $dataAr = $responseAr->json();
            $countryNameAr = $dataAr['address']['country'] ?? null;
        }

        $country = Country::create([
            'iso' => $countryCode,
            'e_name' => $countryNameEn,
            'name' => $countryNameAr,
            'status' => 1,
        ]);

        return $country->id;
    }
}


if (!function_exists('respond_and_continue')) {
    function respond_and_continue($response, callable $callback)
    {
        $response->send();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }
        exit; // ensure no further output
    }
}


if (!function_exists('isValidTimezone')) {
    function isValidTimezone($tz)
    {
        $abbrs = \DateTimeZone::listAbbreviations();
        foreach ($abbrs as $abbreviation => $zones) {
            if (strcasecmp($abbreviation, $tz) === 0) {
                return true;
            }
        }
        return false;
    }
}


if (!function_exists('wallet_available_by_user')) {
    function wallet_available_by_user($userId)
    {
        $wallet = UserWallet::firstOrCreate(['user_id' => $userId]);

        $currentBalance = $wallet->balance ?? 0;
        $currentCutAmount = $wallet->cut_amount ?? 0;
        $currentPending = $wallet->pending_amount ?? 0;

        return $currentBalance - $currentCutAmount - $currentPending;
    }
}


if (!function_exists('wallet_curant_by_user')) {
    function wallet_curant_by_user($userId)
    {
        $wallet = UserWallet::firstOrCreate(['user_id' => $userId]);

        $currentBalance = $wallet->balance ?? 0;
        $currentPending = $wallet->pending_amount ?? 0;

        return $currentBalance - $currentPending;
    }
}

if (!function_exists('wallet_available_by_wallet')) {
    function wallet_available_by_wallet($wallet)
    {
        if (!$wallet) {
            return 0;
        }

        $currentBalance = $wallet->balance ?? 0;
        $currentCutAmount = $wallet->cut_amount ?? 0;
        $currentPending = $wallet->pending_amount ?? 0;
        return $currentBalance - $currentCutAmount - $currentPending;
    }
}

function formatLargeNumber($number): string
{
    if ($number >= 1000000000000) {
        return number_format($number / 1000000000000, 2) . 'Trillion'; // Trillion
    } elseif ($number >= 1000000000) {
        return number_format($number / 1000000000, 2) . 'Billion'; // Billion
    } elseif ($number >= 1000000) {
        return number_format($number / 1000000, 2) . 'Million'; // Million
    } elseif ($number >= 1000) {
        return number_format($number / 1000, 2) . 'Thousand'; // Thousand
    }
    return number_format($number);
}


if (! function_exists('getFairLuckSetting')) {
    function getFairLuckSetting(string $key, $default = null)
    {
        $setting = \App\Models\FairLuckSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}

if (! function_exists('badgeFilePreview')) {
    // Admin-form preview for a badge/animation file (svga, alpha/webp, vap/mp4,
    // plain image). Renders the actual asset instead of dumping raw bytes.
    function badgeFilePreview(string $uniqueId, ?string $path, int $width = 120, int $height = 120): string
    {
        if (!$path) {
            return '';
        }

        $url = getImagePath($path);
        $safeUrl = e($url);
        $ext = strtolower(getFileExtension($url));
        $fileName = e(basename(parse_url($url, PHP_URL_PATH) ?? $path));

        if ($ext === 'svga' || $ext === 'zz') {
            // SVGA inside RTL layouts must target a <canvas> directly, not a
            // wrapper <div> (rendering breaks otherwise).
            $canvasId = 'badge_preview_' . $uniqueId;
            $jsUrl = json_encode($url);

            Admin::script(<<<JS
(function () {
    var tries = 0;
    (function initBadgePreview() {
        var el = document.getElementById('{$canvasId}');
        if ((!el || typeof SVGA === 'undefined') && tries++ < 100) {
            setTimeout(initBadgePreview, 100);
            return;
        }
        if (!el || typeof SVGA === 'undefined') {
            return;
        }
        try {
            var player = new SVGA.Player('#{$canvasId}');
            var parser = new SVGA.Parser('#{$canvasId}');
            player.loops = 0;
            player.clearsAfterStop = false;
            parser.load({$jsUrl}, function (videoItem) {
                player.setVideoItem(videoItem);
                player.startAnimation();
            });
        } catch (e) {
            console.error('Badge preview SVGA error:', e);
        }
    })();
})();
JS);

            return "<canvas id='{$canvasId}' width='{$width}' height='{$height}' style='display:block;'></canvas>"
                . "<span class='help-block' style='margin-top:4px;'>{$fileName}</span>";
        }

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp'])) {
            return "<img src='{$safeUrl}' alt='' style='width:{$width}px;height:{$height}px;object-fit:contain;display:block;' />"
                . "<span class='help-block' style='margin-top:4px;'>{$fileName}</span>";
        }

        if (in_array($ext, ['mp4', 'webm', 'mov', 'vap'])) {
            return "<video width='{$width}' height='{$height}' controls autoplay muted loop style='display:block;'>"
                . "<source src='{$safeUrl}' type='video/mp4'>"
                . '</video>'
                . "<span class='help-block' style='margin-top:4px;'>{$fileName}</span>";
        }

        $size = '';
        try {
            $bytes = \Illuminate\Support\Facades\Storage::size($path);
            $size = ' (' . round($bytes / 1024, 1) . ' KB)';
        } catch (\Throwable $e) {
            // remote/missing file: name only
        }

        return "<span class='help-block'>{$fileName}{$size}</span>";
    }
}
