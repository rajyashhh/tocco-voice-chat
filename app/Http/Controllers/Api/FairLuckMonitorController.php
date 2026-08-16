<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoreWallet;
use App\Models\FairLuckSetting;
use App\Models\FairLuckTransaction;
use App\Models\FairLuckWallet;
use App\Models\FairLuckWalletHistory;
use App\Services\FairLuck\V7\PoolManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FairLuckMonitorController extends Controller
{
    public function dashboard()
    {
        // Set a strict 10s query timeout so heavy queries fail fast instead of 504
        try {
            DB::statement("SET SESSION MAX_EXECUTION_TIME=10000");
        } catch (\Throwable $e) {
            // MySQL < 5.7.8 doesn't support this, ignore
        }

        // Settings
        $settings = FairLuckSetting::pluck('value', 'key')->toArray();
        $appFee = (float) ($settings['fair_luck_owner_fee_rate'] ?? 0.01);
        $receiverFee = (float) ($settings['fair_luck_receiver_fee_rate'] ?? 0.10);
        $targetRtp = (float) ($settings['V7_target_rtp'] ?? 0.89);

        // ── Vault + owner: RAW reads only (never getRedisBalance — it SETNX-seeds) ──
        $live = $this->liveVaultState();
        $vaultBalance = $live['vault'];
        $ownerDurable = $live['ownerDurable'];
        $ownerAccrual = $live['ownerAccrual'];
        $ownerTotal = $ownerDurable + $ownerAccrual;
        $dbVault = (int) (FairLuckWallet::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)->value('balance') ?? 0);

        // ── Solvency / integrity indicators (§6) ──
        $negativeLimit = FairLuckSetting::getVaultNegativeLimit();
        $taperEnabled = filter_var($settings['solvency_taper_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $taperThreshold = (int) round(3.0 * $negativeLimit); // SOLVENCY_TAPER_FACTOR
        $taperActive = $taperEnabled && $negativeLimit > 0 && $vaultBalance < $taperThreshold;

        // Drain risk: the largest configured tier can no longer be paid → wins get
        // downgraded by drain (the win_downgraded_by_drain condition, live).
        $largestMult = 2000; // MultiplierTable::DEFAULT_MULTIPLIERS tail
        $drainRisk = ($vaultBalance + $negativeLimit) <= 0;

        $bp = $this->beginnerCapState($settings);
        $integrity = $this->integrityCounters();

        // Zone
        $thresholds = [
            'min' => (int) ($settings['V7_wallet_min'] ?? 10000),
            'tight' => (int) ($settings['V7_wallet_tight'] ?? 50000),
            'target' => (int) ($settings['V7_wallet_target'] ?? 200000),
            'high' => (int) ($settings['V7_wallet_high'] ?? 500000),
        ];

        // Overall stats
        $allTime = DB::selectOne("
            SELECT COUNT(*) as total, SUM(CASE WHEN is_winner=1 THEN 1 ELSE 0 END) as wins,
                   SUM(bet_amount) as total_bet,
                   SUM(CASE WHEN is_winner=1 THEN profit_amount+bet_amount ELSE 0 END) as total_won,
                   SUM(profit_amount) as net_profit
            FROM fair_luck_transactions
        ");

        // Today stats
        $today = DB::selectOne("
            SELECT COUNT(*) as total, SUM(CASE WHEN is_winner=1 THEN 1 ELSE 0 END) as wins,
                   SUM(bet_amount) as total_bet,
                   SUM(CASE WHEN is_winner=1 THEN profit_amount+bet_amount ELSE 0 END) as total_won,
                   SUM(profit_amount) as net_profit,
                   COUNT(DISTINCT user_id) as unique_users
            FROM fair_luck_transactions WHERE created_at >= CURDATE()
        ");

        // Last hour
        $lastHour = DB::selectOne("
            SELECT COUNT(*) as total, SUM(CASE WHEN is_winner=1 THEN 1 ELSE 0 END) as wins,
                   SUM(bet_amount) as total_bet,
                   SUM(profit_amount) as net_profit,
                   COUNT(DISTINCT user_id) as unique_users
            FROM fair_luck_transactions WHERE created_at >= NOW() - INTERVAL 1 HOUR
        ");

        // Top winners today
        $topWinnersToday = DB::select("
            SELECT user_id, COUNT(*) as spins,
                   SUM(CASE WHEN is_winner=1 THEN 1 ELSE 0 END) as wins,
                   SUM(bet_amount) as total_bet,
                   SUM(CASE WHEN is_winner=1 THEN profit_amount+bet_amount ELSE 0 END) as total_won,
                   MAX(multiplier) as max_mult
            FROM fair_luck_transactions WHERE created_at >= CURDATE()
            GROUP BY user_id ORDER BY total_won DESC LIMIT 10
        ");

        // Biggest wins today
        $bigWinsToday = DB::select("
            SELECT user_id, bet_amount, multiplier, profit_amount, created_at
            FROM fair_luck_transactions
            WHERE is_winner=1 AND created_at >= CURDATE()
            ORDER BY profit_amount DESC LIMIT 15
        ");

        // Multiplier distribution today
        $multDist = DB::select("
            SELECT COALESCE(multiplier, 0) as mult,
                   COUNT(*) as cnt,
                   SUM(CASE WHEN is_winner=1 THEN profit_amount+bet_amount ELSE 0 END) as total_payout
            FROM fair_luck_transactions WHERE created_at >= CURDATE()
            GROUP BY mult ORDER BY mult
        ");

        // Vault history (last 200) — UNIFIED vault now, not the dead global_vault.
        $vaultHistory = FairLuckWalletHistory::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get()->reverse()->values()
            ->map(fn($h) => [
                'time' => $h->created_at?->format('H:i:s') ?? '',
                'date' => $h->created_at?->format('m-d H:i') ?? '',
                'before' => (int) $h->balance_before,
                'after' => (int) $h->balance_after,
                'change' => (int) $h->amount,
                'desc' => $h->description ?? '',
            ]);

        // Hourly breakdown today
        $hourly = DB::select("
            SELECT HOUR(created_at) as hr, COUNT(*) as spins,
                   SUM(CASE WHEN is_winner=1 THEN 1 ELSE 0 END) as wins,
                   SUM(bet_amount) as bet, SUM(profit_amount) as profit
            FROM fair_luck_transactions WHERE created_at >= CURDATE()
            GROUP BY hr ORDER BY hr
        ");

        // Per-user RTP for active users today
        $userRtps = DB::select("
            SELECT user_id, COUNT(*) as spins,
                   SUM(bet_amount) as total_bet,
                   SUM(CASE WHEN is_winner=1 THEN profit_amount+bet_amount ELSE 0 END) as total_won
            FROM fair_luck_transactions WHERE created_at >= CURDATE()
            GROUP BY user_id HAVING spins >= 10
            ORDER BY spins DESC LIMIT 20
        ");

        return response(
            $this->renderHtml(
                $vaultBalance, $dbVault, $ownerTotal, $ownerDurable, $ownerAccrual, $thresholds,
                $allTime, $today, $lastHour, $topWinnersToday, $bigWinsToday,
                $multDist, $vaultHistory, $hourly, $userRtps,
                $appFee, $receiverFee, $targetRtp,
                $negativeLimit, $taperEnabled, $taperActive, $taperThreshold,
                $drainRisk, $largestMult, $bp, $integrity
            ),
            200,
            ['Content-Type' => 'text/html']
        );
    }

    public function apiStats()
    {
        $live = $this->liveVaultState();
        $bp = $this->beginnerCapState(FairLuckSetting::pluck('value', 'key')->toArray());
        $integrity = $this->integrityCounters();
        $lastHour = DB::selectOne("
            SELECT COUNT(*) as total, SUM(CASE WHEN is_winner=1 THEN 1 ELSE 0 END) as wins,
                   SUM(bet_amount) as bet, SUM(profit_amount) as profit
            FROM fair_luck_transactions WHERE created_at >= NOW() - INTERVAL 1 HOUR
        ");
        $history = FairLuckWalletHistory::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)
            ->orderBy('created_at', 'desc')->limit(200)
            ->get()->reverse()->values()
            ->map(fn($h) => ['t' => $h->created_at?->format('H:i:s'), 'v' => (int) $h->balance_after]);

        return response()->json([
            'vault'           => $live['vault'],
            'owner_total'     => $live['ownerDurable'] + $live['ownerAccrual'],
            'owner_durable'   => $live['ownerDurable'],
            'owner_accrual'   => $live['ownerAccrual'],
            'lastHour'        => $lastHour,
            'beginner'        => $bp,
            'integrity'       => $integrity,
            'history'         => $history,
        ]);
    }

    /**
     * RAW live reads of the unified vault + owner earnings. NEVER uses
     * getRedisBalance (its SETNX cold-seed is a write the monitor must not do);
     * falls back to the DB snapshot only when the live key is absent.
     *
     * @return array{vault:int, ownerDurable:int, ownerAccrual:int}
     */
    private function liveVaultState(): array
    {
        $vault = 0;
        $ownerAccrual = 0;
        try {
            $redis = FairLuckWallet::vaultRedis();
            $raw = $redis->get(PoolManager::KEY_VAULT);
            $vault = $raw !== null
                ? (int) $raw
                : (int) (FairLuckWallet::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)->value('balance') ?? 0);
            $ownerAccrual = (int) ($redis->get(PoolManager::KEY_OWNER_ACCRUAL) ?? 0);
        } catch (\Throwable $e) {
            $vault = (int) (FairLuckWallet::where('wallet_type', FairLuckWallet::TYPE_UNIFIED_VAULT)->value('balance') ?? 0);
        }

        $ownerDurable = (int) (CoreWallet::where('name', 'owner_wallet')->value('coins') ?? 0);

        return ['vault' => $vault, 'ownerDurable' => $ownerDurable, 'ownerAccrual' => $ownerAccrual];
    }

    /**
     * Beginner-protection daily-cap consumption (today's rolling key, §4.1).
     *
     * @param array<string,mixed> $settings
     * @return array{enabled:bool, capTotal:int, consumed:int, remaining:int}
     */
    private function beginnerCapState(array $settings): array
    {
        $enabled = filter_var($settings['beginner_protection_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $capTotal = (int) ($settings['beginner_global_daily_cap'] ?? 0);
        $consumed = 0;
        try {
            $consumed = (int) (FairLuckWallet::vaultRedis()
                ->get(PoolManager::beginnerDailyCapKey(gmdate('Ymd'))) ?? 0);
        } catch (\Throwable $e) {
            // leave 0
        }
        $remaining = $capTotal > 0 ? max(0, $capTotal - $consumed) : 0;

        return ['enabled' => $enabled, 'capTotal' => $capTotal, 'consumed' => $consumed, 'remaining' => $remaining];
    }

    /**
     * Money-integrity counters that MUST be 0 in a healthy system:
     *   fallbackLen      — LLEN of the Redis refund-failure fallback list.
     *   pendingRedis     — lucky_refund_failures rows still 'pending' (un-applied).
     *   stuckIntents     — lucky_batch_intents stuck in debited/settling (orphans).
     *
     * @return array{fallbackLen:int, pendingRedis:int, stuckIntents:int}
     */
    private function integrityCounters(): array
    {
        $fallbackLen = 0;
        try {
            $fallbackLen = (int) FairLuckWallet::vaultRedis()
                ->llen(\App\Services\Gifts\LuckyMoneyJournal::REDIS_FALLBACK_LIST);
        } catch (\Throwable $e) {
            $fallbackLen = -1; // unreadable — surfaced distinctly from a healthy 0
        }

        $pendingRedis = 0;
        $stuckIntents = 0;
        try {
            $pendingRedis = (int) DB::table('lucky_refund_failures')->where('state', 'pending')->count();
            $stuckIntents = (int) DB::table('lucky_batch_intents')
                ->whereIn('status', ['debited', 'settling'])
                ->where('created_at', '<', now()->subMinutes(2))
                ->count();
        } catch (\Throwable $e) {
            // leave 0
        }

        return ['fallbackLen' => $fallbackLen, 'pendingRedis' => $pendingRedis, 'stuckIntents' => $stuckIntents];
    }

    private function renderHtml(
        $vault, $dbVault, $ownerTotal, $ownerDurable, $ownerAccrual, $t, $all, $today, $hour,
        $topWinners, $bigWins, $multDist, $vaultHistory, $hourly, $userRtps,
        $appFee, $rcvFee, $targetRtp,
        $negativeLimit, $taperEnabled, $taperActive, $taperThreshold,
        $drainRisk, $largestMult, $bp, $integrity
    ): string {
        $zone = $vault <= $t['min'] ? 'CRITICAL' : ($vault <= $t['tight'] ? 'TIGHT' : ($vault <= $t['target'] ? 'NORMAL' : ($vault <= $t['high'] ? 'GENEROUS' : 'DRAIN')));
        $zc = ['CRITICAL'=>'#dc3545','TIGHT'=>'#e67e22','NORMAL'=>'#3498db','GENEROUS'=>'#28a745','DRAIN'=>'#8e44ad'][$zone];
        $allRtp = ($all->total_bet ?? 0) > 0 ? round(($all->total_won ?? 0) / $all->total_bet * 100, 2) : 0;
        $todayRtp = ($today->total_bet ?? 0) > 0 ? round(($today->total_won ?? 0) / $today->total_bet * 100, 2) : 0;
        $todayWinRate = ($today->total ?? 0) > 0 ? round(($today->wins ?? 0) / $today->total * 100, 1) : 0;
        $allWinRate = ($all->total ?? 0) > 0 ? round(($all->wins ?? 0) / $all->total * 100, 1) : 0;

        $vhJson = json_encode($vaultHistory);
        $zonesJson = json_encode($t);
        $hourlyJson = json_encode($hourly);

        // Top winners rows
        $winnersHtml = '';
        foreach ($topWinners as $w) {
            $rtp = $w->total_bet > 0 ? round($w->total_won / $w->total_bet * 100, 1) : 0;
            $rtpClass = $rtp > 100 ? 'text-success' : ($rtp > 80 ? 'text-warning' : 'text-danger');
            $winnersHtml .= "<tr><td>{$w->user_id}</td><td>{$w->spins}</td><td>{$w->wins}</td><td>" . number_format($w->total_bet) . "</td><td class='text-success'>" . number_format($w->total_won) . "</td><td>{$w->max_mult}x</td><td class='{$rtpClass}'>{$rtp}%</td></tr>";
        }

        // Big wins rows
        $bigWinsHtml = '';
        foreach ($bigWins as $b) {
            $bigWinsHtml .= "<tr><td>{$b->user_id}</td><td>" . number_format($b->bet_amount) . "</td><td><strong>{$b->multiplier}x</strong></td><td class='text-success'>" . number_format($b->profit_amount) . "</td><td>{$b->created_at}</td></tr>";
        }

        // Multiplier dist rows
        $multHtml = '';
        $totalSpinsToday = array_sum(array_column($multDist, 'cnt'));
        foreach ($multDist as $m) {
            $pct = $totalSpinsToday > 0 ? round($m->cnt / $totalSpinsToday * 100, 2) : 0;
            $label = $m->mult == 0 ? '0x (loss)' : $m->mult . 'x';
            $multHtml .= "<tr><td>{$label}</td><td>" . number_format($m->cnt) . "</td><td>{$pct}%</td><td>" . number_format($m->total_payout) . "</td></tr>";
        }

        // User RTP rows
        $userRtpHtml = '';
        foreach ($userRtps as $u) {
            $rtp = $u->total_bet > 0 ? round($u->total_won / $u->total_bet * 100, 1) : 0;
            $cls = $rtp > 100 ? 'text-success' : ($rtp > 80 ? '' : 'text-danger');
            $userRtpHtml .= "<tr><td>{$u->user_id}</td><td>{$u->spins}</td><td>" . number_format($u->total_bet) . "</td><td>" . number_format($u->total_won) . "</td><td class='{$cls}'><strong>{$rtp}%</strong></td></tr>";
        }

        // Hourly rows
        $hourlyHtml = '';
        foreach ($hourly as $h) {
            $wr = $h->spins > 0 ? round($h->wins / $h->spins * 100, 1) : 0;
            $hourlyHtml .= "<tr><td>" . str_pad($h->hr, 2, '0', STR_PAD_LEFT) . ":00</td><td>{$h->spins}</td><td>{$h->wins}</td><td>{$wr}%</td><td>" . number_format($h->bet) . "</td><td>" . number_format($h->profit) . "</td></tr>";
        }

        // ── Integrity indicator badges (must all be green/0) ──
        $fallback = (int) $integrity['fallbackLen'];
        $pending = (int) $integrity['pendingRedis'];
        $stuck = (int) $integrity['stuckIntents'];
        $okColor = '#3fb950'; $badColor = '#f85149'; $warnColor = '#d29922';
        $fallbackColor = $fallback === 0 ? $okColor : ($fallback < 0 ? $warnColor : $badColor);
        $fallbackTxt = $fallback < 0 ? 'unreadable' : (string) $fallback;
        $pendingColor = $pending === 0 ? $okColor : $badColor;
        $stuckColor = $stuck === 0 ? $okColor : $badColor;
        $taperColor = $taperActive ? $warnColor : ($taperEnabled ? '#58a6ff' : '#8b949e');
        $taperTxt = !$taperEnabled ? 'OFF' : ($taperActive ? 'ACTIVE' : 'armed');
        $drainColor = $drainRisk ? $badColor : $okColor;
        $drainTxt = $drainRisk ? 'YES (wins downgraded)' : 'no';

        $bpEnabled = $bp['enabled'];
        $bpConsumed = number_format($bp['consumed']);
        $bpCapTotal = $bp['capTotal'] > 0 ? number_format($bp['capTotal']) : '∞';
        $bpRemaining = $bp['capTotal'] > 0 ? number_format($bp['remaining']) : '—';
        $bpColor = !$bpEnabled ? '#8b949e' : ($bp['capTotal'] > 0 && $bp['remaining'] <= 0 ? $badColor : '#58a6ff');
        $bpTxt = $bpEnabled ? "{$bpConsumed} / {$bpCapTotal}" : 'OFF';

        $ownerTotalF = $this->fmt($ownerTotal);
        $ownerDurableF = $this->fmt($ownerDurable);
        $ownerAccrualF = $this->fmt($ownerAccrual);
        $negLimitF = $this->fmt($negativeLimit);
        $taperThF = $this->fmt($taperThreshold);

        $now = date('Y-m-d H:i:s');

        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>V7 Monitor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{background:#0d1117;color:#c9d1d9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:13px}
.card{background:#161b22;border:1px solid #30363d;border-radius:8px}
.card-header{border-bottom:1px solid #30363d;font-weight:600}
.table{color:#c9d1d9;font-size:12px}.table th{border-color:#30363d;color:#8b949e}.table td{border-color:#21262d}
.stat-box{text-align:center;padding:16px;border-radius:8px}
.stat-value{font-size:1.8rem;font-weight:700}.stat-label{font-size:.75rem;color:#8b949e;text-transform:uppercase}
.ind-box{text-align:center;padding:10px;border-radius:6px;border:1px solid #30363d}
.ind-value{font-size:1.2rem;font-weight:700}.ind-label{font-size:.7rem;color:#8b949e;text-transform:uppercase}
.text-success{color:#3fb950!important}.text-danger{color:#f85149!important}.text-warning{color:#d29922!important}
.pulse{animation:pulse 2s infinite}@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
#lastUpdate{color:#8b949e;font-size:11px}
</style></head><body>
<div class="container-fluid py-3">

<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0" style="color:#58a6ff">FairLuck V7 Live Monitor</h4>
<div><span class="pulse" style="color:#3fb950">●</span> <span id="lastUpdate">Updated: {$now}</span>
<button class="btn btn-sm btn-outline-secondary ms-2" onclick="location.reload()">Refresh</button></div>
</div>

<!-- Top Cards -->
<div class="row g-2 mb-3">
<div class="col-md-3"><div class="stat-box" style="background:{$zc}20;border:1px solid {$zc}">
<div class="stat-value" style="color:{$zc}">{$zone}</div>
<div class="stat-label">Vault Zone (unified)</div>
<div style="font-size:1.2rem;margin-top:4px">{$this->fmt($vault)} coins</div>
</div></div>
<div class="col-md-3"><div class="stat-box" style="background:#3fb95020;border:1px solid #3fb950">
<div class="stat-value" style="color:#3fb950">{$ownerTotalF}</div>
<div class="stat-label">Owner Wallet (total)</div>
<div style="font-size:.8rem;margin-top:4px;color:#8b949e">durable {$ownerDurableF} + accrual {$ownerAccrualF}</div>
</div></div>
<div class="col-md-2"><div class="stat-box" style="background:#58a6ff20;border:1px solid #58a6ff">
<div class="stat-value" style="color:#58a6ff">{$todayRtp}%</div>
<div class="stat-label">RTP Today</div>
</div></div>
<div class="col-md-2"><div class="stat-box" style="background:#3fb95020;border:1px solid #3fb950">
<div class="stat-value" style="color:#3fb950">{$todayWinRate}%</div>
<div class="stat-label">Win Rate Today</div>
</div></div>
<div class="col-md-2"><div class="stat-box" style="background:#bc8cff20;border:1px solid #bc8cff">
<div class="stat-value" style="color:#bc8cff">{$this->fmt($today->total ?? 0)}</div>
<div class="stat-label">Spins Today</div>
</div></div>
</div>

<!-- Integrity / solvency indicators (§6) -->
<div class="card mb-3"><div class="card-header">Integrity & Solvency — all should be GREEN / 0</div>
<div class="card-body"><div class="row g-2">
<div class="col-md-2"><div class="ind-box" style="border-color:{$fallbackColor}">
<div class="ind-value" style="color:{$fallbackColor}">{$fallbackTxt}</div>
<div class="ind-label">Fallback Queue (LLEN)</div></div></div>
<div class="col-md-2"><div class="ind-box" style="border-color:{$pendingColor}">
<div class="ind-value" style="color:{$pendingColor}">{$pending}</div>
<div class="ind-label">Audit pending_redis</div></div></div>
<div class="col-md-2"><div class="ind-box" style="border-color:{$stuckColor}">
<div class="ind-value" style="color:{$stuckColor}">{$stuck}</div>
<div class="ind-label">Stuck Intents (&gt;2m)</div></div></div>
<div class="col-md-2"><div class="ind-box" style="border-color:{$drainColor}">
<div class="ind-value" style="color:{$drainColor}">{$drainTxt}</div>
<div class="ind-label">win_downgraded_by_drain</div></div></div>
<div class="col-md-2"><div class="ind-box" style="border-color:{$taperColor}">
<div class="ind-value" style="color:{$taperColor}">{$taperTxt}</div>
<div class="ind-label">Solvency Taper</div></div></div>
<div class="col-md-2"><div class="ind-box" style="border-color:{$bpColor}">
<div class="ind-value" style="color:{$bpColor}">{$bpTxt}</div>
<div class="ind-label">Beginner Daily Cap</div></div></div>
</div></div></div>

<!-- Config + Last Hour + All Time -->
<div class="row g-2 mb-3">
<div class="col-md-4"><div class="card"><div class="card-header">Engine Config</div><div class="card-body p-2">
<table class="table table-sm mb-0">
<tr><td>App Profit</td><td><strong>{$this->pct($appFee)}</strong></td></tr>
<tr><td>Receiver Share</td><td><strong>{$this->pct($rcvFee)}</strong></td></tr>
<tr><td>Target RTP</td><td><strong>{$this->pct($targetRtp)}</strong></td></tr>
<tr><td>Negative Limit</td><td>{$negLimitF}</td></tr>
<tr><td>Taper Threshold</td><td>{$taperThF}</td></tr>
<tr><td>Vault (live Redis)</td><td>{$this->fmt($vault)}</td></tr>
<tr><td>Vault (DB snapshot)</td><td>{$this->fmt($dbVault)}</td></tr>
</table></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-header">Last Hour</div><div class="card-body p-2">
<table class="table table-sm mb-0">
<tr><td>Spins</td><td><strong>{$this->fmt($hour->total ?? 0)}</strong></td></tr>
<tr><td>Wins</td><td>{$this->fmt($hour->wins ?? 0)}</td></tr>
<tr><td>Unique Users</td><td>{$this->fmt($hour->unique_users ?? 0)}</td></tr>
<tr><td>Total Bet</td><td>{$this->fmt($hour->total_bet ?? 0)}</td></tr>
<tr><td>Net P&L</td><td class="text-danger">{$this->fmt($hour->net_profit ?? 0)}</td></tr>
</table></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-header">All Time</div><div class="card-body p-2">
<table class="table table-sm mb-0">
<tr><td>Total Spins</td><td><strong>{$this->fmt($all->total ?? 0)}</strong></td></tr>
<tr><td>Win Rate</td><td>{$allWinRate}%</td></tr>
<tr><td>RTP</td><td><strong>{$allRtp}%</strong></td></tr>
<tr><td>Total Bet</td><td>{$this->fmt($all->total_bet ?? 0)}</td></tr>
<tr><td>System Net</td><td>{$this->fmt($all->net_profit ?? 0)}</td></tr>
</table></div></div></div>
</div>

<div class="card mb-3"><div class="card-header">Vault Balance Timeline (unified)</div>
<div class="card-body"><canvas id="vaultChart" style="height:280px"></canvas></div></div>

<div class="card mb-3"><div class="card-header">Hourly Activity Today</div>
<div class="card-body"><canvas id="hourlyChart" style="height:200px"></canvas></div></div>

<div class="row g-2 mb-3">
<div class="col-md-6"><div class="card"><div class="card-header">Top Winners Today</div><div class="card-body p-0">
<table class="table table-sm mb-0"><thead><tr><th>User</th><th>Spins</th><th>Wins</th><th>Bet</th><th>Won</th><th>Max</th><th>RTP</th></tr></thead>
<tbody>{$winnersHtml}</tbody></table></div></div></div>
<div class="col-md-6"><div class="card"><div class="card-header">Biggest Wins Today</div><div class="card-body p-0">
<table class="table table-sm mb-0"><thead><tr><th>User</th><th>Bet</th><th>Mult</th><th>Profit</th><th>Time</th></tr></thead>
<tbody>{$bigWinsHtml}</tbody></table></div></div></div>
</div>

<div class="row g-2 mb-3">
<div class="col-md-4"><div class="card"><div class="card-header">Multiplier Distribution Today</div><div class="card-body p-0">
<table class="table table-sm mb-0"><thead><tr><th>Mult</th><th>Count</th><th>%</th><th>Payout</th></tr></thead>
<tbody>{$multHtml}</tbody></table></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-header">Hourly Breakdown</div><div class="card-body p-0">
<table class="table table-sm mb-0"><thead><tr><th>Hour</th><th>Spins</th><th>Wins</th><th>Win%</th><th>Bet</th><th>P&L</th></tr></thead>
<tbody>{$hourlyHtml}</tbody></table></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-header">Per-User RTP Today (10+ spins)</div><div class="card-body p-0">
<table class="table table-sm mb-0"><thead><tr><th>User</th><th>Spins</th><th>Bet</th><th>Won</th><th>RTP</th></tr></thead>
<tbody>{$userRtpHtml}</tbody></table></div></div></div>
</div>

</div>
<script>
var vhData={$vhJson}, zones={$zonesJson}, hourlyData={$hourlyJson};

new Chart(document.getElementById('vaultChart'),{type:'line',data:{
labels:vhData.map(d=>d.time),
datasets:[
{label:'Balance',data:vhData.map(d=>d.after),borderColor:'#58a6ff',backgroundColor:'#58a6ff20',fill:true,tension:.2,pointRadius:vhData.length>100?0:2,borderWidth:2},
{label:'CRITICAL',data:Array(vhData.length).fill(zones.min),borderColor:'#f8514940',borderDash:[4,4],borderWidth:1,pointRadius:0,fill:false},
{label:'TIGHT',data:Array(vhData.length).fill(zones.tight),borderColor:'#d2992240',borderDash:[4,4],borderWidth:1,pointRadius:0,fill:false},
{label:'TARGET',data:Array(vhData.length).fill(zones.target),borderColor:'#58a6ff40',borderDash:[6,3],borderWidth:1,pointRadius:0,fill:false},
{label:'GENEROUS',data:Array(vhData.length).fill(zones.high),borderColor:'#3fb95040',borderDash:[4,4],borderWidth:1,pointRadius:0,fill:false},
]},options:{maintainAspectRatio:false,plugins:{legend:{labels:{color:'#8b949e',font:{size:10}}}},
scales:{x:{ticks:{color:'#8b949e',maxTicksLimit:15,font:{size:10}}},y:{ticks:{color:'#8b949e',callback:v=>v>=1e3?(v/1e3).toFixed(0)+'k':v}}}}});

if(hourlyData.length){new Chart(document.getElementById('hourlyChart'),{type:'bar',data:{
labels:hourlyData.map(h=>String(h.hr).padStart(2,'0')+':00'),
datasets:[
{label:'Spins',data:hourlyData.map(h=>h.spins),backgroundColor:'#58a6ff60',borderRadius:4,yAxisID:'y'},
{label:'Wins',data:hourlyData.map(h=>h.wins),backgroundColor:'#3fb95060',borderRadius:4,yAxisID:'y'},
]},options:{maintainAspectRatio:false,plugins:{legend:{labels:{color:'#8b949e'}}},
scales:{x:{ticks:{color:'#8b949e'}},y:{ticks:{color:'#8b949e'},position:'left'}}}});}

setTimeout(()=>location.reload(),60000);
</script></body></html>
HTML;
    }

    private function fmt($n): string { return number_format((float)$n); }
    private function pct($v): string { return round((float)$v * 100, 2) . '%'; }
}
