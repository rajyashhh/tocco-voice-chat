<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Tik\Services\AdminUsersService;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\AdminUsersResource;
use App\Http\Resources\Api\V1\GamePlayerReportResource;
use App\Http\Resources\Api\V1\GameReportResource;
use App\Models\AllGame;
use App\Models\CoinGameUser;
use App\Models\User;
use Carbon\Carbon;

class GameReportController extends Controller
{
    public function allPlayers()
    {
        // $startDate = request('start_date') ?? date("Y-m-d"); 
        // $endDate = request('end_date') ?? date("Y-m-d"); 

        $players = CoinGameUser::with(["user" => function ($q) {
            $q->with("profile:id,user_id,avatar")->select("id", "name");
        }])->selectRaw('
                MAX(coin_game_users.created_at) as earliest_created_at, 
                coin_game_users.user_id, 
                MAX(users.name) as user_name, 
                SUM(CASE WHEN coin_game_users.type = 1 THEN coin_game_users.coins ELSE 0 END) as total_coins_win, 
                SUM(CASE WHEN coin_game_users.type = 0 THEN coin_game_users.coins ELSE 0 END) as total_coins_lose
            ')
            ->leftJoin('users', 'coin_game_users.user_id', '=', 'users.id')
            // ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            //     $query->whereBetween('coin_game_users.created_at', [$startDate, $endDate]);
            // })
            ->groupBy('coin_game_users.user_id')
            ->orderByDesc('total_coins_win')
            ->get()
            ->map(function ($player) {
                $player->total_app_gain = $player->total_coins_lose - $player->total_coins_win;
                return $player;
            });

        return Common::apiResponse(1, '', $players);
    }

    public function playerDetails($id)
    {
        $userId = $id;
        $gameId = request("game_id");
        $data = AllGame::with(["coinGameUser" => function ($query) use ($userId, $gameId) {
            $query->selectRaw('
                coin_game_users.game_id,
                SUM(CASE WHEN coin_game_users.type = 1 THEN coin_game_users.coins ELSE 0 END) as total_coins_win, 
                SUM(CASE WHEN coin_game_users.type = 0 THEN coin_game_users.coins ELSE 0 END) as total_coins_lose
            ')
                ->leftJoin('users', 'coin_game_users.user_id', '=', 'users.id')
                ->where('users.id', $userId)
                ->when($gameId, function ($q) use ($gameId) {
                    $q->where('coin_game_users.game_id', $gameId);
                })
                ->groupBy('coin_game_users.game_id');
        }])
            ->select('id', 'name')
            ->get();
        $sortedData = $data->sortByDesc(function ($game) {
            return $game->coinGameUser[0]?->total_coins_win ?? 0;
        });

        $sortedResource = GamePlayerReportResource::collection($sortedData);
        return Common::apiResponse(1, '', $sortedResource);
    }

    public function gameRanking($id, Request $request)
    {
        $gameId = $id;
        $type = $request->input('type');
        $filterType = $request->input('filter');
        $data = CoinGameUser::with(['game:id,name', 'user:id,name','user.profile'])
            ->selectRaw('
                coin_game_users.user_id,
                SUM(CASE WHEN coin_game_users.type = 1 THEN coin_game_users.coins ELSE 0 END) as total_coins_win, 
                SUM(CASE WHEN coin_game_users.type = 0 THEN coin_game_users.coins ELSE 0 END) as total_coins_lose
            ')
            ->leftJoin('users', 'coin_game_users.user_id', '=', 'users.id')
            ->when($gameId, function ($q) use ($gameId) {
                $q->where('coin_game_users.game_id', $gameId);
            })
            ->when($filterType, function ($q) use ($filterType) {
                switch ($filterType) {
                    case 'hour':
                        $q->where('coin_game_users.created_at', '>=', Carbon::now()->subHour());
                        break;
                    case 'day':
                        $q->where('coin_game_users.created_at', '>=', Carbon::now()->subDay());
                        break;
                    case 'week':
                        $q->where('coin_game_users.created_at', '>=', Carbon::now()->subWeek());
                        break;
                    case 'month':
                        $q->where('coin_game_users.created_at', '>=', Carbon::now()->subMonth());
                        break;
                }
            })
            ->groupBy('coin_game_users.user_id')
            ->orderByRaw($type == 1 ? 'total_coins_win DESC' : 'total_coins_lose DESC')
            ->limit(10)
            ->get();


        return Common::apiResponse(1, '', $data);
    }

    public function gameInfo($id)
    {
        $data = CoinGameUser::with("game")
            ->selectRaw('
            CAST(coin_game_users.game_id AS UNSIGNED) as game_id,
        CAST(SUM(CASE WHEN coin_game_users.type = 1 THEN coin_game_users.coins ELSE 0 END) AS SIGNED) as total_coins_win, 
        CAST(SUM(CASE WHEN coin_game_users.type = 0 THEN coin_game_users.coins ELSE 0 END) AS SIGNED) as total_coins_lose,
        CAST(
            SUM(CASE WHEN coin_game_users.type = 0 THEN coin_game_users.coins ELSE 0 END) - 
            SUM(CASE WHEN coin_game_users.type = 1 THEN coin_game_users.coins ELSE 0 END)
            AS SIGNED
        ) as total_app_gain
        ')
            ->leftJoin('users', 'coin_game_users.user_id', '=', 'users.id')
            ->groupBy('coin_game_users.game_id')
            ->where('coin_game_users.game_id', $id)->first();

        return Common::apiResponse(1, '', $data);
    }

    public function gamePlay($id, Request $request)
    {
        try {
            $data = User::where(['game_id' => $id, 'online' => 1])->with('profile')->paginate($request->perPage, ['*'], 'page', $request->page);
            return Common::apiResponse(1, '', $data);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}
