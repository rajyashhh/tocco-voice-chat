<?php

namespace App\Tik\Repositories;

use App\Models\PaymentCoin;

class PaymentCoinRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new PaymentCoin());
    }

    public function index($type = 'user')
    {
        $paymentCoins = $this->model->with(['coinsV2' => function($query) {
            $query->withCount(['logs as usage_count' => function ($q) {
                $q->where('status', 1);
            }]);
        }])
        ->where('package_type', $type)
        ->where('status', true)
        ->get();

        $paymentCoins->each(function ($gateway) {
            $coins = $gateway->coinsV2;

            $mostUsedCoinId = $coins->sortByDesc('usage_count')->first()?->id;

            $coins->transform(function ($coin) use ($mostUsedCoinId) {
                $coin->most_used = $coin->id === $mostUsedCoinId;
                return $coin;
            });
        });

        return $paymentCoins;
    }


    public function findById($paymentCoinId)
    {
        return $this->model->with('coins')->find($paymentCoinId);
    }
}
