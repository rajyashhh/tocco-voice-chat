<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Brand identity + colour DEFAULTS seeder.
 *
 * ROOT PROBLEM this fixes: on a fresh install the `settings` table carried ZERO
 * identity/colour rows. The mobile API still rendered fine (its ColorController
 * and RefreshThemeConfig middleware fall back to hard-coded defaults with `??`),
 * but the ADMIN panel's brand page reads the raw DB rows — so every field showed
 * blank. A buyer of the source then had to retype the whole identity by hand.
 *
 * The owner's requirement: when we sell the source, the buyer must find name,
 * logo and colours ALREADY populated with sensible brand defaults; they edit or
 * clear them later if they wish, but must never rebuild everything from scratch.
 *
 * So we seed the identity + colour keys as REAL rows using firstOrCreate:
 *   - A fresh clone gets branded, panel-visible defaults out of the box.
 *   - firstOrCreate (never updateOrCreate) means re-running NEVER overwrites a
 *     value the owner already entered in the panel — safe to run on the demo and
 *     on every future migrate+seed.
 *
 * Colour values are byte-identical to Api\V1\ColorController::COLOR_DEFAULTS and
 * the RefreshThemeConfig fallbacks, so the panel now displays exactly what the
 * app has always rendered — no visual change, just the rows made explicit.
 *
 * Logo/favicon are intentionally NOT seeded to a file path here: the image is an
 * asset the owner uploads from the panel (a seeded path to a non-existent file
 * would render broken). The name + colours are the safe, file-free defaults.
 */
class BrandIdentityDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // ── Identity (name / title) ──────────────────────────────────────
            'app_title_en' => 'Meow Live',
            'app_title_ar' => 'ميو لايف',

            // ── App colour tokens (panel + API, must match COLOR_DEFAULTS) ────
            'app_primary_color'    => '#F0D060',
            'app_secondary_color'  => '#003FA6',
            'text_header_color'    => '#05060A',
            'button_text_color'    => '#14110A',
            'text_primary_color'   => '#000000',
            'text_secondary_color' => '#707070',
            'icon_color'           => '#000000',
            'card_color'           => '#FFFFFF',

            // ── Bottom navigation state colours ──────────────────────────────
            'bottom_nav_bottom_color'   => '#05060A',
            'bottom_nav_active_color'   => '#F0D060',
            'bottom_nav_inactive_color' => '#9C8A52',

            // ── Admin-panel theme colours (RefreshThemeConfig fallbacks) ─────
            'primary_color'          => '#FF9428',
            'secondary_color'        => '#1A1A1A',
            'panel_text_color'       => '#ffffff',
            'box_background_color'   => '#222222',
            'table_background_color' => '#c88213',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}