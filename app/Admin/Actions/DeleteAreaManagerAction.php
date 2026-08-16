<?php

namespace App\Admin\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Modules\Region\Entities\Region;
use Modules\Region\Entities\RegionCountry;
use Modules\Country\Entities\SuperAdmin;
use Modules\Region\Entities\AreaManager;
use Modules\Region\Entities\SubAreaManager;

class DeleteAreaManagerAction extends RowAction
{
    public $name;

    public function __construct()
    {
        parent::__construct();
        $this->name = __('delete');
    }

    public function setModel(Model $model)
    {

        return parent::setModel($model);
    }


    public function handle(Model $model, Request $request)
    {
        if($model->default ==1)  return $this->response()->error('can not delete default area admin')->refresh();
        $user = User::find($model->app_id);
        if ($user) {
            $user->is_area_manager = 0;
            $user->save();
        }
        $default = AreaManager::where('default', 1)->first();
        if(!$default) return $this->response()->error('Default area manager not found. Please set a default area manager before deleting.')->refresh();
        $this->transferCountriesToDefaultManager($model,$default);

        if ($default) SuperAdmin::where('parent_id', $model->id)->update(['parent_id' => $default->id]);
        $subAppId = SubAreaManager::where('parent_id', $model->id)->pluck('app_id')->toArray();
        if (!empty($subAppId)) {
            User::whereIn('id', $subAppId)->update(['sub_area_manger' => 0]);
            SubAreaManager::where('parent_id', $model->id)->delete();
        }

        $model->delete();

        return $this->response()->success(__('dashboard.successful'))->refresh();
    }

    protected function transferCountriesToDefaultManager(AreaManager $manager,AreaManager $defaultManager): void
    {
        if (!$defaultManager) {
            return;
        }
        $defaultRegion = Region::firstOrCreate(
            ['manager_id' => $defaultManager->id],
            ['name' => 'Default Region for Default Manager']
        );
        $region = Region::where('manager_id', $manager->id)->first();
        if (!$region) {
            return;
        }
        $countryIds = RegionCountry::where('region_id', $region->id)
            ->pluck('country_id')
            ->toArray();

        if (!empty($countryIds)) {
            RegionCountry::where('region_id', $region->id)->delete();

            foreach ($countryIds as $countryId) {
                RegionCountry::firstOrCreate([
                    'region_id' => $defaultRegion->id,
                    'country_id' => $countryId,
                ]);
            }
        }
    }

    public function dialog()
    {
        $this->confirm(__('dashboard.chickDelete'), '', []);
    }
}
