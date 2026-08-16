<?php

namespace App\Repositories;

use App\Models\AllGame;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class AllGameRepository
{
    public function getAllEnabledGames()
    {
        return AllGame::query()
            ->where(fn($q) => $q->where('url', '!=', null)->where('url', '!=', ''))
            ->where('is_enable', true)
            ->get();
    }

    public function getAllEnabledMiniGames()
    {
        return AllGame::query()
            ->where(fn($q) => $q->where('mini_url', '!=', null)->where('mini_url', '!=', ''))
            ->where('is_enable', true)
            ->get();
    }

    public function findGameById($gameId)
    {
        return AllGame::find($gameId);
    }

    public function all()
    {
        return AllGame::orderBy('id')->get();
    }

    public function gameByTotalGain()
    {
        // Order by the per-game monthly gain in SQL instead of loading every game
        // and sorting in PHP. A correlated subquery over coin_game_users gives the
        // same total_gain (current month, type=1 coins) used by the old PHP sort;
        // games with no plays gain NULL -> COALESCE 0 -> sort last, matching the
        // previous "?? 0" behavior. The coinGameUser relation is still eager
        // loaded so the serialized payload is byte-for-byte identical.
        $gainSubquery = DB::table('coin_game_users')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = 1 THEN coins ELSE 0 END), 0)')
            ->whereColumn('coin_game_users.game_id', 'all_games.id')
            ->whereMonth('coin_game_users.created_at', date('m'));

        $games = AllGame::with(['coinGameUser' => function ($query) {
            $query->whereMonth('created_at', date('m'))->select('game_id', DB::raw("
                SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) AS total_gain
            "))->groupBy('game_id');
        }])
            ->orderByDesc($gainSubquery)
            ->get();

        return array_values($games->toArray());
    }

    public function create($data)
    {
        AllGame::create($data);
        return true;
    }

    public function update($id, $data)
    {
        AllGame::find($id)->update($data);
        return true;
    }

    public function updateSwitch($id, $data)
    {
        AllGame::find($id)->update($data);
        return true;
    }

    public function findById($game_id)
    {
        return AllGame::find($game_id);
    }
}
