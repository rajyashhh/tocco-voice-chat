<?php

namespace App\Http\Controllers\Dashboard\Events\Chargebenefit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Events\Entities\ChargeTargetEvent;

class AdminChargebenefitController extends Controller
{

    public function index()
    {
        $data = ChargeTargetEvent::all();
        return $data;
    }


    public function store(Request $request)
    {
        $request->validate([
            'value' => 'required'
        ]);
        $data = new ChargeTargetEvent();
        $data->value = $request->value;
        $data->save();
        return 200;
    }


    public function show(string $id)
    {
        $data = ChargeTargetEvent::find($id);
        return  $data;
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'value' => 'required'
        ]);
        $data = ChargeTargetEvent::find($id);
        $data->value = $request->value;
        $data->save();
        return 200;
    }

    public function destroy(string $id)
    {
        $ChargeTargetEvent = ChargeTargetEvent::find($id);
        $ChargeTargetEvent->delete();
        return 200;
    }
}
