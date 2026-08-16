<?php

namespace App\Http\Controllers\Dashboard\Achievement;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Achievement\AdminAchievementGifts;
use Illuminate\Http\Request;
use Modules\Achievement\Entities\GiftAchievement;

class AdminAchievementGiftsController extends Controller
{

    public function index()
    {
        $data = GiftAchievement::orderBy('id','desc')->get();
        return AdminAchievementGifts::collection($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'gift_id'   => 'required|exists:gifts,id',
            'user_id'   => 'required|exists:users,id',
        ]);
        GiftAchievement::insert([
            'gift_id'            => $request->gift_id,
            'user_id'            => $request->user_id,
            'achievement_id'     => 3,
        ]);
        return response()->json([
            'status' => 200 ,
        ]);
    }


    public function show(string $id)
    {
        $data = GiftAchievement::find($id);
        return new AdminAchievementGifts($data);
    }

    public function update(Request $request, string $id)
    {
        $GiftAchievement = GiftAchievement::find($id);
        $request->validate([
            'gift_id'   => 'required|exists:gifts,id',
            'user_id'   => 'required|exists:users,id',
        ]);


        $GiftAchievement->gift_id    = $request->gift_id;
        $GiftAchievement->user_id    = $request->user_id;
        $GiftAchievement->update();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function destroy(string $id)
    {
        $GiftAchievement = GiftAchievement::find($id);
        $GiftAchievement->delete();
        return 200;
    }
}
