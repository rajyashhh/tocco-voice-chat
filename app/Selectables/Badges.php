<?php

namespace App\Selectables;

use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;
use Modules\Badge\Entities\Badge;
use Encore\Admin\Admin;

class Badges extends Selectable
{

    public $model = Badge::class;

    public function make()
    {
        $lang = app()->getLocale();
        $this->model()->with('images')->orderBy('priority', 'desc');
        $this->column('id', __('ID'));
        $this->column('name', __('name'));
        $this->column('image', __('image'))->display(function ($path) {
            $path =   $this->images->firstWhere('language', app()->getLocale())?->image ?? $this->images->firstWhere('language', 'en')?->image;
            /** @var Ware $this */
            $url = getImagePath($path);
            return handleShowImageWithSvga($this->id, $url, 100, 100, 4, 'contain');
        });

        $this->column('show_image', __('show image'))->display(function ($path) {
            $path =   $this->images->firstWhere('language', app()->getLocale())?->show_image ?? $this->images->firstWhere('language', 'en')?->show_image;
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 100, 100, 4, 'contain');
        });

        $this->column('priority', __('Priority'))->sortable();

        $this->filter(function (Filter $filter) {
            $filter->expand();
            $filter->like('name', __('name'));
            $filter->equal('priority', __('Priority'));
        });
    }
}
