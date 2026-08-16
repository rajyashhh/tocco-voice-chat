<?php

namespace App\Tik\Repositories;


use App\Helpers\Common;
use App\Models\Charge;
use Illuminate\Support\Facades\Auth;



class ChargeRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new Charge());
    }

    // public function create($data)
    // {
    //     return   $this->model->query()->create([
    //         'charger_id' => $data['userId'],
    //         'charger_type' => $data['chargeType'],
    //         'user_id' => $data['receiverId'],
    //         'user_type' => $data['type'],
    //         'amount' => $data['amount'],
    //         'amount_type' => $data['amountType'],
    //         'is_used_transferred' => $data['isTransferred'] ?? false,
    //         'usd' => $data['amount'],
    //         'balance_before' => $data['balance_before'],
    //     ]);
    // }


    public function getChargeHistory($type)
    {
        return $this->model->query()->with([
            'sender'      => function ($query) {
                $query->withoutAppends();
            }, 'receiver' => function ($query) {
                $query->withoutAppends();
            }
        ])->where('charger_type', $type)
        
        ->with(Common::chargerRelationsQuery());
    }


    public function getChargeToUserHistory()
    {
        $perPage = request('per_page', 10);
        $page = request('page', 1);
        
        return $this->model
            ->where('charger_id', Auth::user()->id)
            ->where('charger_type', 'user')
            ->where('user_type', 'user')
            ->whereNotNull('user_id')
            // ->with([
            //     'receiver:id,uuid',
            //     'receiver.profile:id,user_id,avatar'
            // ])
            ->with(Common::chargerRelationsQuery())
            ->select('id', 'user_id', 'amount', 'usd', 'created_at','charger_type','user_type','charger_id')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
    
    public function getChargeToAgencyHistory()
    {
        $perPage = request('per_page', 10);
        $page = request('page', 1);
    
        return $this->model
            ->where('charger_id', Auth::user()->id)
            ->where('charger_type', 'user')
            ->where('user_type', 'agency')
            ->whereNotNull('user_id')
            // ->with('receiverUser','receiverUser.profile','receiveragency')
            ->with(Common::chargerRelationsQuery())
            ->select('id', 'agency_id', 'amount', 'usd', 'created_at','charger_type','user_type','user_id','charger_id')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
    

}

