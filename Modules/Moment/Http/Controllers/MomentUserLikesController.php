<?php

namespace Modules\Moment\Http\Controllers;

use App\Helpers\Common;
use Database\Seeders\config;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Modules\Moment\Entities\Moment;
use Illuminate\Support\Facades\Auth;
use Modules\Moment\Entities\MomentLikes;
use Illuminate\Contracts\Support\Renderable;
use Modules\Moment\Http\Services\MomentService;
use Modules\Moment\Http\Services\MomentLikesService;
use Modules\Moment\Transformers\MomentlikesResource;

class MomentUserLikesController extends Controller
{

    public $momentLikesService;
    public $momentsService;

    public function __construct(MomentService $momentsService, MomentLikesService $momentLikesService) {
        $this->momentsService        = $momentsService;
        $this->momentLikesService = $momentLikesService;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index($moment_id)
    {
        $moment = Moment::where('id',$moment_id)->first();

        if (!$moment){
            return Common::apiResponse(0, 'Moment not founded', [], 402);
        }

        $paginateLikes = $this->momentLikesService->showLikes($moment);
        $data =  MomentlikesResource::collection($paginateLikes);

        return Common::apiResponse(1, 'successful', $data, 200);



    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store($moment_id, Request $request)
    {
        // $moment='isSet';
        // try {
        //     $moment = Moment::findOrFail($moment_id);
        // } catch (\Exception $e) {
        //     $moment = null;
        // }
        // if ($moment == null){
        //     return Common::apiResponse(0, 'moment not founded', [], 402);
        // }

        // $user_id =Auth::id();

        //  $created=MomentLikes::create([
        //     'user_id' => $user_id,
        //     'moment_id' => $moment_id,
        //  ]);
        //  if (!$created) {

        //     return Common::apiResponse(0, 'try_again');
        //  }

        // return Common::apiResponse(1, 'success');
        $user = Auth::user();
        try {
            $moment =  Moment::findOrFail($moment_id);
        } catch (\Exception $e) {
            $moment = null;
        }
        if ($moment == null){
            return Common::apiResponse(0, 'Moment not founded', [], 402);
        }

        $add =  $this->momentLikesService->likeOrUnLike( $moment, $user);
        if ($add == 'un Like'){
            return Common::apiResponse(1, 'success', [], 200);
        }
        if ($add == 'Like'){

            CustomNotification::likeMoment($moment, $user);
            return Common::apiResponse(1, 'success', [], 200);
        }


        return Common::apiResponse(1, 'success', [], 200);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('moment::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('moment::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
