<?php

namespace App\Selectables;

use App\Models\Country;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;

class Countries extends Selectable
{
    public $model = Country::class;

    public function make()
    {
        $this->column('id', 'ID');
        $this->column('flag', __('flag'))->display(function ($path) {
            /** @var OVip $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $this->column('name', __('Name'))
            ->display(function ($path) {

                return app()->getLocale() === 'ar' ? $path : ($this->e_name ?? $path);
            });;

        $this->filter(function (Filter $filter) {
            $filter->where(function ($query) {
                $query->where('name', 'like', "%{$this->input}%")
                    ->orWhere('e_name', 'like', "%{$this->input}%");
            }, 'Name');
            $filter->like('code', __('Code'));
        });
    }
}
