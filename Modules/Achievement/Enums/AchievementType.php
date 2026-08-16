<?php

namespace Modules\Achievement\Enums;

enum AchievementType: string
{
    case GIFT_TARGET = 'gift_target';
    case RECHARGE_TARGET = 'recharge_target';
    case ROOM_TARGET = 'room_target';

    public function getName(): string
    {
        $ucfirst = ucfirst(strtolower($this->name));
        return str_replace('_', ' ', $ucfirst);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
