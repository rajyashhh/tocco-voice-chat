<?php

namespace Modules\UsersWallet\Enum;

enum WalletEnum: string
{
    case USER = 'user';
    case AGENCY = 'agency';

    public static function values(): array
    {
        return [
            self::USER,
            self::AGENCY,
        ];
    }
}
