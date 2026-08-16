<?php

namespace App\Enums;

enum TypeCarousel: string
{
    case ROOM = 'room';
    case NORMAL = 'normal';
    case LINK = 'link';
    case EVENT = 'event';

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