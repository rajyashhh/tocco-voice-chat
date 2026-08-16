<?php

namespace Modules\UsersWallet\Services;

use App\Helpers\Common;
use App\Helpers\UserDiamondLogHelper;
use App\Models\Setting;
use App\Models\User;
use App\Enums\UserCoinLogType;
use App\Enums\UserDiamondLogType;
use App\Helpers\UserCoinLogHelper;
use Illuminate\Support\Facades\DB;
use Modules\UsersWallet\Repositories\Eloquent\ExchangeLogRepository;
use Modules\UsersWallet\Repositories\Eloquent\ExchangeRepository;



class ExchangeService
{
    public function __construct(
        private  readonly ExchangeRepository $exchangeRepository,
        private readonly ExchangeLogRepository $exchangeLogRepository
    ) {}


    public function index($type)
    {
        return $this->exchangeRepository->getByType($type);
    }

    public function exchangeSetting()
    {
        return   \Cache::rememberForever('exchange_coin_percentage', function () {
            $setting = Setting::where('key', 'exchange_coin_percentage')->first();
            return $setting?->value ?? 1;
        });
    }

    public function create($user, $exchangeId)
    {
        $ex = $this->exchangeRepository->findById($exchangeId);

        if (!$ex) throw new \Exception('not found');
        if ($user->type_user != 0) throw new \Exception('not allowed');

        return DB::transaction(function () use ($user, $ex) {
            $locked = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$locked) throw new \Exception('not found');

            if ($locked->exchange_diamonds < $ex->diamonds) throw new \Exception('balance low');

            $data = [
                'user_id' => $locked->id,
                'diamonds' => $ex->diamonds,
                'value' => $ex->value,
                'type' => $ex->type,
                'operation_no' => rand(11111111, 99999999),
            ];

            $this->exchangeLogRepository->create($data);
            UserDiamondLogHelper::logByType(
                $locked->id,
                -abs($ex->diamonds),
                $locked->exchange_diamonds,
                UserDiamondLogType::EXCHANGE,
                $locked->id,
                $ex->value
            );
            $locked->exchange_diamonds -= $ex->diamonds;

            if ($locked->exchange_diamonds <= 0) {
                $locked->exchange_diamonds = 0;
            }
            if ($ex->type == 0) {

                $amountBefore = $locked->di;
                UserCoinLogHelper::logByType(
                    $locked->id,
                    $ex->value,
                    $amountBefore,
                    UserCoinLogType::EXCHANGE,
                );

                $locked->di += $ex->value;
            } elseif ($ex->type == 1) {
                $locked->gold += $ex->value;
            }
            $locked->save();

            $user->exchange_diamonds = $locked->exchange_diamonds;
            $user->di = $locked->di;
            $user->gold = $locked->gold;

            return true;
        });
    }

    public function createExchange($user, $diamonds, $exValue)
    {
         if (!in_array($user->type_user, [0, 3])) throw new \Exception(__('you are host you can not exchange diamonds'));

        if (!is_numeric($diamonds) || (int) $diamonds != $diamonds || (int) $diamonds <= 0) throw new \Exception('invalid diamonds amount');
        $diamonds = (int) $diamonds;

        $setting = Common::getSettingValue('exchange_coin_percentage') ?? 1;
        $exchangeCoin = (($setting / 100) * $diamonds);
        if (floor($exchangeCoin) != $exchangeCoin) throw new \Exception(__('you should exchange number of diamond'));

        $exchangeCoin = (int) $exchangeCoin;
        if ((int) $exValue != $exchangeCoin) throw new \Exception(__('calculus not true'));

        return DB::transaction(function () use ($user, $diamonds, $exchangeCoin) {
            $locked = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$locked) throw new \Exception('not found');

            // Re-check balance under the lock: blocks concurrent double-spend of the same diamonds.
            if ($locked->exchange_diamonds < $diamonds) throw new \Exception('balance low');

            $data = [
                'user_id' => $locked->id,
                'diamonds' => $diamonds,
                'value' => $exchangeCoin,
                'type' => 0,
                'operation_no' => rand(11111111, 99999999),
            ];

            $this->exchangeLogRepository->create($data);
            UserDiamondLogHelper::logByType(
                $locked->id,
                -abs($diamonds),
                $locked->exchange_diamonds,
                UserDiamondLogType::EXCHANGE,
                $locked->id,
                $exchangeCoin
            );

            $locked->exchange_diamonds -= $diamonds;

            if ($locked->exchange_diamonds <= 0) {
                $locked->exchange_diamonds = 0;
            }

            $amountBefore = $locked->di;
            UserCoinLogHelper::logByType(
                $locked->id,
                $exchangeCoin,
                $amountBefore,
                UserCoinLogType::EXCHANGE,
                 -abs($diamonds),
            );

            $locked->di += $exchangeCoin;

            $locked->save();

            $user->exchange_diamonds = $locked->exchange_diamonds;
            $user->di = $locked->di;

            return true;
        });
    }

    public function getExchangeLog($userId, $type)
    {
        return $this->exchangeLogRepository->getExchanges($userId, $type);
    }

    public function all($id, $perPage, $page)
    {
        return $this->exchangeRepository->all($id, $perPage, $page);
    }

    public function createDashboard($request)
    {

        $this->exchangeRepository->create($request->all());
        return true;
    }

    public function update($id, $request)
    {

        $this->exchangeRepository->update($request->all(), $id);
        return true;
    }

    public function delete($id)
    {

        $data = $this->exchangeRepository->findOrFail($id);
        $data->delete();
        return true;
    }

    public function show($id)
    {
        return $this->exchangeRepository->findOrFail($id);
    }
}
