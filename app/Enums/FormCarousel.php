<?php

namespace App\Enums;



enum FormCarousel: int
{
    case HOURS = 1;
    case DAYS = 2;
    case MONTH = 3;

    public function label(): string
    {
        return trans("api.{$this->name}");
    }

    public static function list(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->name;
        }
        return $result;
    }
}