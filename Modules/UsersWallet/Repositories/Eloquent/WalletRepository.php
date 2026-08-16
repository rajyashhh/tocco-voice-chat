<?php

namespace Modules\UsersWallet\Repositories\Eloquent;


use Modules\UsersWallet\Entities\UserWallet;
use Modules\UsersWallet\Entities\WalletLog;
use Modules\UsersWallet\Repositories\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
{
    public function getWalletByUserId(int $userId)
    {
           $wallet = UserWallet::firstOrCreate(
                ['user_id' => $userId],
                [
                    'balance' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            return $wallet;
    }

    public function updateWallet(int $walletId, array $data)
    {
        return UserWallet::where('id', $walletId)->update($data);
    }

    public function createLog(array $data)
    {
        return WalletLog::create($data);
    }

    public function createWallet(array $data)
    {
        return UserWallet::create($data);
    }

    public function getProfitsByType($userId ,$type = 'user')
    {
       return   WalletLog::with('target')
            ->where('type', $type)
            ->where('operation', 'add')
            ->where('user_id', $userId)
            ->get();
    }

    public function getLatestTransactions($userId, $limit = 20)
    {
        return WalletLog::where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get();
    }

    public function getTransactions($userId, $type ,$perPage = 15 ,$page = 1)
    {
        return WalletLog::where('user_id', $userId)
                        ->orderBy('id', 'DESC')
                        ->where('operation', $type)
                          ->paginate($perPage, ['*'], 'page', $page);
    }
    
}
