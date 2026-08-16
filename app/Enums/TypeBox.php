<?php

namespace App\Enums;



enum TypeBox: int
{
    case NORMAL = 0;
    case SUPER = 1;
   

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