<?php

namespace App\Rules;

use App\Helpers\Common;
use Illuminate\Contracts\Validation\Rule;

class ValidUsd implements Rule
{
    protected $diamonds;
    protected $appTarget;
    protected $dollar;

    public function __construct($diamonds)
    {
        $this->diamonds = $diamonds;
        $this->appTarget = Common::getConf('one_usd_value_in_coins');
        $this->dollar = ($this->diamonds / $this->appTarget) * 0.6;
        $this->dollar   =     number_format($this->dollar, 2, '.', '');
    }

    public function passes($attribute, $value)
    {
        return $value <= $this->dollar;
    }

    public function message()
    {
        return 'The usd value must be ' . $this->dollar;
    }
}