<?php

namespace App\Enums;

enum ConfigCategory : string
{
    case SYSTEM_SETTINGS = 'system_settings';
    case TARGET_SETTINGS = 'target_settings';
    case ZEGO_SETTINGS = 'zego_settings';
    case SMS_SETTINGS = 'sms_settings';
    case FAMILY_SETTINGS = 'family_settings';
    case PAYMENT_SETTINGS = 'payment_settings';
    case ROOM_SETTINGS = 'room_settings';
    case AGENCY_SETTINGS = 'agency_settings';
    case LEVEL_SETTINGS = 'level_settings';


    public static function getOptions(): array
    {
        return array_column(self::cases(), 'value');
    }

    // Method to get translated options
    public static function getTranslatedOptions(): array
    {
        return translateCategory(self::getOptions());
    }

    public static function getLinkedStringsByValue(string $value): ?array
    {
        return match ($value) {
            self::SYSTEM_SETTINGS->value => [],
            self::TARGET_SETTINGS->value => [],
            self::ZEGO_SETTINGS->value => [],
            self::SMS_SETTINGS->value => [],
            self::FAMILY_SETTINGS->value => [],
            self::PAYMENT_SETTINGS->value => [],
            self::ROOM_SETTINGS->value => [],
            self::AGENCY_SETTINGS->value => ['targets', 'target-percentage'],
            self::LEVEL_SETTINGS->value => [ ],
            default => null,
        };
    }
}
