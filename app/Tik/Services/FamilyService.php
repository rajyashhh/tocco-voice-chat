<?php

namespace App\Tik\Services;

use App\Enums\UserCoinLogType;
use App\Facades\CustomNotification;
use App\Helpers\UserCoinLogHelper;
use App\Models\User;
use App\Helpers\Common;
use Carbon\CarbonInterface;
use App\Tik\Repositories\RoomRepository;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\FamilyRepository;
use App\Http\Resources\Api\V2\FamilyResource;
use App\Tik\Repositories\FamilyRankRepository;
use App\Tik\Repositories\FamilyUserRepository;
use Modules\Milestones\Helpers\MilestoneHelper;

class FamilyService
{

    public function __construct(
        private readonly FamilyRepository $familyRepository,
        private readonly FamilyUserRepository $familyUserRepository,
        private readonly UserRepository $userRepository,
        private readonly FamilyRankRepository $familyRankRepository,
        private readonly RoomRepository $roomRepository
    ) {}


    public function getWithSearch($search = null): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $data = $this->familyRepository->getWithSearch($search);
        return FamilyResource::collection($data);
    }

    public function show($id)
    {
        return $this->familyRepository->findById($id);
    }

    public function create(User $user, $request, $price)
    {
        $family = $this->familyRepository->findByUserId($user->id);
        if ($family) throw new \Exception('already have family');

        $img = null;
        if ($request->hasFile('image'))  $img = Common::upload('families', $request->file('image'));


        // DB::beginTransaction();
        $familyData = [
            'name' => $request->name,
            'introduce' => $request->introduce,
            'notice' =>  $request->notice,
            'user_id' => $user->id,
            'num' => 20,
            'image' =>  $img ?: '',
            'is_success' => 1,
            'country_id' => $user->country_id,
        ];
        $family =  $this->familyRepository->create($familyData);

        // Server-side letter avatar: families always have a real stored image.
        if (empty($family->image)) {
            $generated = app(\App\Services\LetterAvatarService::class)
                ->generate('families', $family->name, $family->id);
            if ($generated) {
                $family->update(['image' => $generated]);
            }
        }

        $familyUserData = [
            'user_id' => $user->id,
            'family_id' => $family->id,
            'user_type' => 2,
            'status' => 1,
        ];
        $this->familyUserRepository->create($familyUserData);

        $logAmount = -abs($price);
        $amountBefore =  $user->di;
        UserCoinLogHelper::logByType(
            $user->id,
            $logAmount,
            $amountBefore,
            UserCoinLogType::FAMILY,
            $request->name
        );
        $this->userRepository->decrementCoins($user->id, $price);
        $this->userRepository->updateFamilyId($user, $family->id);
        MilestoneHelper::grantMilestoneToUser($user, 'family-owner');

        return $family;
    }

    public function ranking($time)
    {

        switch ($time) {
            case 'today':
                $query = ['day' => now()->day, 'month' => now()->month, 'year' => now()->year,];
                break;
            case 'month':
                $query = ['month' => now()->month, 'year' => now()->year,];
                break;
            case 'week':
                $query = ['created_at' => [today()->startOfWeek(), today()->endOfWeek()]];
                break;
            default:
                throw new \Exception('time not define');
        }
        if ($time == 'today' || $time == 'month') {
            $rank = $this->familyRankRepository->ranking(condition: $query, paginate: 10);
        } else {
            $rank = $this->familyRankRepository->ranking(whereBetween: $query, paginate: 10);
        }

        return $rank;
    }

    public function userRank()
    {
        $todayCondition = ['day' => now()->day, 'month' => now()->month, 'year' => now()->year,];
        $monthCondition = ['month' => now()->month, 'year' => now()->year,];
        $weeklyCondition = ['created_at' => [today()->startOfWeek(CarbonInterface::SATURDAY), today()->endOfWeek(CarbonInterface::FRIDAY)]];

        $today = $this->familyRankRepository->ranking(condition: $todayCondition);
        $month = $this->familyRankRepository->ranking(condition: $monthCondition);
        $week = $this->familyRankRepository->ranking(whereBetween: $weeklyCondition);

        return [$today, $month, $week];
    }

    public function update($userId, $request, $familyId)
    {
        $family = $this->familyRepository->findById($familyId);
        $is_admin = $this->familyUserRepository->checkIsAdmin($familyId, $userId);
        if (($userId != $family->user_id) && !$is_admin) throw new \Exception('not allowed');
        if (!$family) throw new \Exception('not found');
        if ($request->name) {
            $family->name = $request->name;
        }
        if ($request->introduce) {
            $family->introduce = $request->introduce;
        }
        if ($request->notice) {
            $family->notice = $request->notice;
        }
        if ($request->hasFile('image')) {
            $family->image = Common::upload('families', $request->file('image'));
        }
        $family->save();
        return $family;
    }

    public function join($user, $familyId)
    {

        $family = $this->familyRepository->findById($familyId);


        $familyUser = $this->familyRepository->findByUserId($user->id);

        if ($familyUser)  throw new \Exception(__('already have one'));
        if (!$family)   throw new \Exception(__('family not found'));

        if ($family->id == $user->family_id) throw new \Exception(__('already joined'));

        if ($family->members_num >= $family->num) throw new \Exception(__('family is full members'));
        $UserFamilyMember = $this->familyUserRepository->checkFamilyMember($user->id, $familyId);
        if ($UserFamilyMember) {
            $user->family_id = $family->id;
            $user->save();
            throw new \Exception(__('already joined'));
        }
        $userSentRequest = $this->familyUserRepository->checkSendJoinRequest($user->id, $familyId);
        if ($userSentRequest)  throw new \Exception(__('you_alredy_have_sent'));


        $data = [
            'user_id' => $user->id,
            'family_id' => $family->id,
            'user_type' => 0,
            'status' => 0,
        ];
        $this->familyUserRepository->create($data);
        return $family;
    }

    public function delete($user, $familyId)
    {
        $family = $this->familyRepository->findById($familyId);
        if (!$family) throw new \Exception('not found');
        if ($user->id != $family->user_id) return Common::apiResponse(0, 'not allowed', null, 403);
        $this->familyUserRepository->delete($familyId);
        $this->userRepository->updateFamilyId($user, null);
        $this->userRepository->updateUsersFamily($familyId, null);
        $family->delete();
        return true;
    }

    public function removeUserFromFamily($userId, $familyId, $authId)
    {
        $family =   $this->familyRepository->findById($familyId);
        $isAdmin = $this->familyUserRepository->checkIsAdmin($familyId, $userId);


        if ($authId == $userId) throw new \Exception('try to remove your self');
        $user =  $this->userRepository->findById($userId);
        if (!$family || !$user) throw new \Exception('not found');

        if (!$isAdmin && ($family->user_id !=  $authId)) throw new \Exception('not allowed');
        $this->userRepository->update(['family_id' => null], $user->id);
        $this->familyUserRepository->deleteUserFromFamily($userId, $family->id);

        return [$family, $user];
    }

    public function requestList($userId)
    {
        $family = $this->familyRepository->findByUserId($userId);
        if (!$family) {
            $userFamily = $this->familyUserRepository->admin($userId);

            if ($userFamily) {
                $family = $this->familyRepository->findById($userFamily->family_id);
            }
        }
        if (!$family) throw new \Exception('not found');

        return $this->familyUserRepository->requestUsersList($userId, $family->id);
    }

    public function actionRequest($request, $auth)
    {
        $requestUser = $this->familyUserRepository->findById($request->req_id);

        if (!$requestUser) throw new \Exception('not found');
        $user = $this->userRepository->findById($requestUser->user_id);
        if ($request->status == 1) {
            $other = $this->familyUserRepository->findByUserId($requestUser->user_id);
            if (!$user) throw new \Exception('user not found');
            if ($other && $user->family_id != null)  throw new \Exception('user already joined to other family');

            $familyUser = $this->familyRepository->findByUserId($requestUser->user_id);
            if ($familyUser)  throw new \Exception(__('already have one'));

            $family = $this->familyRepository->findById($requestUser->family_id);
            if (!$family) throw new \Exception(__('not found'));
            if ($request->status == 1 && $family->members_num >= $family->num) throw new \Exception(__('family is full members'));
            $admin = $this->familyUserRepository->checkIsAdmin($family->id, $auth->id);
            if ($family->user_id != $auth->id  && !$admin) throw new \Exception(__('you do not have permeation to take action'));

            $this->familyUserRepository->update(['status' => $request->status], $requestUser->id);

            $this->userRepository->update(['family_id' => $family->id], $user->id);
            $this->familyUserRepository->deleteOldRequest($requestUser->user_id, $requestUser->id);

            if ($user && ($request->status == 1))  CustomNotification::acceptUserFamily($family, $user);
        } elseif ($request->status == 2) {
            $this->familyUserRepository->deleteRefusedRequest($requestUser->id);
        }

        return $user;
    }


    public function familyUserType($request)
    {
        $family = $this->familyRepository->findById($request->family_id);
        if (!$family) throw new \Exception(__('not found'));
        if ($request->type == 1 && $family->admins_num >= $family->num_admins) throw new \Exception('full admins');
        if ($request->type == 0 && $family->members_num >= $family->num) throw new \Exception('full members');
        $familyUser = $this->familyUserRepository->getFamilyUser($request->user_id, $request->family_id);

        if (!$familyUser) throw new \Exception('not found');
        $familyUser->user_type = $request->type;
        $familyUser->save();
        $user = $this->userRepository->findById($request->user_id);
        return [$family, $user];
    }


    public function memberList($familyId)
    {
        $family =  $this->familyRepository->findById($familyId);

        if (!$family) throw new \Exception('not found');
        $owner = $this->userRepository->findById($family->user_id);

        $adminIds = $this->familyUserRepository->adminIds($family->id);
        $memberIds = $this->familyUserRepository->memberIds($family->id);

        $admins = $this->userRepository->getUsers($adminIds);

        $members = $this->userRepository->getUsersWithPaginate($memberIds, 20);
        return [$owner, $admins, $members];
    }

    public function familyRooms($familyId)
    {
        $family = $this->familyRepository->findById($familyId);

        if (!$family) throw new \Exception('not found');
        $memberIds = $this->familyUserRepository->familyMemberIds($family->id);

        // $rooms = $this->roomRepository->getRooms($memberIds);
        $rooms = $this->roomRepository->all(request(), $memberIds);
        return $rooms;
    }

    public function exitMember(User $user)
    {
        $this->familyUserRepository->exitUser($user->id);
        $this->userRepository->updateFamilyId($user, 0);
    }
}
