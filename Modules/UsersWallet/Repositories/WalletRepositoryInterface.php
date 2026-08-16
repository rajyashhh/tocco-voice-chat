<?php

namespace Modules\UsersWallet\Repositories;

interface WalletRepositoryInterface
{
    public function getWalletByUserId(int $userId);
    public function updateWallet(int $walletId, array $data);
    public function createLog(array $data);
    public function createWallet(array $data);
    public function getProfitsByType(int $userId,  $type);
    public function getLatestTransactions($userId, $limit = 20);
    public function getTransactions($userId, $type , $Perpage, $page);

}