<?php

namespace App\Services;

use App\Helpers\Common;
use App\Models\GameWallet;
use App\Models\GameChargeHistory;
use App\Http\Resources\AllGameResource;
use App\Repositories\AllGameRepository;
use App\Repositories\User\UserRepository;
use App\Http\Resources\AllGameInRoomResource;

class AllGameService
{
    protected $allGameRepository;
    protected $userRepository;

    public function __construct(AllGameRepository $allGameRepository, UserRepository $userRepository)
    {
        $this->allGameRepository = $allGameRepository;
        $this->userRepository = $userRepository;
    }

    public function getAllGamesData()
    {
        // Fetch full games
        $allGames = $this->allGameRepository->getAllEnabledGames();
        \request()->type = 0;
        $fullGames = AllGameResource::collection($allGames)->toArray(request());

        // Fetch mini games
        $miniGames = $this->allGameRepository->getAllEnabledMiniGames();
        \request()->type = 1;
        $miniGames = AllGameResource::collection($miniGames)->toArray(request());

        return [
            'full' => $fullGames,
            'mini' => $miniGames,
            'credentials'  => $this->gameCredentials(),
        ];
    }

    public function getOutRoom()
    {
        // Fetch full games
        $allGames = $this->allGameRepository->getAllEnabledGames();
        \request()->type = 0;
        $fullGames = AllGameResource::collection($allGames)->toArray(request());

        return [
            'full' => $fullGames,

            'credentials'  => $this->gameCredentials(),
        ];
    }

    public function getInRoom()
    {
        // Fetch mini games
        $miniGames = $this->allGameRepository->getAllEnabledMiniGames();
        \request()->type = 1;
        $miniGames = AllGameInRoomResource::collection($miniGames)->toArray(request());

        return [
            'mini' => $miniGames,
            'credentials'  => $this->gameCredentials(),
        ];
    }

    /**
     * Credentials merged into the getConfig bridge from every registered game
     * provider (plan §5 "slot"). Each PAID provider lives in its own module and
     * tags a GameCredentialsContributor under 'game.credentials'; this core host
     * never imports them. No tagged contributors (e.g. the BaishunGame module
     * was subtracted from this build) => empty credentials => the app hides that
     * provider's launch. Replaces the old hard-coded baishunCredentials().
     */
    private function gameCredentials(): array
    {
        $credentials = [];

        foreach (app()->tagged('game.credentials') as $contributor) {
            $credentials = array_merge($credentials, $contributor->credentials());
        }

        return $credentials;
    }

    public function updateGame($gameId, $user)
    {
        if (!$gameId) {
            return ['status' => 0, 'message' => 'please inter game_id', 'code' => 404];
        }

        $allGame = $this->allGameRepository->findGameById($gameId);

        if (!$allGame) {
            return ['status' => 0, 'message' => 'game not found', 'code' => 404];
        }

        $this->userRepository->updateUserGame($user, $gameId);

        return ['status' => 1, 'message' => 'updated', 'code' => 200];
    }

    public function utdIndex()
    {
        return $this->allGameRepository->gameByTotalGain();
    }

    public function createUtd($request)
    {
        $image = null;
        if ($request->hasFile('image')) {
            $image = Common::upload('images', $request->file('image'));
        }
        $data = [
            'name' => $request->name,
            'name_en' => $request->name_en,
            'url' => $request->url,
            'image' => $image ?? '',
            'mini_url' => $request->mini_url,
            'type' => $request->type,
            'is_enable' => $request->is_enable,
            'custom_id' => $request->custom_id,
            'height_image' => $request->hight_image,
            'in_room'    => $request->in_room,
            'height' => $request->hight,

        ];
        return $this->allGameRepository->create($data);
    }

    public function updateUtd($request)
    {
        $data = [
            'name' => $request->name,
            'name_en' => $request->name_en,
            'url' => $request->url,
            'mini_url' => $request->mini_url,
            'type' => $request->type,
            'is_enable' => $request->is_enable,
            'custom_id' => $request->custom_id,
            'height_image' => $request->hight_image,
            'in_room'    => $request->in_room,
            'height' => $request->hight,
        ];
        if ($request->hasFile('image')) {
            $data['image'] = Common::upload('images', $request->file('image'));
        }

        return $this->allGameRepository->update($request->game_id, $data);
    }

    public function updateSwitch($request)
    {
        $data = [
            'is_enable' => $request->is_enable,
        ];
        return $this->allGameRepository->updateSwitch($request->game_id, $data);
    }

    public function show($game_id)
    {
        return $this->allGameRepository->findById($game_id);
    }

    public function gameChargeDetails($date)
    {
        $balance = GameWallet::query();
        $balanceDollar = GameChargeHistory::query();
        if ($date != null) {
            $year = substr($date, 0, 4);
            $month = substr($date, 5, 2);
            $balance = $balance->whereMonth("created_at", $month)->whereYear("created_at", $year);
            $balanceDollar = $balanceDollar->whereMonth("created_at", $month)->whereYear("created_at", $year);
        } else {
            $balance = $balance->whereMonth("created_at", date("m"))->whereYear("created_at", date("Y"));
            $balanceDollar = $balanceDollar->whereMonth("created_at", date("m"))->whereYear("created_at", date("Y"));
        }
        $balance = $balance->first();
        $balanceDollar = $balanceDollar->sum("value");
        $allBalance = $balance->balance ?? 0;
        $availableBalance = $balance ? $balance->balance - $balance->used : 0;
        $dollarValue =  config("app.one_coins") * 4;
        $usedDollar = (@$balance->used ?? 0) / $dollarValue;
        $availableBalanceDollar = (@$availableBalance ?? 0) / $dollarValue;
        return $data = [
            "balance" => @$allBalance ?? 0,
            'balance_dollar' => @$balanceDollar ?? 0,
            'available_balance' => @$availableBalance ?? 0,
            'available_balance_dollar' => $availableBalanceDollar ?? 0,
            'used_dollar' => $usedDollar ?? 0,
            'used' => @$balance->used ?? 0,
        ];
    }
}
