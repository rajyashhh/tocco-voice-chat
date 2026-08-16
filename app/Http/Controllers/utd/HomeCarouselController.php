<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Enums\FormCarousel;
use App\Enums\TypeCarousel;
use App\Models\HomeCarousel;
use Illuminate\Http\Request;
use App\Enums\EventTypeCarousel;
use App\Http\Controllers\Controller;

class HomeCarouselController extends Controller
{
    public function index()
    {
        $search = request('search');
        $sort = request('sort') ?? 'asc';
        $perPage = request('per_page') ?? 10;

        $results = HomeCarousel::when($search, function ($q) use ($search) {
            $q->where('id', $search);
        })
            ->when($sort, function ($q) use ($sort) {
                $q->orderBy('id', $sort);
            })
            ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $results);
    }


    public function show($id)
    {
        $result = HomeCarousel::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete_all(Request $request)
    {

        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        HomeCarousel::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete($id)
    {
        $result = HomeCarousel::findOrFail($id);
        $result->delete();
        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request)
    {
        $request->validate([
            'sort' => 'required|integer',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust image validation as needed
            'enable' => 'required|boolean',
            'form' => 'nullable|in:0,1,2,3',
            'input' => 'nullable|string|required_if:form,1,2,3',
            'type' => 'required|in:room,normal,link,event',
            'owner_id' => 'nullable|integer|required_if:type,room',
            'url' => 'nullable|url|required_if:type,link',
            'event_type' => 'nullable|in:event,pk_event,weekly_star,charge_event,event_period|required_if:type,event',

        ]);

        $img = Common::upload('images', $request->file('img'));

        // Prepare data for creation
        $data = [
            'sort' => $request->input('sort'),
            'img' => $img,
            'enable' => $request->input('enable'),
            'type' => $request->input('type'),
            'form' => $request->input('form'),
            'input' => $request->input('input'),
        ];



        // Handle conditional logic for 'type'
        switch ($request->input('type')) {
            case 'room':
                $data['owner_id'] = $request->input('owner_id');
                break;
            case 'link':
                $data['url'] = $request->input('url');
                break;
            case 'event':
                $data['event_type'] = $request->input('event_type');
                if ($request->input('event_type') === 'event') {
                    $data['url'] = $request->input('url');
                }
                break;
        }

        // Create a new HomeCarousel entry using the create() method
        $result = HomeCarousel::create($data);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'sort' => 'required|integer',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust image validation as needed
            'enable' => 'required|boolean',
            'form' => 'nullable|in:0,1,2,3',
            'input' => 'nullable|string|required_if:form,1,2,3',
            'type' => 'required|in:room,normal,link,event',
            'owner_id' => 'nullable|integer|required_if:type,room',
            'url' => 'nullable|url|required_if:type,link',
            'event_type' => 'nullable|in:event,pk_event,weekly_star,charge_event,event_period|required_if:type,event',
        ]);

        $result = HomeCarousel::findOrFail($id);



        // Prepare data for creation
        $data = [
            'sort' => $request->input('sort'),
            'enable' => $request->input('enable'),
            'type' => $request->input('type'),
            'form' => $request->input('form'),
            'input' => $request->input('input'),
        ];

        if ($request->hasFile('img')) {
            $img = Common::upload('images', $request->file('img'));
            $data['img'] = $img;
        }


        // Handle conditional logic for 'type'
        switch ($request->input('type')) {
            case 'room':
                $data['owner_id'] = $request->input('owner_id');
                break;
            case 'link':
                $data['url'] = $request->input('url');
                break;
            case 'event':
                $data['event_type'] = $request->input('event_type');
                if ($request->input('event_type') === 'event') {
                    $data['url'] = $request->input('url');
                }
                break;
        }

        // Create a new HomeCarousel entry using the create() method
        $result->update($data);

        return Common::apiResponse(true, 'Success');
    }

    public function update_is_active($id, Request $request)
    {

        $request->validate([
            'enable' => 'required'
        ]);

        $result = HomeCarousel::findOrFail($id);

        $result->enable = $request->enable;

        $result->save();

        return Common::apiResponse(true, 'Success');
    }

    public function type()
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => (object) TypeCarousel::getTranslatedOptions(),
        ]);
    }

    public function eventType()
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => (object) EventTypeCarousel::getTranslatedOptions(),
        ]);
    }

    public function form()
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => (object) FormCarousel::list(),
        ]);
    }
}
