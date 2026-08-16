<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Menu;

class AdminMenu extends Menu
{
    public function getTitleAttribute($value)
    {
        return __($value);
    }
}
