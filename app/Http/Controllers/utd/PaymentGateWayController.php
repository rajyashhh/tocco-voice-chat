<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class PaymentGateWayController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = PaymentGateway::when($search,function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true,'Success', $result);
    }

    public function show($id){
        $result = PaymentGateway::findOrFail($id);

        return Common::apiResponse(true,'Success', $result);
    }

    public function delete($id){

        PaymentGateway::findOrFail($id)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request){

        $request->validate([
            'ids' => 'required'
        ]);


        $ids = explode(',', $request->ids);

        PaymentGateway::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request){
        $validated = $request->validate([
            'title' => 'required',
            'photo' => 'required'
        ]);

        $validated['photo'] = Common::upload('images', $request->file('photo'));

        $result = PaymentGateway::create($validated);

        return Common::apiResponse(true, 'Success',$result);
    }

    public function update($id, Request $request){

        $validated = $request->validate([
            'title' => 'required',
            'photo' => 'nullable'
        ]);


        if($request->hasFile('photo')){
            $validated['photo'] = Common::upload('images', $request->file('photo'));
        }

        $result = PaymentGateway::findOrFail($id);

        $result->update($validated);

        return Common::apiResponse(true, 'Success');
    }
}
