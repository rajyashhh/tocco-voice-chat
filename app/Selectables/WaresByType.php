<?php

namespace App\Selectables;

use App\Models\Ware;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;

class WaresByType extends Selectable
{
    public $model = Ware::class;
    public function make()
    {
        $this->model()->where('get_type', '!=', 1);

        $this->column('id', __('ID'));
        $this->column('name', __('Name'));
        $this->column('show_img', __('Show Image'))->image('', 30);
         $this->column('img2', __('show_img'))->display(function ($path) {
            /** @var Ware $this */
            $url = getImagePath($path);
            return handleShowImageWithSvga($this->id, $url, 60, 60);
        });
        $this->column('type', __('Type'))->select([
            4 => trans('Avatar Frame'),
            5 => trans('Bubble Frame'),
            6 => trans('Entering Special Effects'),
            28 => __('profile frame'),

        ]);
        $this->column('price', __('price'))->display(function ($coin) {
            $path = 'coin.png';
            $url = getImagePath($path);

            $media = handleShowImageWithTypes($this->id, $url, 25, 25);

            return "<div style='display:flex; align-items:center; gap:8px;'>
                $media
                <span style='font-weight:bold; font-size:14px; color:#333;'>{$coin}</span>
            </div>";
        });

        $this->filter(function (Filter $filter) {
            $filter->like('name', __('Name'));
            $filter->column(0.5, function ($filter) {
                $filter->equal('type', __('Type'))->select([
                    4 => trans('Avatar Frame'),
                    5 => trans('Bubble Frame'),
                    6 => trans('Entering Special Effects'),
                    28 => __('profile frame'),
                ]);
            });
        });
    }
}
