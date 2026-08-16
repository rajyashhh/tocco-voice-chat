<?php

namespace App\Enums;

enum PermissionType: string
{
    case BD = 'bd';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'country';
    case SUB_SUPER_ADMIN = 'sub_country';
    case AREA_MANAGER = 'region';
    case SUB_AREA_MANAGER = 'sub_region';
    case SHIPPING_SUPER_ADMIN = 'shipping_super_admin';


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
