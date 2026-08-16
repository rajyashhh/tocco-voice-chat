<?php

namespace App\Selectables;


use Modules\Country\Entities\SuperAdmin;
use Encore\Admin\Grid\Filter;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Grid\Selectable;

class SuperAdmins extends Selectable
{

    public $model = SuperAdmin::class;

    public function make()
    {
        $this->column('id');
        $this->column('username', __('username'));
        $this->column('name', __('name'));
        $this->column('avatar', __('img'))->display(function ($path) {
            /** @var OVip $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });

        $this->filter(function (Filter $filter) {
            $filter->like('name');
        });
    }
}
