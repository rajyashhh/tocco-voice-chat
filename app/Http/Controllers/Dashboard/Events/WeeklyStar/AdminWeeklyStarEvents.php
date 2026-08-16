<?php

namespace App\Http\Controllers\Dashboard\Events\WeeklyStar;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Room\AdminGiftsResource;
use App\Models\Gift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Events\Entities\WeeklyStar;
use Modules\Events\Entities\WeeklyStarGift;

class AdminWeeklyStarEvents extends Controller
{

    public function index()
    {
        $data = WeeklyStar::where('type','weekly_star')->get();
        return $data;
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required'
        ]);
        //gifts_id
        $start = Carbon::parse($request->start_date)->format('Y-m-d');
        $end = Carbon::parse($request->start_date)->addWeek(1)->format('Y-m-d');
        $data = new WeeklyStar();
        $data->admin_id = $request->user()->id;
        $data->editor_id = $request->user()->id;
        $data->start_date = $start;
        $data->end_date = $end;
        $data->type = 'weekly_star';
        $data->save();

        if($request->gifts_id)
        {
            for($i=0 ; $i < count($request->gifts_id); $i++)
            {
                $item = Gift::find($request->gifts_id[$i]);
                if($item)
                {
                    WeeklyStarGift::insert([
                        'gift_id'          =>    $request->gifts_id[$i],
                        'weekly_star_id'   => $data->id ,
                    ]);
                }
            }
        }

        return 200;
    }


    public function show(string $id)
    {
        $data = WeeklyStar::with('gifts')->find($id);
        $gifts_id = WeeklyStarGift::where('weekly_star_id',$data->id)->pluck('gift_id')->toArray();
        return [
            'data' =>$data,
            'gifts' => AdminGiftsResource::collection($data->gifts)  ,
            'gifts_id' => $gifts_id
        ];
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'start_date' => 'required'
        ]);

        $data = WeeklyStar::find($id);
        WeeklyStarGift::where('weekly_star_id',$data->id)->delete();
        $start = Carbon::parse($request->start_date)->format('Y-m-d');
        $end = Carbon::parse($request->start_date)->addWeek(1)->format('Y-m-d');
        $data->start_date = $start;
        $data->end_date = $end;
        $data->save();

        if($request->gifts_id)
        {
            for($i=0 ; $i < count($request->gifts_id); $i++)
            {
                $item = Gift::find($request->gifts_id[$i]);
                if($item)
                {
                    WeeklyStarGift::insert([
                        'gift_id'          =>    $request->gifts_id[$i],
                        'weekly_star_id'   => $data->id ,
                    ]);
                }
            }
        }
        return 200;
    }

    public function destroy(string $id)
    {
        $PkEvent = WeeklyStar::find($id);
        $PkEvent->delete();
        return 200;
    }
}
