<?php

namespace App\Selectables;


use Modules\Vip\Entities\OVip;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;

class OVips extends Selectable
{

    public $model = OVip::class;

    public function make()
    {
        $this->column('id');
        $this->column('level', __('level'));
        $this->column('name', __('name'));
        $this->column('img', __('img'))->display(function ($path) {
            /** @var OVip $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });

        $this->filter(function (Filter $filter) {
            $filter->like('name');
        });
    }
}
