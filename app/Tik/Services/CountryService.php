<?php

namespace App\Tik\Services;

use App\Helpers\Common;
use App\Models\CountryCategory;
use Modules\Region\Entities\Region;
use App\Tik\Repositories\CountryRepository;
use Illuminate\Database\Eloquent\Collection;


class CountryService
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
    ) {}

    public function index()
    {
        return $this->countryRepository->getCountries();
    }

    public function indexWithSupporters()
    {
        return $this->countryRepository->getCountriesWithSupporters();
    }

    public function indexByHotAndSupporters($categoryId): Collection|array
    {
        return $this->countryRepository->orderByHotAndSupporters($categoryId);
    }
    public function findById($id)
    {
        return $this->countryRepository->findById($id);
    }
    public function index2()
    {
        return $this->countryRepository->countryGet();
    }

    public function countryDetails($id)
    {
        $country = $this->findById($id);
        if ($country) {
            $bladeUrl = url("/countries/{$id}");

            return Common::apiResponse(1, 'success', $bladeUrl, 200);
        }

        return Common::apiResponse(0, __('not found'), null, 404);
    }

    public function searchCountries($key, $page, $areaManagerId = null)
    {
        $perPage = 10;
        return $this->countryRepository->searchCountry($key, $page, $perPage, $areaManagerId);
    }

    public function searchRegions($key, $page)
    {
        $perPage = 10;
        return Region::query()->selectRaw('concat(name) as name, id')
            ->where('name', 'like', '%' . $key . '%')
            ->orWhere('id', 'like', '%' . $key . '%')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Country categories are retired (admin pages, table and column dropped),
     * but the published APK still calls GET /countries/categories on the home
     * screens — answer with the same shape (an empty collection) without
     * touching the dropped table so it never breaks.
     */
    public function countryCategory()
    {
        return CountryCategory::newModelInstance()->newCollection();
    }

    public function changeRequest($data): true
    {
        $user = request()->user();

        $user->country_id = $data['country_id'];
        $user->save();

        return true;
    }
}
