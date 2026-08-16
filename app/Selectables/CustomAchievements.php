<?php

namespace App\Selectables;

use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;
use Modules\Achievement\Entities\CustomAchievement;

class CustomAchievements extends Selectable
{

    public $model = CustomAchievement::class;

    public function make()
    {
    
        $this->model()->with('images');
        $this->column('id', __('ID'));
        $this->column('name', __('name'));
        $this->column('image', __('image'))->display(function ($path) {
            $path =   $this->images->firstWhere('language', app()->getLocale())?->image ?? $this->images->firstWhere('language', 'en')?->image;
            /** @var Ware $this */
            $url = getImagePath($path);
            return handleShowImageWithSvga($this->id, $url, 60, 60);
        });

        $this->column('show_image', __('show image'))->display(function ($path) {
            $path =   $this->images->firstWhere('language', app()->getLocale())?->show_image ?? $this->images->firstWhere('language', 'en')?->show_image;
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });

        $this->filter(function (Filter $filter) {
            $filter->expand();
            $filter->like('name', __('name'));
        });
    }
}
