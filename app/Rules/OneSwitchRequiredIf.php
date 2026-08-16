<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class OneSwitchRequiredIf implements Rule
{
    private $switch1;
    private $switch2;

    public function __construct($switch1, $switch2)
    {
        $this->switch1 = $switch1;
        $this->switch2 = $switch2;
    }

    public function passes($attribute, $value)
    {
        return !$this->switch1 || !$this->switch2 || $value;
    }

    public function message()
    {
        return 'At least one switch must be enabled.';
    }
}
