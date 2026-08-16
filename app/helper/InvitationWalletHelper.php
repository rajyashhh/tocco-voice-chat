<?php

namespace App\helper;

use App\Models\InvitationWallet;
use App\Models\User;

class InvitationWalletHelper
{

    public static function updateInvitationWallet(float|int $diffCoins): void
    {
        $sql = '
            UPDATE core_wallets
            SET coins = coins + :coins
            WHERE name = "invitation_code_wallet"
        ';

        \DB::update($sql, [
            'coins' => $diffCoins,
        ]);
    }
}
