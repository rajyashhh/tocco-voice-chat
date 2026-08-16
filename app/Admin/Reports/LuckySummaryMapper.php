<?php

namespace App\Admin\Reports;

/**
 * Pure math mapper for the lucky-gifts period summary — no DB, unit-testable.
 *
 * fair_luck_transactions.profit_amount is the sender's NET per round
 * (win: payout - bet, loss: -bet — see FairLuckServiceV7 STEP 6), so the
 * gross payout to senders = Σ(profit_amount + bet_amount) over WINNING rounds
 * only, and the actual RTP = gross payouts / total bets.
 */
final class LuckySummaryMapper
{
    public static function map(?object $row): array
    {
        $rounds = (int) ($row->rounds ?? 0);
        $totalBets = (float) ($row->total_bets ?? 0);
        $sendersNet = (float) ($row->senders_net ?? 0);
        $totalPayouts = (float) ($row->total_payouts ?? 0);
        $receiversTotal = (float) ($row->receivers_total ?? 0);
        $appTotal = (float) ($row->app_total ?? 0);
        $players = (int) ($row->players ?? 0);
        $wins = (int) ($row->wins ?? 0);

        return [
            'rounds' => $rounds,
            'total_bets' => $totalBets,
            'senders_net' => $sendersNet,
            'total_payouts' => $totalPayouts,
            'receivers_total' => $receiversTotal,
            'app_total' => $appTotal,
            'players' => $players,
            'wins' => $wins,
            'win_rate' => $rounds > 0 ? round($wins * 100 / $rounds, 2) : 0.0,
            'rtp' => $totalBets > 0.0 ? round($totalPayouts * 100 / $totalBets, 2) : 0.0,
        ];
    }
}
