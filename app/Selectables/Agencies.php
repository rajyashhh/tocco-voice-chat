<?php

namespace App\Selectables;


use App\Models\Agency;
use Encore\Admin\Grid\Filter;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Grid\Selectable;
use Illuminate\Support\Facades\Auth;

class Agencies extends Selectable
{

    public $model = Agency::class;

    public function make()
    {
        if (in_array(Auth::user()->type, ['country', 'sub_country'])) {
             $this->model()->where('type',1)->where('country_id', Auth::user()->country_id);
        }
        $this->column('id');
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
