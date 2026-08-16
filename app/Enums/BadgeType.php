<?php

namespace App\Enums;

enum BadgeType: string
{
    case Regular = 'regular';
    case Top = 'top';

    public function label(): string
    {
        return match ($this) {
            self::Regular => __('regular'),
            self::Top     => __('Top'),
        };
    }

    public static function options(): array
    {
        return [
            self::Regular->value => self::Regular->label(),
            self::Top->value     => self::Top->label(),
        ];
    }
}
