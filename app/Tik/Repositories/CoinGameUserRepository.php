<?php

namespace App\Tik\Repositories;

use App\Models\CoinGameUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CoinGameUserRepository extends AbstractRepository
{


    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new CoinGameUser());
    }


    public function topThree()
    {
        // whereBetween([startOfDay, endOfDay]) is sargable and uses the created_at index,
        // unlike whereDate('created_at', ...) which wraps the column in DATE() and disables it.
        return $this->model->select(
            'user_id',
            DB::raw(" SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) AS exp")
        )->groupBy('user_id')->with('user')->orderByRaw("exp desc")
            ->whereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])
            ->limit(3)->get();
    }
}
