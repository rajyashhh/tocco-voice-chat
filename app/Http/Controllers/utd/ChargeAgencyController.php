<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SalaryTransaction\Entities\ChargeAgency;

class ChargeAgencyController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = ChargeAgency::when($search,function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function show($id){
        $result = ChargeAgency::findOrFail($id);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function delete($id){

        ChargeAgency::findOrFail($id)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function delete_all(Request $request){

        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        ChargeAgency::whereIn('id', $ids)->delete();

        return Common::apiResponse(true, 'Success');
    }

    public function store(Request $request){
        $request->validate([
            'agency_id' => 'required|integer|unique:charge_agencies,agency_id|exists:agencies,id',
        ]);

        $result = ChargeAgency::create([
            'agency_id' => $request->agency_id,
        ]);

        return Common::apiResponse(true, 'Success', $result);
    }

    public function update($id, Request $request){
        $request->validate([
            'agency_id' => "required|integer|unique:charge_agencies,agency_id,{$id}|exists:agencies,id",
        ]);

        $chargeAgency = ChargeAgency::findOrFail($id);
        $chargeAgency->update([
            'agency_id' => $request->agency_id,
        ]);

        return Common::apiResponse(true, 'Success');
    }
}
