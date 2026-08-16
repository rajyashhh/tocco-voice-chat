<?php

namespace App\Enums;

enum IntervalLevel : string
{
    case WARE = 'ware';
    case VIP = 'vip';
    case COINS = 'coins';
    case ACHIEVEMENT = 'achievement';


    public static function getOptions(): array
    {
        return array_column(self::cases(), 'value');
    }

    // Method to get translated options
    public static function getTranslatedOptions(): array
    {
        return translateCategory(self::getOptions());
    }
}