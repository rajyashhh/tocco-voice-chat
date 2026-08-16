<?php

namespace App\Admin\Selectable;

use App\Models\ImageColor;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;

class ImageColors extends Selectable
{
    public $model = ImageColor::class;

    protected $perPage = 10; // Sets the number of records per page

    public function make()
    {
        $this->column('id', __('ID'));
        $this->column('name', __('Name'));
        $this->column('image', __('image'))->image('', 50);
        $this->column('color', __('color'))->display(function ($color) {
            return "<div style='width: 20px; height: 20px; background-color: {$color}; border: 1px solid #ccc;'></div>";
        });

        $this->filter(function (Filter $filter) {
            $filter->like('name', __('Name'));
        });
    }
}
