<?php

namespace Modules\Moment\Http\Controllers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Entities\Real;
use Modules\Moment\Entities\ReportMoment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('reals::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(Request $request )
    {



        return view('reals::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $momentId)
    {
        $userId = Auth::id();
        $data = [];
        if (!$request['description']) return Common::apiResponse(false, 'please add description to help owner to remove this moment');
        if (!$request['type']) return Common::apiResponse(false, 'please select type');
        $data['description'] = $request['description'];
        $data['type'] = $request['type'];
        $data['moment_id'] = $momentId;
        $data['Reporter_id'] = $userId;
        $moment= Moment::where('id',$data['moment_id'])->first();
        if(!$moment){
            return Common::apiResponse(0, 'Moment not founded', [], 402);
        }
        $data['Reported_id'] = $moment->user_id;

        $is_set_report = ReportMoment::where('moment_id',$data['moment_id'])->where('Reported_id',$data['Reported_id'])->where('Reporter_id',$data['Reporter_id'])->first();
        if($is_set_report){
            return Common::apiResponse(0, 'You already reported it', [], 402);

        }

        $user= User::withoutAppends()->where('id',$data['Reported_id'])->first();
          if(!$user){
            return Common::apiResponse(0, 'user not found', [], 402);

        }
        $data['Reported_id'] = $user->id;

        ReportMoment::create($data);
        if (!$data) {
            return Common::apiResponse(0, 'try again later', [], 402);
        }
        return Common::apiResponse(1, 'successful','', 200);


    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('reals::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('reals::edit');
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
