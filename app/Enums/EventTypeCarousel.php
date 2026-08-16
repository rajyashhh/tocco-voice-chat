<?php

namespace App\Enums;

enum EventTypeCarousel: string
{
    case EVENT = 'event';
    case PK_EVENT = 'pk_event';
    case WEEKLY_STAR = 'weekly_star';
    case CHARGE_EVENT = 'charge_event';
    case EVENT_PERIOD = 'event_period';

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
