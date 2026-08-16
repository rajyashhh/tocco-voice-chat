<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\Offer;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;





class OfferController extends Controller
{

    public function index(Request $request)
    {
        $id = $request->id;
        $page = $request->page;
        $perPage = $request->per_page;
        $data = Offer::when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(1, '', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'title_en'         => 'required|string|max:255',
            'body'         => 'required|string|max:255',
            'body_en'         => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {

            Offer::create($request->all());
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        try {
            $offer =   Offer::findOrFail($id);
            return Common::apiResponse(1, 'done', $offer);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'title_en'         => 'required|string|max:255',
            'body'         => 'required|string|max:255',
            'body_en'         => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {

            Offer::where('id', $id)->update($request->all());
            return Common::apiResponse(1, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function delete($id)
    {

        try {
            $offer =   Offer::findOrFail($id);
            $offer->delete();
            return Common::apiResponse(1, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}
