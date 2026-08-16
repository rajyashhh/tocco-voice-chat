<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Doctrine\DBAL\Schema\Index;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Tik\Services\CountryService;
use App\Http\Resources\CountryResource;
use App\Http\Resources\CountryCategoryResource;
use App\Http\Resources\CountrySupportersResource;

class CountryController extends Controller
{

    public function __construct(private CountryService $countryService) {}

    public function allCountries(Request $request): JsonResponse
    {
        try {
  

            $categoryId = $request->category_id;


            $countries = $this->countryService->indexByHotAndSupporters($categoryId);

            if ($countries === null) {
                return Common::apiResponse(0, __('Failed to fetch countries data'), null, 500);
            }

            if (empty($countries)) {
                return Common::apiResponse(1, __('No countries available'), CountrySupportersResource::collection($countries));
            }

      

            // Transform to resource collection
            $resourceCollection = CountrySupportersResource::collection($countries);

            // Verify resource collection is not empty
            if ($resourceCollection->isEmpty()) {
                return Common::apiResponse(1, __('No countries available'), $resourceCollection);
            }


            return Common::apiResponse(1, '', $resourceCollection);

        } catch (\Throwable $exception) {
            // Log the exception with full details
            Log::error('Error in allCountries method', [
                'exception' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'category_id' => $request->category_id ?? null,
            ]);

            return Common::apiResponse(0, __('An error occurred while fetching countries'), null, 500);
        }
    }

    public function getCountry($id)
    {
        $country = $this->countryService->findById($id);
        if ($country) {
            return Common::apiResponse(1, '', new CountryResource($country));
        }
        return Common::apiResponse(0, __('not found'), null, 404);
    }

    public function getCountryByHtml($id)
    {
        return $this->countryService->countryDetails($id);
    }

    public function index()
    {
        return $this->countryService->index2();
    }

    public function countries()
    {

        $allTypesWithCountries = DB::table('countries')
            ->leftJoin('users', 'users.country_id', '=', 'countries.id')
            ->select(
                'countries.name as country_name',
                'countries.flag as image',
                DB::raw('COUNT(users.id) as user_count')
            )
            ->groupBy('countries.id', 'countries.name', 'countries.flag')
            ->get();

        // تقسيم النتائج إلى المصفوفتين
        $allCountriesSortedByName = $allTypesWithCountries->sortBy('country_name')->values();
        $hotCountries = $allTypesWithCountries->sortByDesc('user_count')->take(20)->values();

        $data = [
            'all' => $allCountriesSortedByName,
            'hot' => $hotCountries,
        ];

        return Common::apiResponse(1, '', $data);
    }

    public function countryCategory()
    {
        $data = $this->countryService->countryCategory();
        return Common::apiResponse(1, '', CountryCategoryResource::collection($data));
    }

    public function searchCountries(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $areaManagerId = $request->areaManagerId;
        $countries = $this->countryService->searchCountries($key, $page, $areaManagerId);
        return response()->json($countries);
    }

    public function searchRegions(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $countries = $this->countryService->searchRegions($key, $page);
        return response()->json($countries);
    }

    public function changeRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')],
        ]);

        if ($request->user()->country_id == $request->country_id) return Common::apiResponse(0, __('You are already using this country.'), 400);
        try {
            $this->countryService->changeRequest($data);
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, __('Your country has been changed successfully'), 200);
    }
}
