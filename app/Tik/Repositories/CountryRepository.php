<?php

namespace App\Tik\Repositories;

use App\Helpers\Common;
use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CountryRepository extends AbstractRepository
{


    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new Country());
    }


    public function getCountries()
    {
        return $this->model->query()->where('status', 1)->get();
    }

    public function getCountriesWithSupporters()
    {
        return Cache::remember(
            'countries_with_supporters',
            300, // 5 minutes cache
            fn() => $this->model->query()
                ->select('id', 'name', 'e_name', 'flag', 'language', 'phone_code', 'iso')
                ->where('status', 1)
                ->with([
                    'supporters' => function ($q) {
                        $q->orderByDesc('total_sent')->take(3);
                    },
                    'supporters.sender:id,name,uuid',
                    'supporters.sender.profile:id,user_id,avatar'
                ])
                ->get()
        );
    }

    public function orderByHotAndSupporters($categoryId): Collection|array
    {
        // Country categories were retired (country_categories is empty on every
        // install); the published app only ever sends category_id after picking
        // a category from the always-empty list, so no filter is applied. The
        // cache key keeps the segment for compatibility with existing flushes.
        return Cache::remember(
            "countries_hot_supporters_{$categoryId}",
            300, // 5 minutes cache
            fn() => $this->model->query()
                ->select('id', 'name', 'e_name', 'flag', 'language', 'phone_code', 'iso')
                ->where('status', 1)
                ->withCount(['rooms as hot_rooms_count' => function ($q) {
                    $q->whereNotNull('hour_hot')
                        ->orWhere('hour_hot', '!=', '');
                }])
                ->withCount(['rooms as total_rooms' => function ($q) {
                    $q->where('status', 1);
                }])
                // Countries with the most active rooms come first so the home
                // country bar surfaces where the action is right now.
                ->orderByDesc('total_rooms')
                ->orderByDesc('hot_rooms_count')
                ->with([
                    'supporters' => function ($q) {
                        $q->orderByDesc('total_sent')->take(3);
                    },
                    'supporters.sender:id,name,uuid',
                    'supporters.sender.profile:id,user_id,avatar'
                ])
                ->get()
        );
    }
    public function countryGet()
    {
        return $this->model->select('id', 'name', 'e_name', 'flag', 'iso')->orderByDesc('id')->get();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function findByPhoneCode($phoneCode)
    {
        return $this->model->query()->where('phone_code', $phoneCode)->first();
    }

    public function searchCountry($key, $page, $perPage, $areaManagerId = null)
    {
        $countriesIds = Common::areaCountriesV2($areaManagerId);

        return $this->model
            ->query()
            ->selectRaw('concat(name, " - ", e_name) as name, id')
            ->when(!empty($countriesIds), function ($q) use ($countriesIds) {
                $q->whereIn('id', $countriesIds);
            })
            ->where(function ($q) use ($key) {
                $q->where('name', 'like', "%{$key}%")
                    ->orWhere('e_name', 'like', "%{$key}%")
                    ->orWhere('id', 'like', "%{$key}%");
            })
            ->paginate($perPage, ['*'], 'page', $page);
    }
}




