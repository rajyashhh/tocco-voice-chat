<?php

namespace Modules\Moment\Http\Controllers;

use App\Facades\CustomNotification;
use App\Models\User;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Moment\Entities\Moment;
use Illuminate\Support\Facades\Auth;
use Modules\Moment\Entities\MomentCommint;
use Illuminate\Contracts\Support\Renderable;
use Modules\Moment\Http\Services\MomentService;
use Modules\Moment\Http\Services\MomentCommentsService;
use Modules\Moment\Transformers\MomentCommmintResource;

class MomentUserCommentController extends Controller
{

    public $momentCommentsService;
    public $momentsService;

    public function __construct(MomentService $momentsService, MomentCommentsService $momentCommentsService) {
        $this->momentsService        = $momentsService;
        $this->momentCommentsService = $momentCommentsService;
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

        $paginateComments = $this->momentCommentsService->showComments($moment);
        $data =  MomentCommmintResource::collection($paginateComments);

        return Common::apiResponse(1, 'successful', $data, 200);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store($moment_id ,Request $request )
    {
        $moment='isSet';
        try {
            $moment = Moment::findOrFail($moment_id);
        } catch (\Exception $e) {
            $moment = null;
        }
        if ($moment == null){
            return Common::apiResponse(0, 'Moment not founded', [], 402);
        }


        $comment =$request->comment;
        // $moment_id =$moment_id;
        $user_id =Auth::id();
         $user = User::find($user_id);
         $created=MomentCommint::create([
            'user_id' => $user_id,
            'comment' => $comment,
            'moment_id' => $moment_id,
         ]);
         if (!$created) {

            return Common::apiResponse(0, 'try_again');
         }
        CustomNotification::momentComment($moment, $user);

        return Common::apiResponse(1, 'success');

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
    public function destroy($momentId , $id)
    {

        try {
            $moment = $this->momentsService->findOrFail($momentId);
        } catch (\Exception $e) {
            $moment = null;
        }
        if ($moment == null){
            return Common::apiResponse(0, 'Moment not founded', [], 402);
        }
        try {
            $this->momentCommentsService->delete($id, $moment);
            return Common::apiResponse(1, 'success', [], 200);

        } catch (\Exception $e) {
            return Common::apiResponse(0, 'try later', [], 402);

        }

    }
}
