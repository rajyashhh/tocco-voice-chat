<?php

namespace App\Services;

use App\Models\Ban;
use App\Models\BanType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for ban checks on the authenticated read path.
 *
 * PERFORMANCE FIX: GeneralBanMiddleware and UserBanMiddleware each loaded the
 * (unused) packs relation and ran their own ban queries on every request. The
 * overwhelmingly common case is "not banned", yet every request paid 2-3
 * uncached queries. BanGuard fetches the user's currently-active bans once,
 * caches the small result for a short TTL, and serves both the login-ban
 * (GeneralBanMiddleware, 501) and action-ban (UserBanMiddleware, 377) decisions
 * from that single cached snapshot. The cache is invalidated whenever a ban is
 * applied or lifted (BanUser / RemoveBanUser).
 *
 * No default fallback: a cache miss reads the DB (source of truth) once.
 */
class BanGuard
{
    /** Short TTL: a request may see a just-applied ban up to this many seconds late. */
    private const TTL_SECONDS = 12;

    /**
     * Login-ban message (account/device/ip ban), or null if none.
     */
    public function loginBanMessage(?string $uuid, Request $request): ?string
    {
        if ($uuid === null) {
            return null;
        }

        $bans = $this->activeBans($uuid, $request);

        $ip = $request->ip();
        $device = $request->header('x-device-token');

        foreach ($bans as $ban) {
            if ($ban['type'] === 'action') {
                continue;
            }

            $matches = ($ban['uid'] === $uuid)
                || ($ban['ip'] !== null && $ban['ip'] === $ip)
                || ($ban['device_number'] !== null && $ban['device_number'] === $device);

            if (!$matches) {
                continue;
            }

            $description = app()->getLocale() == 'ar' ? $ban['description_ar'] : $ban['description_en'];
            $banType = match ($ban['type']) {
                'normal' => __('api.account'),
                'device' => __('api.device'),
                default => __('api.ip'),
            };

            return __('api.login_ban', [
                'type'        => $banType,
                'time'        => $ban['duration'],
                'description' => $description,
            ]);
        }

        return null;
    }

    /**
     * Action-ban message for the current route, or null if none.
     */
    public function actionBanMessage(?string $uuid, Request $request): ?string
    {
        if ($uuid === null) {
            return null;
        }

        $route = $this->routeFromRequest($request);
        $method = $request->method();

        $banType = BanType::where('route', $route)
            ->where(function ($query) use ($method) {
                $query->where('method', $method)->orWhereNull('method');
            })
            ->first();

        if (!$banType) {
            return null;
        }

        $bans = $this->activeBans($uuid, $request);

        foreach ($bans as $ban) {
            if ($ban['type'] !== 'action') {
                continue;
            }
            if ($ban['ban_type_id'] === null || (int) $ban['ban_type_id'] !== (int) $banType->id) {
                continue;
            }
            if ($ban['uid'] !== $uuid) {
                continue;
            }

            return __('api.type_ban', [
                'type'        => app()->getLocale() == 'ar' ? ($banType->name_ar ?? '') : ($banType->name_en ?? ''),
                'time'        => $ban['duration'],
                'description' => app()->getLocale() == 'ar' ? $ban['description_ar'] : $ban['description_en'],
            ]);
        }

        return null;
    }

    /**
     * Invalidate the cached ban snapshot for a user (call after apply/lift ban).
     */
    public static function invalidate(?string $uuid): void
    {
        if ($uuid === null) {
            return;
        }
        Cache::forget(self::cacheKey($uuid));
    }

    /**
     * The user's currently-active bans, cached as a plain array for the TTL.
     * Returns [] for the common (not-banned) case.
     *
     * @return array<int, array<string, mixed>>
     */
    private function activeBans(string $uuid, Request $request): array
    {
        $cacheKey = self::cacheKey($uuid);

        return Cache::remember($cacheKey, self::TTL_SECONDS, function () use ($uuid, $request) {
            $now = now();

            return Ban::query()
                ->where(function ($q) use ($uuid, $request) {
                    $q->where('uid', $uuid)
                        ->orWhere(function ($q) use ($request) {
                            $q->whereNotNull('ip')->where('ip', $request->ip());
                        })
                        ->orWhere(function ($q) use ($request) {
                            $q->whereNotNull('device_number')->where('device_number', $request->header('x-device-token'));
                        });
                })
                // Expiry check with a bound parameter (no $now string interpolation
                // => no injection). Rows are first narrowed by the bans(uid) index
                // from the OR tree, so the per-row duration arithmetic runs only on
                // the handful of matched rows instead of a full table scan.
                ->whereRaw('DATE_ADD(created_at, INTERVAL duration HOUR) > ?', [$now])
                ->get([
                    'uid', 'type', 'ip', 'device_number', 'duration',
                    'description_ar', 'description_en', 'ban_type_id', 'created_at',
                ])
                ->map(fn ($ban) => [
                    'uid'            => $ban->uid,
                    'type'           => $ban->type,
                    'ip'             => $ban->ip,
                    'device_number'  => $ban->device_number,
                    'duration'       => $ban->duration,
                    'description_ar' => $ban->description_ar,
                    'description_en' => $ban->description_en,
                    'ban_type_id'    => $ban->ban_type_id,
                ])
                ->all();
        });
    }

    private function routeFromRequest(Request $request): string
    {
        $path = parse_url($request->url(), PHP_URL_PATH);
        $path = ltrim((string) $path, '/');
        $segments = explode('/', $path);

        return implode('/', array_slice($segments, 1));
    }

    private static function cacheKey(string $uuid): string
    {
        return 'user_ban_state_' . $uuid;
    }
}
