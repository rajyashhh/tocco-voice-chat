<?php

namespace App\Admin\Controllers;

use App\Models\CoreWallet;
use App\Models\FairLuckSetting;
use App\Models\FairLuckWallet;
use App\Models\FairLuckWalletHistory;
use App\Models\Setting;
use App\Services\FairLuck\V7\PoolManager;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * FairLuck settings — the single write path for every owner-tunable lucky-gift
 * key (§6). Three sections, all backed by the fair_luck_settings rows the
 * 2026_06_13 migrations seed (or that the engine reads via getByKey defaults):
 *
 *   1. ECONOMY      app profit% / receiver share% / RTP%  (3 EV knobs)
 *   2. BEGINNER     enabled / budget_coins / max_age_days / global_daily_cap / RTP_boost
 *   3. ADAPTIVE     wallet|rtp|peak|room-activity modulators + solvency taper toggle
 *
 * Validation rejects (never silently clamps in the panel) app + receiver + RTP
 * that would exceed 100% — the engine still clamps as defense-in-depth, but the
 * panel must not let an unsustainable config be saved.
 *
 * The GET route doubles as the ajax=history feed for the live vault chart; it
 * reads the unified_vault history with a raw live balance (NO getRedisBalance —
 * that seeds the key via SETNX, a write the monitor must never perform).
 */
class FairLuckSettingsController extends AdminController
{
    protected $title = 'FairLuck Settings';

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . 'lucky-gift-setting');
        }

        // AJAX endpoint for the live vault chart on admin/lucky-gift-settings.
        if (request()->has('ajax') && request('ajax') === 'history') {
            $limit = (int) request('limit', 500);
            $history = FairLuckWalletHistory::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()->reverse()->values()
                ->map(fn($h) => [
                    'date' => $h->created_at?->format('m-d H:i:s') ?? '',
                    'before' => (int) $h->balance_before,
                    'change' => (int) $h->amount,
                    'after' => (int) $h->balance_after,
                    'desc' => $h->description ?? 'Transaction',
                ]);
            return response()->json($history);
        }

        return redirect(admin_url('lucky-gift-settings'));
    }

    /**
     * The SINGLE write path for the whole lucky-gift settings page (one form, one
     * save). Four sections in order: economy → beginner protection → shape
     * modulators → safety/display. Validation rejects (never silently clamps) any
     * unsustainable or out-of-range value. The removed modulators (peak hours,
     * room activity — manual/inert, no live input source) are force-disabled here
     * so they can never apply regardless of stale rows.
     */
    public function saveSettings(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . 'lucky-gift-setting');
        }

        // ── 1) Economy ──────────────────────────────────────────────────────
        $app = (float) $request->input('fair_luck_owner_fee_rate', -1);
        $recv = (float) $request->input('fair_luck_receiver_fee_rate', -1);
        $rtp = (float) $request->input('V7_target_rtp', -1);
        $negativeLimit = (int) $request->input('V7_negative_limit', 30_000);

        if ($app < 0 || $app > 20) {
            admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_app_profit_range'));
            return back();
        }
        if ($recv < 0 || $recv > 50) {
            admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_receiver_range'));
            return back();
        }
        if ($rtp < 50) {
            admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_rtp_min'));
            return back();
        }
        if ($negativeLimit < 0) {
            admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_negative_limit_positive'));
            return back();
        }

        // The sustainable ceiling: only (100 − app − receiver)% of every bet ever
        // reaches the payout vault. app + receiver + RTP > 100% drains the vault
        // until the gate strangles the high tiers — REJECT with the exact maximum.
        $ceiling = round(100.0 - $app - $recv, 2);
        if ($app + $recv + $rtp > 100.0) {
            admin_error(
                __('admin.fair_luck_unsustainable_title'),
                __('admin.fair_luck_unsustainable_body', ['ceiling' => $ceiling])
            );
            return back();
        }

        // ── 2) Beginner protection ──────────────────────────────────────────
        $beginnerEnabled = $request->boolean('beginner_protection_enabled');
        $budget = (int) $request->input('beginner_budget_coins', 0);
        $maxAge = (int) $request->input('beginner_max_age_days', 10);
        $dailyCap = (int) $request->input('beginner_global_daily_cap', 0);
        $rtpBoost = (float) $request->input('RTP_boost', 92);

        if ($budget < 0 || $dailyCap < 0) {
            admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_budget_cap_positive'));
            return back();
        }
        if ($maxAge < 1 || $maxAge > 365) {
            admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_max_age_range'));
            return back();
        }
        if ($rtpBoost < 50 || $rtpBoost > 100) {
            admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_rtp_boost_range'));
            return back();
        }

        // ── 3) Shape modulators (variance/timing only — never lift realised RTP).
        // Merged enabled+strength: a strength > 0 means the modulator is on.
        $walletStrength = (float) $request->input('mod_wallet_strength', 0);
        $rtpStrength = (float) $request->input('mod_rtp_strength', 0);
        foreach (['mod_wallet_strength' => $walletStrength, 'mod_rtp_strength' => $rtpStrength] as $v) {
            if ($v < 0 || $v > 1) {
                admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_modulator_strength_range'));
                return back();
            }
        }
        $taperEnabled = $request->boolean('solvency_taper_enabled');

        // ── 4) Safety / display ─────────────────────────────────────────────
        $luckyGiftCoins = (int) $request->input('lucky_gift_coins', 2000);
        if ($luckyGiftCoins < 0) {
            admin_error(__('admin.fair_luck_error_title'), __('admin.fair_luck_win_announce_positive'));
            return back();
        }

        // Master on/off switch — reuse the existing stop_luckyGift kill-switch
        // (settings.json). enabled => stop_luckyGift=0 ; disabled => 1. When off,
        // sendLuckyGiftV2 refuses every send with "هدايا الحظ معطلة الآن".
        settings()->set('stop_luckyGift', $request->boolean('lucky_gift_enabled') ? 0 : 1);

        $this->putMany([
            // economy
            'fair_luck_owner_fee_rate'    => $app / 100,
            'fair_luck_receiver_fee_rate' => $recv / 100,
            'V7_target_rtp'               => $rtp / 100,
            'V7_negative_limit'           => $negativeLimit,
            // beginner protection
            'beginner_protection_enabled' => $beginnerEnabled ? 1 : 0,
            'beginner_budget_coins'       => $budget,
            'beginner_max_age_days'       => $maxAge,
            'beginner_global_daily_cap'   => $dailyCap,
            'RTP_boost'                   => round($rtpBoost / 100, 4),
            // shape modulators (enabled derived from strength)
            'mod_wallet_enabled'          => $walletStrength > 0 ? 1 : 0,
            'mod_wallet_strength'         => $walletStrength,
            'mod_rtp_enabled'             => $rtpStrength > 0 ? 1 : 0,
            'mod_rtp_strength'            => $rtpStrength,
            'solvency_taper_enabled'      => $taperEnabled ? 1 : 0,
            // removed modulators (no live input) — force OFF so stale rows can't apply
            'mod_peak_enabled'            => 0,
            'mod_peak_active'             => 0,
            'mod_peak_strength'           => 0,
            'mod_room_activity_enabled'   => 0,
            'mod_room_activity_factor'    => 0,
        ]);

        // Big-win banner/sound threshold lives in the general Setting table (read by
        // LuckyEngine + WinLuckyGift). Mirror the cache write used elsewhere.
        Setting::updateOrCreate(['key' => 'lucky_gift_coins'], ['value' => $luckyGiftCoins]);
        Cache::forget('lucky_gift_coins');
        Cache::put('lucky_gift_coins', $luckyGiftCoins);

        $this->flushSettingsCache();
        admin_success(__('admin.fair_luck_saved_title'), __('admin.fair_luck_saved_body'));
        return back();
    }

    /**
     * @param array<string,int|float|string> $pairs
     */
    private function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            FairLuckSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * Invalidate BOTH caches the engine reads: the settings pluck and the derived
     * RTP_eff (recomputed from the new rates on next draw).
     */
    private function flushSettingsCache(): void
    {
        Cache::forget('fair_luck:settings');
    }
}
