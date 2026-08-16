<?php

namespace App\Http\Controllers\Dashboard\Events\PK;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Events\AdminPKEventRewords;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkReward;

class AdminPKEventsController extends Controller
{

    public function index()
    {
        $data = PkEvent::all();
        return $data;
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required'
        ]);
        $start = Carbon::parse($request->start_date)->format('Y-m-d');
        $end = Carbon::parse($request->start_date)->addWeek(1)->format('Y-m-d');
        $data = new PkEvent();
        $data->admin_id = $request->user()->id;
        $data->editor_id = $request->user()->id;
        $data->start_date = $start;
        $data->end_date = $end;
        $data->save();
        return 200;
    }

    public function show(string $id)
    {
        $data = PkEvent::find($id);
        return  $data;
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'start_date' => 'required'
        ]);
        $data = PkEvent::find($id);
        $start = Carbon::parse($request->start_date)->format('Y-m-d');
        $end = Carbon::parse($request->start_date)->addWeek(1)->format('Y-m-d');
        $data->start_date = $start;
        $data->end_date = $end;
        $data->save();
        return 200;
    }

    public function destroy(string $id)
    {
        $PkEvent = PkEvent::find($id);
        $PkEvent->delete();
        return 200;
    }



}
