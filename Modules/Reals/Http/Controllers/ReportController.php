<?php

namespace Modules\Reals\Http\Controllers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Reals\Entities\Real;
use Modules\Reals\Entities\ReportReals as EntitiesReportReals;
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
     * @return Renderable
     */
    public function store(Request $request)
    {
        $data =$request->all();
        $data['Reporter_id'] = Auth::user()->id;

        $is_set_report = EntitiesReportReals::where('real_id',$data['real_id'])->where('Reported_id',$data['Reported_id'])->where('Reporter_id',$data['Reporter_id'])->first();
        if($is_set_report){
            return Common::apiResponse(0, 'You already reported it', [], 402);

        }
        
        $real= Real::where('id',$data['real_id'])->first();
          if(!$real){
            return Common::apiResponse(0, 'real not founded', [], 402);
        }

        $user= User::where('id',$data['Reported_id'])->first();
          if(!$user){
            return Common::apiResponse(0, 'user not found', [], 402);

        }
        $data['Reported_id'] = $user->id;

        EntitiesReportReals::create($data);
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
