<?php

namespace App\Enums;

enum GiftSourceType: string
{
    case GIFT = 'gift';
    case COINS = 'coins';

    public static function fromType( $type): self
    {
        return $type == 'bag' ? self::GIFT : self::COINS;
    }
}
