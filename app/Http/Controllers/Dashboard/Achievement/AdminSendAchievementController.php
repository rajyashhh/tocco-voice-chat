<?php

namespace App\Http\Controllers\Dashboard\Achievement;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Achievement\AdminSendAchievementResource;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;
use Modules\Achievement\Entities\UserAchievementLevel;

class AdminSendAchievementController extends Controller
{
    use DashBoardTrait;

    public function index()
    {
        $data = UserAchievementLevel::with('achievementLevel','user','achievement')->orderBy('id','desc')->get();
        return AdminSendAchievementResource::collection( $data);
    }

    public function enable_user_achievemnt(Request $request, $id , $status)
    {
        $achievemnt = UserAchievementLevel::find($id);
        if($achievemnt)
        {
            $achievemnt->is_enable = $status  == 'true' ? 1 : 0;
            $achievemnt->update() ;
        }
        return response()->json([
            'status' => 200,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'enable' => 'required',
            'achievement_level_id' => 'nullable|exists:achievement_levels,id'
        ]);
        $img = $request->hasFile('img') ? $this->store_img($request->file('img'), 'images') : null;
        $data = new UserAchievementLevel();
        $data->custom_image    = $img;
        $data->is_enable = $request->enable;
        $data->user_id   = $request->user_id;
        $data->achievement_level_id   = $request->achievement_level_id;
        $data->save();
        return 200;
    }

    public function destroy(string $id)
    {
        $UserAchievementLevel = UserAchievementLevel::find($id);
        if( $UserAchievementLevel->custom_image)
        {
            $this->delete_img($UserAchievementLevel->custom_image);
        }
        $UserAchievementLevel->delete();
        return 200;
    }
}
