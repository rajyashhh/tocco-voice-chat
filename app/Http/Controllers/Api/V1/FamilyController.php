<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\Room;
use App\Models\User;
use App\Models\Family;
use App\Helpers\Common;
use App\Models\FamilyRank;
use App\Models\FamilyUser;
use App\Tik\Services\FamilyService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use App\Models\configesModel;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Api\V1\RoomResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\Api\V1\FamilyResource;
use App\Http\Resources\Api\V1\FamilyRankResource;
use App\Http\Resources\Api\V1\FamilyUserResource;
use App\Http\Resources\Api\V1\MembersUserResource;
use App\Http\Resources\Api\V1\NewFamilyUserResource;

class FamilyController extends Controller
{

    public function __construct(private FamilyService $familyServices)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $data = $this->familyServices->getWithSearch($search);
        return Common::apiResponse(1, '', $data);
    }

    public function topUserRanking(Request $request)
    {
        [$today, $week, $month] = $this->familyServices->userRank();

        $data = [
            'today' => FamilyRankResource::collection($today),
            'week' => FamilyRankResource::collection($week),
            'month' => FamilyRankResource::collection($month),
        ];
        return Common::apiResponse(true, 'success', $data);
    }
    public function ranking(Request $request)
    {
        $time = $request->time;
        if (!$time) return Common::apiResponse(0, 'time is required', null, 422);
        try {
            $data =  $this->familyServices->ranking($time);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }


        $data = FamilyRankResource::collection($data);
        return Common::apiResponse(1, '',$data);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $family_price = Common::getConfig('family_price');
        if (!isset($family_price)) {
            return Common::apiResponse(0, 'Not available now', null, 407);
        }

        if ($user->di < @$family_price) {
            return Common::apiResponse(0, 'Insufficient balance, please go to recharge!', null, 407);
        }


        try {
            $family =   $this->familyServices->create($user, $request, $family_price);


        } catch (\Exception $e) {

            return Common::apiResponse(0, $e != null ? $e->getMessage() : 'missing params', 422);
        }
        CustomNotification::family($family, $user);

        return Common::apiResponse(1, 'created', ['id' => $family->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $family = $this->familyServices->show($id);
        if (!$family) return Common::apiResponse(0, 'not found', null, 404);
        return Common::apiResponse(1, '', new FamilyResource($family));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */

    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;
        try {
            $family = $this->familyServices->update($userId, $request, $id);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }

        return Common::apiResponse(1, '', new FamilyResource($family));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        DB::beginTransaction();
        try {

            $this->familyServices->delete($user, $id);
            DB::commit();

            return Common::apiResponse(1, 'success', null, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage() ?? 'failed', null, 400);
        }
    }

    public function join(Request $request)
    {
        $user = $request->user();
        $familyId = $request->family_id;
        try {
            $family = $this->familyServices->join($user, $familyId);

            CustomNotification::requestJoinFamily($family, $user);

            return Common::apiResponse(1, __('joinSacses') . ' ' . $family->name);
        } catch (\Exception $e) {
            DB::rollBack();
            return Common::apiResponse(0, $e->getMessage() ?? __('failed'), null, 400);
        }
    }

    public function req_list(Request $request)
    {
        $userId = $request->user()->id;

        try {
            $list = $this->familyServices->requestList($userId);
        } catch (\Exception $e) {
            DB::rollBack();
            return Common::apiResponse(0, $e->getMessage() ?? __('failed'), null, 400);
        }
        $data = NewFamilyUserResource::collection($list);
        return Common::apiResponse(1, '', $data, 200);
    }

    public function RequestFamilyAction(Request $request)
    {
        $auth = Auth::user();

        DB::beginTransaction();
        try {

             $user = $this->familyServices->actionRequest($request, $auth);
             DB::commit();

            if ($request->status == 1){
                request()->family_status = 0;
                $resource = new MembersUserResource($user);
            }

            return Common::apiResponse(1, 'success', @$resource ?? null, 200);
        } catch (\Exception $exception) {

            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage()??'failed', null, 400);
        }
    }

    public function changeFamilyUserType(Request $request)
    {
        if ($request->type == null || !$request->user_id || !$request->family_id || !in_array($request->type, [0, 1])) {
            return Common::apiResponse(0, __('invalid data'), null, 422);
        }
        try {
            [$family, $user] = $this->familyServices->familyUserType($request);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        if ($request->type == 1) {
            CustomNotification::adminFamily($family, $user);
        }

        return Common::apiResponse(1, 'success', [], 200);
    }

    public function removeUser(Request $request)
    {
        if (!$request->family_id || !$request->user_id) return Common::apiResponse(0, 'missing params', null, 422);
        $authId = $request->user()->id;
        $userId = $request->user_id;

        try {

            [$family, $user] = $this->familyServices->removeUserFromFamily($userId, $request->family_id, $authId);

            CustomNotification::removeFamilyUser($family, $user);
            return Common::apiResponse(1, 'success', new FamilyResource(Family::find($family->id)), 200);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage() ?? 'failed', null, 400);
        }
    }

    public function getMembersList(Request $request)
    {
        // Validate family_id
        if (!$request->family_id) {
            return Common::apiResponse(0, 'missing params', null, 422);
        }

        try {
            // Fetch owner, admins, and members
            [$owner, $admins, $members] = $this->familyServices->memberList($request->family_id);
        } catch (\Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

        // Set family_status for owner
        request()->family_status = 2;
        $owner->family_id = $request->family_id;
        $membersUserResource = (new MembersUserResource($owner, $request->family_id))->toJson();

        // Set family_status for admins
        request()->family_status = 1;
        $anonymousResourceCollection = MembersUserResource::collection($admins)->additional([
            'family_id' => $request->family_id, // Pass family_id to the collection
        ])->toJson();

        // Set family_status for members
        request()->family_status = 0;
        $anonymousResourceCollection1 = MembersUserResource::collection($members)->additional([
            'family_id' => $request->family_id, // Pass family_id to the collection
        ])->toJson();

        // Prepare the response data
        $data = [
            'owner' => json_decode($membersUserResource),
            'admins' => json_decode($anonymousResourceCollection),
            'members' => json_decode($anonymousResourceCollection1),
        ];

        return Common::apiResponse(1, '', $data, 200);
    }

    public function getFamilyRooms(Request $request)
    {
        if (!$request->family_id) return Common::apiResponse(0, 'missing params', null, 422);
        try {
            $rooms = $this->familyServices->familyRooms($request->family_id);
        } catch (\Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, '', RoomResource::collection($rooms), 200);
    }

    public function exitFamily(Request $request)
    {
        $user = $request->user();
        $this->familyServices->exitMember($user);
        return Common::apiResponse(1, 'done', null, 201);
    }

    /**
     * @param mixed $familyId
     * @return void
     */
    private function updateUsersFamily(mixed $familyId): void
    {
        DB::statement("
            UPDATE users
            SET family_id = 0
            where family_id = :family_id
        ", ['family_id' => $familyId]);
    }
}
