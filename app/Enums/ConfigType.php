<?php

namespace App\Enums;

enum ConfigType : string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case SELECT = 'select';


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
