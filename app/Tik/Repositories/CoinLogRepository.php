<?php

namespace App\Tik\Repositories;


use App\Models\CoinLog;
use Illuminate\Support\Facades\Auth;

class CoinLogRepository extends AbstractRepository
{


    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new CoinLog());

    }

    public function getCoinsByUserId($userId, string $searchKey = null)
    {
        return $this->model::where('user_id', $userId)
            ->when($searchKey, fn($q) => $q->where('trx', 'like', $searchKey))
            ->orderByDesc('id')->get();
    }


    public function getCoinsById($id)
    {
        return $this->model::find($id);
            
    }

    public function getUserCoinLogs()
    {
        return CoinLog::query()
            ->where('user_type', 'user')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    public function getShippingAgencyCoinLogs($id)
    {
        return CoinLog::query()
            ->where('user_type', 'shipping_agency')
            ->where('user_id',$id ) 
            ->latest()
            ->get();
    }
}
