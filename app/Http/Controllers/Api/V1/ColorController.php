<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Color;
use App\Http\Controllers\Controller;
use App\Helpers\Common;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class ColorController extends Controller
{
    /**
     * Brand default hex for every color key the app consumes.
     * The colors/v2 endpoint must NEVER return "" for a color: an empty value
     * breaks color parsing in the app (CSS error: the specified value "" does
     * not conform to "#rrggbb") and blanks the whole UI to white.
     */
    private const COLOR_DEFAULTS = [
        'app_primary_color'        => '#F0D060',
        'app_secondary_color'      => '#003FA6',
        'bottom_nav_bottom_color'  => '#05060A',
        'bottom_nav_active_color'  => '#F0D060',
        'bottom_nav_inactive_color' => '#9C8A52',
        'text_header_color'        => '#05060A',
        'button_text_color'        => '#14110A',
        'text_primary_color'       => '#000000',
        'text_secondary_color'     => '#707070',
        'icon_color'               => '#000000',
        'card_color'               => '#FFFFFF',
        'dark_mode_color'          => '#05060A',
        'light_mode_color'         => '#FFFFFF',
    ];

    /** TTL for the assembled colors/v2 blob (seconds). */
    private const BLOB_TTL = 3600;

    /**
     * Every Setting key the colors/v2 payload reads. Colors are FIXED to
     * COLOR_DEFAULTS (owner decision: colors live in the Flutter code per
     * theme, the panel no longer manages them) — only the nav icons remain
     * dynamic, so they are the only keys fetched.
     */
    private const PAYLOAD_KEYS = [
        // nav icons: 5 tabs × {legacy, active, inactive}
        'nav_icon_1', 'nav_icon_active_1', 'nav_icon_inactive_1',
        'nav_icon_2', 'nav_icon_active_2', 'nav_icon_inactive_2',
        'nav_icon_3', 'nav_icon_active_3', 'nav_icon_inactive_3',
        'nav_icon_4', 'nav_icon_active_4', 'nav_icon_inactive_4',
        'nav_icon_5', 'nav_icon_active_5', 'nav_icon_inactive_5',
    ];

    /**
     * Pre-fetched settings map for the current build pass (key => value). Only
     * the nav-icon keys are fetched now — every color resolves from the fixed
     * COLOR_DEFAULTS, never from settings (stored legacy values are ignored so
     * they can't reach older app builds either).
     *
     * @var array<string,?string>
     */
    private array $settings = [];

    /**
     * Fixed color for a key. Settings are deliberately NOT consulted: colors
     * are baked into the Flutter code per theme (owner decision).
     */
    private function colorSetting(string $key): string
    {
        return self::COLOR_DEFAULTS[$key] ?? '#F0D060';
    }

    /**
     * Fixed solid ColorToken sibling ({key}_grad shape kept for the app
     * contract): {type:'solid', colors:['#RRGGBB']}.
     */
    private function colorToken(string $key): array
    {
        return [
            'type'   => 'solid',
            'colors' => [$this->colorSetting($key)],
        ];
    }

    public function index()
    {
        // Legacy v1 endpoint — colors are fixed in code now (owner decision),
        // so stored/cached values are never emitted.
        $data = [
            'main_color' => self::COLOR_DEFAULTS['app_primary_color'],
            'secondary_colors' => self::COLOR_DEFAULTS['app_secondary_color'],
            'white_color' => '',
            'black_color' => '',
            'grey_color' => '',
            'yellow_color' => '',
            'background_color' => '#FFFFFF',
            'background_image' => '',
            'gradient_1' => '',
            'gradient_2' => '',
            'gradient_3' => '',
        ];

        return Common::apiResponse(true, '', $data, 200);
    }


    public function appCollor()
    {
        // Single versioned, lock-coalesced blob. The whole colors/v2 payload is
        // cached under one key carrying the theme version (colors_updated_at,
        // bumped by SettingObserver on any theme save) — invalidation is by
        // version change, never a 30-key forget storm (cache-first + version
        // invalidation). A panel save changes the version → a new key → the old
        // blob ages out by TTL on its own.
        $version = (string) (settings()->get('colors_updated_at') ?? '0');
        $cacheKey = 'colors_v2_payload_v' . $version;

        $data = Cache::get($cacheKey);
        if ($data !== null) {
            return Common::apiResponse(true, '', $data, 200);
        }

        // Coalesce the thundering herd: exactly one request rebuilds the blob
        // (a single whereIn->pluck), the rest wait up to a few seconds for that
        // result instead of all hammering the DB with cold fan-out queries.
        $lock = Cache::lock($cacheKey . '_lock', 10);
        try {
            $data = $lock->block(5, function () use ($cacheKey) {
                // Double-check: the winner may have already filled it while we waited.
                $cached = Cache::get($cacheKey);
                if ($cached !== null) {
                    return $cached;
                }
                $built = $this->buildPayload();
                Cache::put($cacheKey, $built, self::BLOB_TTL);
                return $built;
            });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            // Could not acquire/serve within the window: build directly so the
            // request still succeeds (degrades to the old behavior for THIS
            // request only, never a 500).
            $data = $this->buildPayload();
            Cache::put($cacheKey, $data, self::BLOB_TTL);
        } finally {
            optional($lock)->release();
        }

        return Common::apiResponse(true, '', $data, 200);
    }

    /**
     * Assemble the full colors/v2 payload. Colors are FIXED (COLOR_DEFAULTS);
     * only the nav icons are read from settings — one whereIn->pluck.
     */
    private function buildPayload(): array
    {
        $this->settings = Setting::whereIn('key', self::PAYLOAD_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        // Fixed solid regions: body defaults to white, nav to the fixed
        // bottom-nav color. Shape kept identical for the app contract.
        $bodyRegion = [
            'type'      => 'color',
            'colors'    => ['#FFFFFF'],
            'image'     => '',
            'direction' => 'vertical',
            'reverse'   => false,
        ];
        $navRegion = [
            'type'      => 'color',
            'colors'    => [$this->colorSetting('bottom_nav_bottom_color')],
            'image'     => '',
            'direction' => 'vertical',
            'reverse'   => false,
        ];

        return [
            "primary_color" => $this->colorSetting('app_primary_color'),
            "secondary_color" => $this->colorSetting('app_secondary_color'),
            // Legacy single-value background field — always a safe solid color.
            "background" => [
                "value" => $bodyRegion['colors'][0],
                "type" => 'color',
            ],
            "regions" => [
                "body" => $bodyRegion,
                "nav"  => $navRegion,
            ],
            "nav_icons" => $this->navIcons(),
            "bottom_nav" => [
                "bottom_color" => $this->colorSetting('bottom_nav_bottom_color'),
                "active_color" => $this->colorSetting('bottom_nav_active_color'),
                "inactive_color" => $this->colorSetting('bottom_nav_inactive_color'),
            ],
            "text_header_color" => $this->colorSetting('text_header_color'),
            "button_text_color" => $this->colorSetting('button_text_color'),
            "text_primary_color" => $this->colorSetting('text_primary_color'),
            "text_secondary_color" => $this->colorSetting('text_secondary_color'),
            "icon_color" => $this->colorSetting('icon_color'),
            "card_color" => $this->colorSetting('card_color'),

            // ColorToken siblings kept for the app contract — always solid,
            // derived from the same fixed defaults as the flat strings above.
            "app_primary_color_grad"   => $this->colorToken('app_primary_color'),
            "text_header_color_grad"   => $this->colorToken('text_header_color'),
            "button_text_color_grad"   => $this->colorToken('button_text_color'),
            "text_primary_color_grad"  => $this->colorToken('text_primary_color'),
            "text_secondary_color_grad" => $this->colorToken('text_secondary_color'),
            "icon_color_grad"          => $this->colorToken('icon_color'),
            "card_color_grad"          => $this->colorToken('card_color'),

            'body_color' => [
                "dark_mode_color" => $this->colorSetting('dark_mode_color'),
                "light_mode_color" => $this->colorSetting('light_mode_color'),
            ],
        ];
    }

    /**
     * The five bottom-nav tabs, each as {active, inactive} full URLs ('' when
     * unset). Order is fixed: Home, Explore/Games, Chat, Moment/World, Profile.
     * The app fetches and caches these. Back-compat: when a per-state key is
     * unset it falls back to the legacy single nav_icon_N upload so existing
     * setups keep rendering their old icon for both states.
     */
    private function navIcons(): array
    {
        $read = fn(string $k) => $this->settings[$k] ?? null;
        $url = fn($v) => is_string($v) && trim($v) !== '' ? getImagePath($v) : '';

        $icons = [];
        for ($i = 1; $i <= 5; $i++) {
            $legacy   = $read("nav_icon_$i");
            $active   = $read("nav_icon_active_$i");
            $inactive = $read("nav_icon_inactive_$i");
            $icons[] = [
                'active'   => $url(is_string($active) && trim($active) !== '' ? $active : $legacy),
                'inactive' => $url(is_string($inactive) && trim($inactive) !== '' ? $inactive : $legacy),
            ];
        }
        return $icons;
    }
}
