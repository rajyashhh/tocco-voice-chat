<?php

namespace Modules\Achievement\Http\Controllers;

use App\Helpers\Common;
use App\Http\Resources\AchievementValidImagesResource;
use App\Models\AchievementValidImage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkReward;
use Modules\Events\Entities\Reward;
use Modules\Events\Entities\RewardTarget;
use Modules\Events\Entities\WeeklyStar;
use Illuminate\Contracts\Support\Renderable;
use Modules\Achievement\Entities\Achievement;
use Modules\Events\Entities\ChargeTargetEvent;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Achievement\Http\Services\AchievementService;
use Modules\Achievement\Transformers\AchievementResource;
use Modules\Achievement\Transformers\AchievementDetailResource;
use Modules\Achievement\Transformers\AchievementOneLevelsResource;

class AchievementController extends Controller
{

    // public function __construct(private AchievementService $achievementService) { }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    // public function index()
    // {
    //     $user = Auth::user();
    //     $data = $this->achievementService->show($user);
    //     return Common::apiResponse(1, 'successfully', $data);
    // }

    public function achivement_select(Request $request)
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $request) {
            UserAchievementLevel::where('user_id', $user->id)->update(['picked' => 0]);

            if (!empty($request->ids) && is_array($request->ids)) {
                UserAchievementLevel::where('user_id', $user->id)
                    ->whereIn('id', $request->ids)
                    ->update(['picked' => 1]);
            }
        });

        return Common::apiResponse(1, 'Achievements updated successfully', []);
    }

    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('achievement::show');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function get_all_select($id = null)
    {
        $user = Auth::user();
        if (isset($id)) {
            $achievements = Achievement::whereHas("userAchievments", function ($q) use ($id) {
                $q->where("enable", 1)->where("picked", 1)->where("user_id", $id);
            })->where('id', $id)->with([
                'levels' => function ($query) {
                    $query->withCount([
                        'achievementUsers as enable' => function ($query) {
                            $query->where('user_id', auth()->id())
                                ->where('is_enable', true);
                        }
                    ]);
                },
            ])->get();


            return Common::apiResponse(1, 'successfully', AchievementOneLevelsResource::collection($achievements));
        }

        $achievements = Achievement::whereHas("userAchievments", function ($q) use ($user) {
            $q->where("enable", 1)->where("picked", 1)->where("user_id", $user->id);
        })->get();

        return Common::apiResponse(1, 'successfully', AchievementResource::collection($achievements));
    }


    public function get_all($id = null)
    {

        if (isset($id) && ($id != 4)) {
            $achievements = Achievement::where('id', $id)->with([
                'levels' => function ($query) {
                    $query->withCount([
                        'achievementUsers as enable' => function ($query) {
                            $query->where('user_id', auth()->id())
                                ->where('is_enable', true);
                        }
                    ]);
                },
            ])->get();

            return Common::apiResponse(1, 'successfully', AchievementOneLevelsResource::collection($achievements));
        }

        if (isset($id) && ($id == 4)) {
            $weeklyStar = WeeklyStar::currentEvent()
                ->select('*')
                ->with(['rewards' => function ($query) {
                    $query->where('type', 'achievement');
                }])
                ->distinct()
                ->get();
            $pkEvent = PkEvent::currentEvent()->with(['rewards' => function ($query) {
                $query->where('type', 'achievement');
            }])->first();

            $chargeEvent = ChargeTargetEvent::query()->with(['rewards' => function ($query) {
                $query->where('type', 'achievement');
            }])->get();


            $append = [
                "achievement_id" => 1,
                "gift_id" => null,
                "created_at" => "2023-12-21T13:11:38.000000Z",
                "updated_at" => "2023-12-21T13:11:38.000000Z",
                "deleted_at" => null,
                "enable" => 1,
                "description" => null,
                'id' => 0,
                'type' => '',
                'invalid_image' => '',
                'target_type' => '',
            ];
            $pkArray = ($pkEvent?->rewards->map(fn($e) =>
            /** @var PkReward $e*/
            collect(
                ['image' => $e->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '', 'name' => 'event' . '_' . $e->target, 'target' => __($e->pk_type) . ' top ' . $e->level,]
            )->merge($append))->toArray()) ?? [];
            $chargeArray = $chargeEvent?->pluck('rewards')->flatten()->map(function ($e) use ($append) {
                /** @var RewardTarget $e */
                return collect(['image' => @$e->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '', 'name' => 'event' . '_' . $e->getAttribute('target'), 'target' => __('target-events')])->merge($append);
            })->toArray() ?? [];
            $weeklyArray = $weeklyStar?->pluck('rewards')->flatten()->map(fn($e) =>
            /** @var Reward $e*/
            collect(['image' => $e->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '', 'name' => 'event' . '_' . $e->target, 'target' => __('weekly Star') . ' top ' . $e->level])->merge($append))->toArray() ?? [];


            $list = array_merge($weeklyArray, $pkArray, $chargeArray);

            $data = [[
                'levels' => $list,
                "id" => 1,
                "type" => "recharge_target",
                "valid_image" => "/test",
                "invalid_image" => "/test2",
                "target" => null,
                "target_type" => null,
            ]];

                /*['weekly_star' => $weeklyStar,
                  'pk_event' => $pkEvent,
                  'charge_event' => $chargeEvent,

                ]*/;
            return Common::apiResponse(1, 'successfully', $data);
        }
        $user = Auth::user();
        $achievements = Achievement::whereHas("userAchievementLevel", function ($q) use ($user) {
            $q->where("is_enable", 1)->where("user_id", $user->id);
        })->get();
        return Common::apiResponse(1, 'successfully', AchievementResource::collection($achievements));
    }

    // public function get_details($id = null)
    // {
    //     $user=Auth::user();
    //     if(isset($id)){
    //         $achievements = Achievement::whereHas("userAchievments",function($q) use ($id){
    //             $q->where("user_id",$id);
    //         })-> with([
    //             'levels' => function ($query) {
    //                 $query->withCount([
    //                     'achievementUsers as enable' => function ($query) {
    //                         $query->where('user_id', auth()->id())
    //                               ->where('is_enable', true);
    //                     }
    //                 ]);
    //             },
    //         ])->get();


    //         return Common::apiResponse(1, 'successfully', AchievementOneLevelsResource::collection($achievements));
    //     }

    //     $achievements = Achievement::whereHas("userAchievments",function($q) use ($user){
    //         $q->where("user_id",$user->id);
    //     })-> get();
    //     return Common::apiResponse(1, 'successfully', AchievementDetailResource::collection($achievements));

    // }

    public function get_details($id = null)
    {
        $user = Auth::user();
        $userId = $id ?? $user->id;

        $achievementsQuery = UserAchievementLevel::with(['customAchievement.images', "achievementLevel"]);

        $achievementsQuery->where("user_id", $userId)->where(function ($query) {
            $query->whereNull('end_at')
                ->orWhere('end_at', '>', now());
        });

        if (request('type')) {
            $achievementsQuery->where(function ($outerQuery) {
                $outerQuery->whereHas('achievementLevel', function ($query) {
                    $types = request('type') == 1 ? ['recharge_target', 'gift_target'] : ['room_target'];

                    $query->whereHas('achievement', function ($q) use ($types) {
                        $q->whereIn('type', $types);
                    })->orWhereDoesntHave('achievement');
                });
                if (request('type') == 1) {
                    $outerQuery->orWhereDoesntHave('achievementLevel');
                }
            });
        }
        $achievements = $achievementsQuery->get();
        return Common::apiResponse(1, 'successfully', AchievementDetailResource::collection($achievements));
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

    public function achievement_valid_images()
    {
        $data = AchievementValidImage::all();
        return Common::apiResponse(1, 'successfully', AchievementValidImagesResource::collection($data));
    }
}
