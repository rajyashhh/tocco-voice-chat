<?php

namespace Modules\Achievement\Enums;

enum TargetType : string
{

    case DEFAULT = 'default';
    case WEEKLY = 'week';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

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
