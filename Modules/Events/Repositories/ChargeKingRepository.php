<?php

namespace Modules\Events\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChargeKingRepository
{
    /**
     * Eligible users with their charge totals for the window.
     * Excludes staff accounts (type_user == 3) and users under an active
     * login ban (same active-window predicate as BanGuard).
     */
    public function eligibleQuery(string $fromDate, string $tillDate)
    {
        return User::query()
            ->leftJoinSub($this->chargesSub($fromDate, $tillDate), 'charges', 'users.id', 'charges.user_id')
            ->leftJoinSub($this->coinLogsSub($fromDate, $tillDate), 'coin_logs', 'users.id', 'coin_logs.user_id')
            ->where('users.type_user', '!=', 3)
            ->whereDoesntHave('bans', function ($q) {
                $q->where('bans.type', '!=', 'action')
                    ->whereRaw('DATE_ADD(bans.created_at, INTERVAL bans.duration HOUR) > ?', [now()]);
            })
            ->select(['users.*', DB::raw('IFNULL(charges_sum_amount, 0) + IFNULL(coin_logs_sum_obtained_coins, 0) as total_sum')])
            ->orderByDesc('total_sum')
            ->orderBy('users.id');
    }

    public function top(string $fromDate, string $tillDate, int $limit = 10)
    {
        return $this->eligibleQuery($fromDate, $tillDate)
            ->with('profile')
            ->limit($limit)
            ->get()
            ->filter(fn ($user) => (int) $user->total_sum > 0)
            ->values();
    }

    public function totalFor(int $userId, string $fromDate, string $tillDate): int
    {
        $charges = (int) DB::table('charges')
            ->where('user_id', $userId)
            ->where('user_type', 'user')
            ->whereBetween('created_at', [$fromDate . ' 00:00:00', $tillDate . ' 23:59:59'])
            ->sum('amount');

        $coinLogs = (int) DB::table('coin_logs')
            ->where('user_id', $userId)
            ->where('user_type', User::class)
            ->where('status', 1)
            ->whereBetween('created_at', [$fromDate . ' 00:00:00', $tillDate . ' 23:59:59'])
            ->sum('obtained_coins');

        return $charges + $coinLogs;
    }

    public function rankOf(int $total, string $fromDate, string $tillDate): ?int
    {
        if ($total <= 0) {
            return null;
        }

        $higher = DB::query()
            ->fromSub($this->totalsSub($fromDate, $tillDate), 'ranked')
            ->where('total_sum', '>', $total)
            ->count();

        return $higher + 1;
    }

    /**
     * Smallest eligible total strictly above the given one (the score of the
     * user holding the rank directly ahead), or null when leading.
     */
    public function nextHigherTotal(int $total, string $fromDate, string $tillDate): ?int
    {
        $value = DB::query()
            ->fromSub($this->totalsSub($fromDate, $tillDate), 'ranked')
            ->where('total_sum', '>', $total)
            ->min('total_sum');

        return $value === null ? null : (int) $value;
    }

    private function totalsSub(string $fromDate, string $tillDate)
    {
        return $this->eligibleQuery($fromDate, $tillDate)
            ->reorder()
            ->select(['users.id', DB::raw('IFNULL(charges_sum_amount, 0) + IFNULL(coin_logs_sum_obtained_coins, 0) as total_sum')]);
    }

    private function chargesSub(string $fromDate, string $tillDate)
    {
        return DB::table('charges')
            ->select('charges.user_id', DB::raw('SUM(charges.amount) as charges_sum_amount'))
            ->where('charges.user_type', 'user')
            ->whereBetween('charges.created_at', [$fromDate . ' 00:00:00', $tillDate . ' 23:59:59'])
            ->groupBy('charges.user_id');
    }

    private function coinLogsSub(string $fromDate, string $tillDate)
    {
        return DB::table('coin_logs')
            ->select('user_id', DB::raw('SUM(obtained_coins) as coin_logs_sum_obtained_coins'))
            ->where('coin_logs.user_type', User::class)
            ->where('coin_logs.status', 1)
            ->whereBetween('coin_logs.created_at', [$fromDate . ' 00:00:00', $tillDate . ' 23:59:59'])
            ->groupBy('user_id');
    }
}