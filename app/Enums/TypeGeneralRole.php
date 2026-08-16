<?php

namespace App\Enums;

enum TypeGeneralRole : string
{
    case WEEKLY_STAR = 'weekly_star';
    case PK_EVENT = 'pk_event';
    case CHARGE_EVENT = 'charge_event';
    case EVENT_PERIOD = 'event_period';
    case WEEKLY_CP = 'weekly_cp';
    case HOST_LEVEL = 'host_level';


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