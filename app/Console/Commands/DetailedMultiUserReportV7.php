<?php

namespace App\Console\Commands;

use App\Models\CoreWallet;
use App\Models\FairLuckWallet;
use App\Models\User;
use App\Models\Gift;
use App\Services\FairLuck\V7\FairLuckServiceV7;
use App\Services\FairLuck\V7\MultiplierTable;
use App\Services\FairLuck\ProfileManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class DetailedMultiUserReportV7 extends Command
{
    protected $signature = 'fairluck:detailed-multi-v7
                            {gift_id=383 : Gift ID}
                            {bet_amount=100 : Bet amount}
                            {--users=3 : Number of users}
                            {--initial_balance=1000 : Initial balance}
                            {--max_rounds=3000 : Max rounds}
                            {--force_completion : Force completion}
                            {--unit_price=1 : Unit price}
                            {--vault=500000 : Initial vault balance}
                            {--output= : Output HTML file path (optional)}';

    protected $description = 'V7 Single-Step Engine — Multi-user simulation with detailed report';

    private array $users = [];
    private array $allRounds = [];
    private int $totalRounds = 0;
    private int $initialAppWalletBalance = 0;
    private int $initialVaultBalance = 0;
    private int $finalVaultBalance = 0;
    private int $finalAppWalletBalance = 0;

    public function handle()
    {
        $giftId = $this->argument('gift_id');
        $betAmount = (int) $this->argument('bet_amount');
        $userCount = (int) $this->option('users');
        $initialBalance = (int) $this->option('initial_balance');
        $unitPrice = (float) $this->option('unit_price');
        $maxRounds = (int) $this->option('max_rounds');
        $initialVault = (int) $this->option('vault');

        $this->info("=== V7 Single-Step Engine Simulation ===");
        $this->info("Users: {$userCount} | Bet: {$betAmount} | Balance: " . number_format($initialBalance) . " | Vault: " . number_format($initialVault) . " | Max rounds: {$maxRounds}");

        // Seed settings
        $this->seedV7Settings();

        $gift = Gift::find($giftId);
        if (!$gift) {
            $this->error("Gift {$giftId} not found");
            return;
        }

        $this->createTestUsers($userCount, $initialBalance);
        $this->runSimulation($gift, $betAmount, $unitPrice, $maxRounds, $initialVault);
        $this->generateHtmlReport($gift, $betAmount, $initialBalance);
        $this->displayResults();
    }

    private function seedV7Settings(): void
    {
        $settings = [
            // Fee rates
            'V7_app_fee_rate' => MultiplierTable::DEFAULT_APP_FEE_RATE,
            'fair_luck_app_fee_rate' => MultiplierTable::DEFAULT_APP_FEE_RATE,
            'fair_luck_owner_fee_rate' => 0.10,
            'fair_luck_receiver_fee_rate' => 0.10,

            // Target RTP
            'V7_target_rtp' => 0.99,

            // Wallet thresholds (coins)
            'V7_wallet_min' => 10_000,
            'V7_wallet_tight' => 50_000,
            'V7_wallet_target' => 200_000,
            'V7_wallet_high' => 500_000,
            'V7_wallet_drain' => 1_000_000,

            // Base weights (JSON) — admin can tune these
            'V7_base_weights' => json_encode([
                0    => 93_445,
                5    => 4_000,
                10   => 1_500,
                20   => 600,
                50   => 220,
                100  => 110,
                250  => 65,
                500  => 38,
                1000 => 22,
            ]),

            // Weight adjustment sensitivity — MORE AGGRESSIVE for vault sustainability
            // When vault is high (GENEROUS/DRAIN): boost wins strongly → drain to target
            // When vault is low (TIGHT/CRITICAL): suppress hard → recover to target
            'V7_nowin_sensitivity' => 0.15,         // 0x weight swings more (was 0.08)
            'V7_win_base_sensitivity' => 0.7,       // suppression base stronger (was 0.5)
            'V7_win_position_sensitivity' => 2.0,   // high tiers suppressed more (was 1.5)
            'V7_boost_base_sensitivity' => 0.5,     // boost base stronger (was 0.3)
            'V7_boost_position_sensitivity' => 1.8,  // high tiers boosted more when generous (was 1.2)
            'V7_nowin_floor' => 50_000,
            'V7_wallet_weight' => 0.70,             // wallet health matters more (was 0.60)
            'V7_rtp_weight' => 0.30,                // user RTP matters less (was 0.40)
            'V7_rtp_activation' => 500,

            // Negative vault threshold — vault can go this far negative before blocking
            'V7_negative_limit' => 30_000,

            // Loss streak protection — force a win after this many consecutive losses
            'V7_max_loss_streak' => 20,
            'V7_forced_win_mult' => 5,

            // Legacy
            'coin_to_usd_rate' => 0.01,
        ];

        foreach ($settings as $key => $value) {
            \App\Models\FairLuckSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'description' => 'V7 Single-Step']
            );
        }

        \Illuminate\Support\Facades\Cache::forget('fair_luck:settings');
        $this->info("Settings seeded (" . count($settings) . " keys)");
    }

    private function createTestUsers(int $count, int $balance): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $letter = chr(64 + $i);
            $user = User::create([
                'name' => "SimUser_{$letter}_" . time() . rand(100, 999),
                'email' => strtolower($letter) . '_sim_' . time() . rand(100, 999) . '@test.local',
                'password' => bcrypt('password'),
                'di' => $balance,
                'email_verified_at' => now(),
            ]);

            $this->users[] = [
                'user' => $user,
                'letter' => $letter,
                'initial_balance' => $balance,
                'attempts' => 0,
                'wins' => 0,
                'total_bet' => 0,
                'total_win' => 0,
                'total_sender_payout' => 0,
                'final_balance' => $balance,
                'finished_round' => 0,
                'max_multiplier' => 0,
                'high_multipliers' => 0,
                'multiplier_counts' => [0 => 0, 5 => 0, 10 => 0, 20 => 0, 50 => 0, 100 => 0, 250 => 0, 500 => 0, 1000 => 0],
                'current_loss_streak' => 0,
                'max_loss_streak' => 0,
                'current_win_streak' => 0,
                'max_win_streak' => 0,
            ];
        }
    }

    private function runSimulation(Gift $gift, int $betAmount, float $unitPrice, int $maxRounds, int $initialVault): void
    {
        // === PURE IN-MEMORY SIMULATION ===
        // No DB writes per spin. Only MultiplierTable + array state.
        // This allows running 100k+ spins without OOM.

        $this->initialVaultBalance = $initialVault;
        $this->initialAppWalletBalance = 0;

        $table = (new MultiplierTable())->preload();
        $appFeeRate = \App\Models\FairLuckSetting::getOwnerFeeRate();
        $negativeLimit = \App\Models\FairLuckSetting::getVaultNegativeLimit();

        // In-memory state
        $luckyWallet = $initialVault;
        $appWallet = 0;
        $minLuckyWallet = $initialVault;

        // Per-user RTP tracking (in-memory, no Redis needed)
        $userRtp = [];
        foreach ($this->users as $u) {
            $userRtp[$u['letter']] = ['total_bet' => 0, 'total_returned' => 0];
        }

        $this->info("Vault: " . number_format($initialVault) . " | Running in-memory simulation...");

        $round = 1;
        $cumulativeAppFee = 0;

        while ($round <= $maxRounds) {
            $anyActive = false;

            foreach ($this->users as &$userData) {
                $balance = $userData['final_balance'];
                if ($balance < $betAmount) continue;
                $anyActive = true;

                // Compute fees
                $fee = (int) round($betAmount * $appFeeRate);
                $netBet = $betAmount - $fee;

                // Credit vault + app
                $luckyWallet += $netBet;
                $appWallet += $fee;
                $cumulativeAppFee += $fee;

                $luckyBefore = $luckyWallet;

                // Select multiplier
                $rtp = &$userRtp[$userData['letter']];
                $selection = $table->select(
                    $luckyWallet,
                    $rtp['total_bet'],
                    $rtp['total_returned'],
                    $betAmount,
                    $userData['current_loss_streak']
                );

                $mult = $selection['multiplier'];
                $isWinner = ($mult > 0);

                // Compute payouts
                $totalPayout = 0;
                $senderPayout = 0;
                $receiverPayout = 0;

                if ($isWinner) {
                    $totalPayout = $netBet * $mult;
                    $receiverRate = (float) \App\Models\FairLuckSetting::getByKey('fair_luck_receiver_fee_rate', 0.10);
                    $receiverPayout = (int) round($totalPayout * $receiverRate);
                    $senderPayout = $totalPayout - $receiverPayout ;

                    // Allow vault to go negative up to the limit
                    if (($luckyWallet - $totalPayout) >= -$negativeLimit) {
                        $luckyWallet -= $totalPayout;
                    } else {
                        // Beyond negative limit — force no-win
                        $mult = 0;
                        $isWinner = false;
                        $totalPayout = $senderPayout = $receiverPayout  = 0;
                    }
                }

                $luckyAfter = $luckyWallet;
                $minLuckyWallet = min($minLuckyWallet, $luckyAfter);

                // Track RTP (in-memory)
                $rtp['total_bet'] += $betAmount;
                $rtp['total_returned'] += $senderPayout;

                // Update user balance
                $balanceBefore = $balance;
                $balanceAfter = $balanceBefore - $betAmount + $senderPayout;
                $userData['final_balance'] = $balanceAfter;

                // Update stats
                $userData['attempts']++;
                $userData['total_bet'] += $betAmount;

                if ($isWinner && $senderPayout > 0) {
                    $userData['wins']++;
                    $userData['total_win'] += $senderPayout;
                    $userData['max_multiplier'] = max($userData['max_multiplier'], $mult);
                    if ($mult >= 250) $userData['high_multipliers']++;
                    if (isset($userData['multiplier_counts'][$mult])) $userData['multiplier_counts'][$mult]++;
                    $userData['current_win_streak']++;
                    $userData['current_loss_streak'] = 0;
                    $userData['max_win_streak'] = max($userData['max_win_streak'], $userData['current_win_streak']);
                } else {
                    $userData['multiplier_counts'][0]++;
                    $userData['current_loss_streak']++;
                    $userData['current_win_streak'] = 0;
                    $userData['max_loss_streak'] = max($userData['max_loss_streak'], $userData['current_loss_streak']);
                }

                // Store round data
                $this->allRounds[] = [
                    'global_round' => $round,
                    'user_round' => $userData['attempts'],
                    'user_letter' => $userData['letter'],
                    'is_winner' => $isWinner,
                    'multiplier' => $mult,
                    'profit_amount' => $senderPayout,
                    'bet_amount' => $betAmount,
                    'user_balance_before' => $balanceBefore,
                    'user_balance_after' => $balanceAfter,
                    'user_balance_change' => $balanceAfter - $balanceBefore,
                    'lucky_wallet_before' => $luckyBefore,
                    'lucky_wallet_after' => $luckyAfter,
                    'app_fee' => $fee,
                    'cumulative_app_fee' => $cumulativeAppFee,
                    'app_wallet' => $appWallet,
                    'wallet_factor' => $selection['walletFactor'],
                    'rtp_factor' => $selection['rtpFactor'],
                    'wallet_zone' => $selection['walletZone'],
                    'jackpot_gate_fired' => $selection['jackpotGateFired'],
                    'receiver_payout' => $receiverPayout,
                    'net_bet' => $netBet,
                    'total_payout' => $totalPayout,
                ];

                if ($balanceAfter < $betAmount && $userData['finished_round'] == 0) {
                    $userData['finished_round'] = $round;
                    $this->line("User {$userData['letter']} ran out at round {$round}");
                }
            }
            unset($userData);

            if (!$anyActive) {
                $this->info("All users out of balance at round {$round}");
                break;
            }

            if ($round % 500 == 0) {
                $this->line("Round {$round}... (vault: " . number_format($luckyWallet) . ", mem: " . round(memory_get_usage(true) / 1024 / 1024) . "MB)");
            }

            $round++;
        }

        // Finalize
        foreach ($this->users as &$userData) {
            if ($userData['finished_round'] == 0) $userData['finished_round'] = $round - 1;
        }
        unset($userData);

        $this->totalRounds = $round - 1;

        // Store final state in instance variables for report generation (NO real DB/Redis writes)
        // This prevents corruption if run on production
        $this->finalVaultBalance = $luckyWallet;
        $this->finalAppWalletBalance = $appWallet;
        $this->initialAppWalletBalance = 0; // Track from 0 since sim is in-memory
    }

    private function generateHtmlReport(Gift $gift, int $betAmount, int $initialBalance): void
    {
        $timestamp = date('Y_m_d_H_i_s');
        $filename = "detailed_fairluck_report_v7_{$timestamp}.html";

        $totalAttempts = array_sum(array_column($this->users, 'attempts'));
        $totalWins = array_sum(array_column($this->users, 'wins'));
        $totalBets = array_sum(array_column($this->users, 'total_bet'));
        $totalWinnings = array_sum(array_column($this->users, 'total_win'));
        $overallRTP = $totalBets > 0 ? ($totalWinnings / $totalBets) * 100 : 0;

        $finalVault = $this->finalVaultBalance;
        $finalAppWallet = $this->finalAppWalletBalance;
        $vaultChange = $finalVault - $this->initialVaultBalance;
        $appChange = $finalAppWallet - $this->initialAppWalletBalance;

        // Compute verification assertions
        $assertions = $this->runAssertions($finalAppWallet, $finalVault);

        // Compute zone distribution
        $zoneDist = array_count_values(array_column($this->allRounds, 'wallet_zone'));

        // Compute multiplier distribution
        $multDist = [];
        foreach ([0, 5, 10, 20, 50, 100, 250, 500, 1000] as $m) {
            $count = count(array_filter($this->allRounds, fn($r) => $r['multiplier'] == $m || (!$r['is_winner'] && $m == 0)));
            $multDist[$m] = $count;
        }
        // Recount properly
        $multDist = [0 => 0, 5 => 0, 10 => 0, 20 => 0, 50 => 0, 100 => 0, 250 => 0, 500 => 0, 1000 => 0];
        foreach ($this->allRounds as $r) {
            $mult = $r['is_winner'] ? $r['multiplier'] : 0;
            if (isset($multDist[$mult])) $multDist[$mult]++;
        }

        $noWinRate = $totalAttempts > 0 ? ($multDist[0] / $totalAttempts) * 100 : 0;

        $html = $this->buildHtml(
            $gift, $betAmount, $initialBalance, $totalAttempts, $totalWins, $totalBets, $totalWinnings,
            $overallRTP, $finalVault, $finalAppWallet, $vaultChange, $appChange,
            $assertions, $zoneDist, $multDist, $noWinRate
        );

        $outputPath = $this->option('output');
        if ($outputPath) {
            $dir = dirname($outputPath);
            if (!file_exists($dir)) mkdir($dir, 0755, true);
            file_put_contents($outputPath, $html);
            $this->info("Report: {$outputPath}");
        } else {
            // Internal report — keep out of public/ (was served unauthenticated).
            $dir = storage_path('app/reports');
            if (!file_exists($dir)) mkdir($dir, 0755, true);
            $fullPath = "{$dir}/{$filename}";
            file_put_contents($fullPath, $html);
            $this->info("Report: {$fullPath}");
        }
    }

    private function runAssertions(int $finalAppWallet, int $finalVault): array
    {
        $results = [];

        // 1. App wallet only increased
        $appIncreased = $finalAppWallet >= $this->initialAppWalletBalance;
        $results['app_wallet_increased'] = [
            'pass' => $appIncreased,
            'detail' => "Before: " . number_format($this->initialAppWalletBalance) . " | After: " . number_format($finalAppWallet) . " | Delta: " . number_format($finalAppWallet - $this->initialAppWalletBalance),
        ];

        // 2. Lucky wallet never negative
        $minLucky = PHP_INT_MAX;
        foreach ($this->allRounds as $r) {
            $minLucky = min($minLucky, $r['lucky_wallet_after']);
        }
        $results['lucky_wallet_floor'] = [
            'pass' => $minLucky >= 0,
            'detail' => "Minimum recorded: " . number_format($minLucky),
        ];

        // 3. No-win rate >= 85%
        $noWins = count(array_filter($this->allRounds, fn($r) => !$r['is_winner']));
        $noWinRate = count($this->allRounds) > 0 ? ($noWins / count($this->allRounds)) * 100 : 0;
        $results['no_win_rate'] = [
            'pass' => $noWinRate >= 85,
            'detail' => "Actual: " . number_format($noWinRate, 2) . "%",
        ];

        // 4. Per-user RTP within ±15% of 99%
        $rtpOk = true;
        $rtpDetails = [];
        foreach ($this->users as $u) {
            $rtp = $u['total_bet'] > 0 ? ($u['total_win'] / $u['total_bet']) * 100 : 0;
            $rtpDetails[] = "User {$u['letter']}: " . number_format($rtp, 2) . "%";
            if ($u['total_bet'] > 0 && abs($rtp - 99) > 15) $rtpOk = false;
        }
        $results['user_rtp'] = [
            'pass' => $rtpOk,
            'detail' => implode(' | ', $rtpDetails),
        ];

        // 5. Payout split correct (sender + receiver + host = totalPayout)
        $splitErrors = 0;
        foreach ($this->allRounds as $r) {
            if ($r['is_winner']) {
                $sum = ($r['profit_amount'] ?? 0) + ($r['receiver_payout'] ?? 0);
                if ($sum != ($r['total_payout'] ?? 0)) $splitErrors++;
            }
        }
        $results['payout_split'] = [
            'pass' => $splitErrors === 0,
            'detail' => "Mismatches: {$splitErrors}",
        ];

        // 6. Fee split correct (appFee + netBet = betAmount)
        $feeErrors = 0;
        foreach ($this->allRounds as $r) {
            if (($r['app_fee'] + $r['net_bet']) != $r['bet_amount']) $feeErrors++;
        }
        $results['fee_split'] = [
            'pass' => $feeErrors === 0,
            'detail' => "Mismatches: {$feeErrors}",
        ];

        return $results;
    }

    private function buildHtml(
        Gift $gift, int $betAmount, int $initialBalance,
        int $totalAttempts, int $totalWins, int $totalBets, int $totalWinnings,
        float $overallRTP, int $finalVault, int $finalAppWallet,
        int $vaultChange, int $appChange,
        array $assertions, array $zoneDist, array $multDist, float $noWinRate
    ): string {
        $vc = $vaultChange >= 0 ? '#28a745' : '#dc3545';
        $vs = $vaultChange >= 0 ? '+' : '';
        $ac = $appChange >= 0 ? '#28a745' : '#dc3545';
        $as = $appChange >= 0 ? '+' : '';

        $allPass = !in_array(false, array_column($assertions, 'pass'));
        $assertColor = $allPass ? '#28a745' : '#dc3545';
        $assertLabel = $allPass ? 'ALL CHECKS PASSED' : 'SOME CHECKS FAILED';

        $html = "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>FairLuck V7 Report</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
<style>
body{background:#f8f9fb;font-family:'Segoe UI',sans-serif}
.winner-row{background:#d4edda!important}.loser-row{background:#f8d7da!important}
.stats-summary{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff}
.wallet-card{border-radius:12px;padding:20px;color:#fff}
.wallet-game{background:linear-gradient(135deg,#1a73e8,#0d47a1)}
.wallet-app{background:linear-gradient(135deg,#e67e22,#c0392b)}
.wallet-label{font-size:.85rem;opacity:.85}.wallet-value{font-size:1.5rem;font-weight:700}
.wallet-change{font-size:1.1rem;font-weight:600}
.positive{color:#28a745}.negative{color:#dc3545}
</style></head><body><div class='container py-5'>

<div class='stats-summary p-4 rounded mb-4'>
<h1 class='text-center mb-3'>FairLuck V7 — Single-Step Engine Report</h1>
<p class='text-center mb-1'>Gift: {$gift->name} (ID {$gift->id}) | Bet: " . number_format($betAmount) . " | Initial Balance: " . number_format($initialBalance) . " | Rounds: {$this->totalRounds}</p>
<p class='text-center mb-0'><small>Engine: Single-step weighted selection | APP_FEE: 1.5% | Sender: 80% | Receiver: 10% | Host: 10% | Target RTP: 99%</small></p>
</div>

<!-- Wallet Cards -->
<div class='row mb-4 g-3'>
<div class='col-md-6'><div class='wallet-card wallet-game'>
<div class='wallet-label'>Lucky Wallet (Global Vault)</div>
<div class='row mt-2'>
<div class='col-4 text-center'><div class='wallet-label'>Before</div><div class='wallet-value'>" . number_format($this->initialVaultBalance) . "</div></div>
<div class='col-4 text-center'><div class='wallet-label'>After</div><div class='wallet-value'>" . number_format($finalVault) . "</div></div>
<div class='col-4 text-center'><div class='wallet-label'>Change</div><div class='wallet-change' style='color:{$vc};background:#fff;border-radius:8px;padding:4px 8px;display:inline-block'>{$vs}" . number_format($vaultChange) . "</div></div>
</div></div></div>
<div class='col-md-6'><div class='wallet-card wallet-app'>
<div class='wallet-label'>App Wallet</div>
<div class='row mt-2'>
<div class='col-4 text-center'><div class='wallet-label'>Before</div><div class='wallet-value'>" . number_format($this->initialAppWalletBalance) . "</div></div>
<div class='col-4 text-center'><div class='wallet-label'>After</div><div class='wallet-value'>" . number_format($finalAppWallet) . "</div></div>
<div class='col-4 text-center'><div class='wallet-label'>Change</div><div class='wallet-change' style='color:{$ac};background:#fff;border-radius:8px;padding:4px 8px;display:inline-block'>{$as}" . number_format($appChange) . "</div></div>
</div></div></div>
</div>

<!-- User Cards -->
<div class='row mb-4'>";

        foreach ($this->users as $u) {
            $rtp = $u['total_bet'] > 0 ? ($u['total_win'] / $u['total_bet']) * 100 : 0;
            $winRate = $u['attempts'] > 0 ? ($u['wins'] / $u['attempts']) * 100 : 0;
            $html .= "<div class='col-md-4 mb-3'><div class='card shadow-sm h-100'><div class='card-body'>
<h5 class='card-title'>User {$u['letter']}</h5>
<div class='row text-center g-2'>
<div class='col-4'><small>Spins</small><p class='h5 mb-0'>{$u['attempts']}</p></div>
<div class='col-4'><small>Wins</small><p class='h5 mb-0'>{$u['wins']}</p></div>
<div class='col-4'><small>Win Rate</small><p class='h5 mb-0'>" . number_format($winRate, 1) . "%</p></div>
<div class='col-4'><small>Total Bet</small><p class='h6 mb-0'>" . number_format($u['total_bet']) . "</p></div>
<div class='col-4'><small>Total Won</small><p class='h6 text-success mb-0'>" . number_format($u['total_win']) . "</p></div>
<div class='col-4'><small>Final Bal</small><p class='h6 mb-0'>" . number_format($u['final_balance']) . "</p></div>
<div class='col-4'><small>RTP</small><p class='h5 mb-0'>" . number_format($rtp, 1) . "%</p></div>
<div class='col-4'><small>Max Mult</small><p class='h6 mb-0'>{$u['max_multiplier']}x</p></div>
<div class='col-4'><small>Big Wins</small><p class='h6 mb-0'>{$u['high_multipliers']}</p></div>
<div class='col-4'><small>Max Loss Streak</small><p class='h6 text-danger mb-0'>{$u['max_loss_streak']}</p></div>
<div class='col-4'><small>Max Win Streak</small><p class='h6 text-success mb-0'>{$u['max_win_streak']}</p></div>
</div></div></div></div>";
        }

        $html .= "</div>

<!-- Verification Assertions -->
<div class='card shadow-sm mb-4'><div class='card-header text-white' style='background:{$assertColor}'><h5 class='mb-0'>{$assertLabel}</h5></div><div class='card-body'><table class='table table-sm mb-0'><tbody>";

        foreach ($assertions as $name => $a) {
            $icon = $a['pass'] ? '<span class="text-success">PASS</span>' : '<span class="text-danger">FAIL</span>';
            $label = str_replace('_', ' ', ucfirst($name));
            $html .= "<tr><td>{$icon}</td><td><strong>{$label}</strong></td><td>{$a['detail']}</td></tr>";
        }

        $html .= "</tbody></table></div></div>

<!-- Wallet Zone Distribution -->
<div class='card shadow-sm mb-4'><div class='card-body'><h5>Wallet Zone Distribution</h5>
<table class='table table-sm table-bordered'><thead class='table-light'><tr><th>Zone</th><th>Spins</th><th>% of Total</th></tr></thead><tbody>";

        foreach (['CRITICAL', 'TIGHT', 'NORMAL', 'GENEROUS', 'DRAIN'] as $zone) {
            $cnt = $zoneDist[$zone] ?? 0;
            $pct = $totalAttempts > 0 ? ($cnt / $totalAttempts) * 100 : 0;
            $html .= "<tr><td>{$zone}</td><td>{$cnt}</td><td>" . number_format($pct, 1) . "%</td></tr>";
        }

        $html .= "</tbody></table></div></div>

<!-- Multiplier Distribution -->
<div class='card shadow-sm mb-4'><div class='card-body'><h5>Multiplier Distribution</h5>
<table class='table table-sm table-bordered'><thead class='table-light'><tr><th>Multiplier</th><th>Count</th><th>% of Spins</th><th>Total Payout</th></tr></thead><tbody>";

        foreach ($multDist as $mult => $cnt) {
            $pct = $totalAttempts > 0 ? ($cnt / $totalAttempts) * 100 : 0;
            $totalPay = 0;
            if ($mult > 0) {
                foreach ($this->allRounds as $r) {
                    if ($r['is_winner'] && $r['multiplier'] == $mult) {
                        $totalPay += $r['total_payout'];
                    }
                }
            }
            $label = $mult == 0 ? '0x (no win)' : "{$mult}x";
            $html .= "<tr><td>{$label}</td><td>{$cnt}</td><td>" . number_format($pct, 2) . "%</td><td>" . number_format($totalPay) . "</td></tr>";
        }

        $html .= "</tbody></table></div></div>

<!-- Spin Log -->
<div class='card shadow-sm'><div class='card-body'>
<div class='row g-3 align-items-end mb-3'>
<div class='col-8'><h5 class='mb-0'>Full Spin Log</h5></div>
<div class='col-4'><select id='pf' class='form-select form-select-sm'><option value='all'>All Users</option>";

        foreach ($this->users as $u) {
            $html .= "<option value='{$u['letter']}'>{$u['letter']}</option>";
        }

        $html .= "</select></div></div>
<div class='table-responsive'><table class='table table-sm align-middle small'><thead class='table-light'><tr>
<th>#</th><th>Spin</th><th>User</th><th>Result</th><th>Mult</th><th>Bet</th><th>User P&L</th><th>Balance</th>
<th>walletFactor</th><th>rtpFactor</th><th>Zone</th><th>Lucky Wallet</th>
<th>Receiver</th><th>Host</th><th>App Fee</th><th>Cum App Fee</th><th>App Wallet</th><th>Gate</th>
</tr></thead><tbody>";

        foreach ($this->allRounds as $i => $r) {
            $isWin = $r['is_winner'];
            $rc = $isWin ? 'winner-row' : 'loser-row';
            $badge = $isWin ? "<span class='badge bg-success'>WIN</span>" : "<span class='badge bg-danger'>LOSS</span>";
            $mult = $isWin ? $r['multiplier'] . 'x' : '-';
            $pnl = $r['user_balance_change'];
            $pnlClass = $pnl >= 0 ? 'positive' : 'negative';
            $pnlStr = ($pnl >= 0 ? '+' : '') . number_format($pnl);
            $gate = $r['jackpot_gate_fired'] ? '<span class="badge bg-warning">YES</span>' : '';

            $html .= "<tr class='{$rc}' data-p='{$r['user_letter']}'>
<td>" . ($i + 1) . "</td><td>{$r['user_round']}</td><td>{$r['user_letter']}</td><td>{$badge}</td><td>{$mult}</td>
<td>" . number_format($r['bet_amount']) . "</td><td class='{$pnlClass}'>{$pnlStr}</td>
<td>" . number_format($r['user_balance_after']) . "</td>
<td>" . number_format($r['wallet_factor'], 4) . "</td><td>" . number_format($r['rtp_factor'], 4) . "</td>
<td>{$r['wallet_zone']}</td><td>" . number_format($r['lucky_wallet_after']) . "</td>
<td>" . number_format($r['receiver_payout']) . "</td>
<td>" . number_format($r['app_fee']) . "</td><td>" . number_format($r['cumulative_app_fee']) . "</td>
<td>" . number_format($r['app_wallet']) . "</td><td>{$gate}</td>
</tr>";
        }

        $html .= "</tbody></table></div></div></div>
</div>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
<script>
document.getElementById('pf').addEventListener('change',function(){
var f=this.value;document.querySelectorAll('[data-p]').forEach(function(r){
r.style.display=(f==='all'||r.getAttribute('data-p')===f)?'':'none';});});
</script></body></html>";

        return $html;
    }

    private function displayResults(): void
    {
        $this->info("");
        $this->info("=== V7 SIMULATION RESULTS ===");
        $this->info("Total Rounds: {$this->totalRounds}");

        $summaryData = [];
        $totalBets = 0;
        $totalWinnings = 0;
        $totalAttempts = 0;
        $totalWins = 0;

        foreach ($this->users as $u) {
            $rtp = $u['total_bet'] > 0 ? ($u['total_win'] / $u['total_bet']) * 100 : 0;
            $summaryData[] = [
                'User' => $u['letter'],
                'Spins' => number_format($u['attempts']),
                'Wins' => number_format($u['wins']),
                'Win%' => $u['attempts'] > 0 ? number_format(($u['wins'] / $u['attempts']) * 100, 1) . '%' : '0%',
                'RTP' => number_format($rtp, 1) . '%',
                'Final' => number_format($u['final_balance']),
                'P&L' => number_format($u['final_balance'] - $u['initial_balance']),
                'MaxMult' => $u['max_multiplier'] . 'x',
                'MaxLoss' => $u['max_loss_streak'],
            ];
            $totalBets += $u['total_bet'];
            $totalWinnings += $u['total_win'];
            $totalAttempts += $u['attempts'];
            $totalWins += $u['wins'];
        }

        if (!empty($summaryData)) {
            $this->table(array_keys($summaryData[0]), array_map('array_values', $summaryData));
        }

        $overallRTP = $totalBets > 0 ? ($totalWinnings / $totalBets) * 100 : 0;
        $noWins = count(array_filter($this->allRounds, fn($r) => !$r['is_winner']));
        $noWinRate = count($this->allRounds) > 0 ? ($noWins / count($this->allRounds)) * 100 : 0;

        $this->info("");
        $this->info("Overall RTP: " . number_format($overallRTP, 2) . "% (target: 99%)");
        $this->info("No-win rate: " . number_format($noWinRate, 2) . "% (target: ~93.4%)");
        $this->info("Win rate: " . number_format(100 - $noWinRate, 2) . "%");
        $this->info("App fee collected: " . number_format($this->allRounds ? end($this->allRounds)['cumulative_app_fee'] : 0));

        $this->info("Lucky wallet: " . number_format($this->initialVaultBalance) . " -> " . number_format($this->finalVaultBalance) . " (delta: " . number_format($this->finalVaultBalance - $this->initialVaultBalance) . ")");
        $this->info("===========================");
    }
}
