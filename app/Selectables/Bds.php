<?php

namespace App\Selectables;

use App\Models\Bd;

use App\Helpers\Common;
use Encore\Admin\Grid\Filter;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Grid\Selectable;
use Illuminate\Support\Facades\Auth;
use Modules\Region\Entities\AreaManager;

class Bds extends Selectable
{

    public $model = Bd::class;

    public function make()
    {
        if (in_array(Auth::user()->type, ['region', 'sub_region'])) {
            $authId = auth()->user()->type == 'region' ? auth()->id() : auth()->user()->parent_id;
            $countriesIds = Common::areaCountriesV2($authId);
            $this->model()->whereIn('country_id', $countriesIds);
        }
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
