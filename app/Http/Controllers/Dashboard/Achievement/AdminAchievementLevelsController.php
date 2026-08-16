<?php

namespace App\Http\Controllers\Dashboard\Achievement;

use App\Http\Controllers\Controller;
use App\Traits\Dashboard\DashBoardTrait;
use Illuminate\Http\Request;
use Modules\Achievement\Entities\AchievementLevel;

class AdminAchievementLevelsController extends Controller
{
    use DashBoardTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $Type = $request->get('Type');
        $data = AchievementLevel::where('achievement_id',$Type)->get();
        return $data;
    }

    public function store(Request $request)
    {
        $request->validate([
            'en_description'   => 'required|max:255',
            'ar_description'   => 'required|max:255',
            'target'           => 'nullable|max:255',
            'achievement_id'   => 'required|max:255',
            'img1'             => 'required|image|mimes:jpeg,png,jpg',
            'img2'             => 'required|image|mimes:jpeg,png,jpg',
        ]);
        $img1_name = $request->hasFile('img1') ? $this->store_img($request->file('img1'), 'images') : null;
        $img2_name = $request->hasFile('img2') ? $this->store_img($request->file('img2'), 'files') : null;
        AchievementLevel::insert([
            'en_description'     => $request->en_description,
            'ar_description'     => $request->ar_description,
            'target'             => $request->target ?? null,
            'achievement_id'     => $request->achievement_id,
            'valid_image'        => $img1_name ,
            'invalid_image'      => $img2_name,
            'target_type'        => $request->type_id,
        ]);
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function show(string $id)
    {
        $data = AchievementLevel::find($id);
        return $data;
    }
    public function update(Request $request, string $id)
    {
        $AchievementLevel = AchievementLevel::find($id);
        $request->validate([
            'en_description'   => 'required|max:255',
            'ar_description'   => 'required|max:255',
            'target'           => 'nullable|max:255',
        ]);
        if( $request->hasFile('img1'))
        {
            $this->delete_img($AchievementLevel->valid_image);
            $img1_name = $request->hasFile('img1') ? $this->store_img($request->file('img1'), 'images') : null;;
            $AchievementLevel->valid_image   = $img1_name ;
        }
        if( $request->hasFile('img2'))
        {
            $this->delete_img($AchievementLevel->invalid_image);
            $img2_name = $request->hasFile('img2') ? $this->store_img($request->file('img2'), 'files') : null;
            $AchievementLevel->invalid_image       = $img2_name;
        }

        $AchievementLevel->en_description    = $request->en_description;
        $AchievementLevel->ar_description    = $request->ar_description;
        $AchievementLevel->target            = $request->target ;
        $AchievementLevel->target_type       = $request->type_id;
        $AchievementLevel->update();
        return response()->json([
            'status' => 200 ,
        ]);
    }

    public function destroy(string $id)
    {
        $AchievementLevel = AchievementLevel::find($id);
        $this->delete_img($AchievementLevel->valid_image ?? null);
        $this->delete_img($AchievementLevel->invalid_image ?? null);
        $AchievementLevel->delete();
        return 200;
    }
}
