<?php

namespace App\Traits\HelperTraits;

trait AdminTrait
{
    public static function getSwitchStates()
    {
        return [
            'on' => ['value' => 1, 'text' => 'yes', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'no', 'color' => 'danger'],
        ];
    }

    public static function getSwitchStatesv2($field = null)
    {
        return [
            'on'  => ['value' => 1, 'text' => __('On'),  'color' => 'success'],
            'off' => ['value' => 0, 'text' => __('Off'), 'color' => 'danger'],
        ];
    }
    public static function getSwitchStates2()
    {
        return [
            'on' => ['value' => 1, 'text' => 'pass', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'stop', 'color' => 'danger'],
        ];
    }

    public static function getSwitchStatestypeAgancy()
    {
        return [
            'on' => ['value' => 1, 'text' => 'pass', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'stop', 'color' => 'danger'],
        ];
    }

    public static function getSwitchStatesGiftMucic()
    {
        return [
            'on' => ['value' => 1, 'text' => 'yes', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'no', 'color' => 'danger'],
        ];
    }
    public static function getSwitchStatesGiftINtrnahional()
    {
        return [
            'on' => ['value' => 1, 'text' => 'yes', 'color' => 'success'],
            'off' => ['value' => 0, 'text' => 'no', 'color' => 'danger'],
        ];
    }
}
