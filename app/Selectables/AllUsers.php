<?php

namespace App\Selectables;

use App\Models\User;

use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;

class AllUsers extends Selectable
{

    public $model = User::class;

    public function make()
    {
        $this->grid->model()->with('profile');


        $this->column('id');
        $this->column('uuid');
        $this->column('name');
        $this->column('profile.avatar', __('Image'))->image();

        $this->filter(function (Filter $filter) {
            $filter->like('name');
        });
    }
}
