<?php

namespace App\Tik\Repositories;

use App\Models\Coin;

class CoinRepository extends AbstractRepository
{
    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new Coin());
    }

    public function allCoins()
    {
        return   $this->model->query()->select('id', 'usd', 'coin')->get();
    }
    public function allCoinsByPaymentId($payment_id)
    {
        return   $this->model->query()->select('id', 'usd', 'coin')->where('payment_gateway_id',$payment_id)->get();
    }

    public function findById($coinId)
    {
        return $this->model->query()->find($coinId);
    }
}
