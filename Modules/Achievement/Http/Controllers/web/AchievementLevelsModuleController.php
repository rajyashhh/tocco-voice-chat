<?php

namespace Modules\Achievement\Http\Controllers\web;

use App\Helpers\Common;
use App\Models\AchievementValidImage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Support\Renderable;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Achievement\Http\Services\AchievementLevelsService;

class AchievementLevelsModuleController extends Controller
{

    protected $userAchievementService;

    public function __construct(AchievementLevelsService $userAchievementService)
    {
        $this->userAchievementService = $userAchievementService;
    }

    public function store(Request $request)
    {
        $achievementLevel_id = request('achievement_level_id');
        $userId = request('user_id');
        $gift = request('gift_achievement_id');

        $adminId = Auth::user()->id;

        $achievementLevel = AchievementLevel::find($achievementLevel_id);
        if ($achievementLevel_id == null && $request->hasFile('custom_image')) {
            $customImage = Common::upload('custom_image', $request->file('custom_image'));
            $attributes = [
                'user_id'       => $userId,
                'custom_image' => $customImage,
                'file' => $customImage,
                'achievement_id' => $request->input('achievement_id'),
                'admin_id' =>  $adminId,
            ];

            UserAchievementLevel::create($attributes);
            AchievementValidImage::create([
                'image' => $customImage,
                'user_id' => Auth::user()->id,
                'type' => 'user',
            ]);
        } elseif ($achievementLevel_id == null && $request->hasFile('custom_file')) {
            $custom_file = Common::upload('custom_file', $request->file('custom_file'));
            $attributes = [
                'user_id'       => $userId,
                'file' => $custom_file,
                'custom_image' => $custom_file,
                'achievement_id' => $request->input('achievement_id'),
                'admin_id' =>  $adminId,
            ];

            UserAchievementLevel::create($attributes);
            AchievementValidImage::create([
                'file' => $custom_file,
                'user_id' => Auth::user()->id,
                'type' => 'user',
            ]);
        } elseif ($achievementLevel_id == null && request('custom_image')) {
            $customImagepath = request('custom_image');
            $attributes = [
                'user_id'       => $userId,
                'custom_image' => $customImagepath,
                'file' => $customImagepath,
                'achievement_id' => $request->input('achievement_id'),
                'admin_id' =>  $adminId,
            ];

            UserAchievementLevel::create($attributes);
        } elseif ($achievementLevel != null) {
            $res = $this->userAchievementService->assignAchievementLevelToUserByAdmin($userId, $achievementLevel);
            if (!$res) {
                $error = new MessageBag([
                    'title' => 'Error',
                    'message' => __('this user not found'),
                ]);

                return redirect()->route(nameRoute('admin.get-view-page'), compact('error')); // Error message added
            }
        } elseif ($achievementLevel_id == null && $gift) {
            $attributes = [
                'user_id'       => $userId,
                'gift_achievement_id' => $gift,
                'achievement_id' => $request->input('achievement_id'),
            ];

            UserAchievementLevel::create($attributes);
        }

        return redirect()->route(nameRoute('admin.achievement-dedicate.index'));
    }



    public function getAchievementLevels($achievementId)
    {
        $levels = AchievementLevel::where('achievement_id', $achievementId)->pluck('target', 'id');
        return response()->json($levels);
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('achievement::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('achievement::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */


    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function viewPage()
    {
        return redirect(nameRoute('admin/user-achievement-levels'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('achievement::edit');
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
