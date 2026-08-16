<?php

namespace App\Tik\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Admin;
use App\Models\Agency;
use App\Helpers\Common;
use App\Models\LiveTime;
use App\Models\UserSallary;
use App\Helpers\UserCommon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use App\Facades\UserHandling;
use App\Models\AgencyJoinRequest;
use App\Models\UsersJoinedAgency;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use App\Notifications\AcceptAgency;
use App\Notifications\RefuseAgency;
use http\Exception\RuntimeException;
use Illuminate\Support\Facades\Hash;
use App\Models\MonthlyDiamondReceive;
use App\Notifications\AgencyOwnerRole;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\CValidationException;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\AdminRepository;
use App\Tik\Repositories\AgencyRepository;
use App\Tik\Repositories\FollowRepository;
use App\Tik\Repositories\TargetRepository;
use Modules\Milestones\Entities\Milestone;
use App\Tik\Repositories\GiftLogRepository;
use App\Tik\Repositories\HistoryRepository;
use App\Tik\Repositories\LiveTimeRepository;
use Illuminate\Support\Facades\Notification;
use Modules\Reals\Http\Services\RealsService;
use App\Tik\Repositories\UserSalaryRepository;
use Modules\Milestones\Helpers\MilestoneHelper;
use App\Tik\Repositories\AgencySalaryRepository;
use App\Tik\Repositories\AgencyUserJobRepository;
use App\Tik\Repositories\AdditionalInfoRepository;
use App\Tik\Repositories\ProfileVisitorRepository;
use App\Tik\Repositories\ShippingAgencyRepository;
use App\Tik\Repositories\AgencyJoinRequestRepository;
use App\Tik\Repositories\UsersJoinedAgencyRepository;
use App\Tik\Repositories\LeaveAgencyRequestRepository;
use Modules\AgencyApp\Transformers\AgencyHostResource;
use App\Http\Resources\Api\V1\AgancyCurantMonthResource;
use App\Http\Resources\Api\V1\AgencyUsersTargetResource;
use App\Http\Resources\Api\V1\MyDataForAgencyNewResource;
use Modules\AgencyApp\Transformers\AgencyMonthlyHostResource;



class AgencyService
{
    public function __construct(
        private readonly AgencyRepository $agencyRepository,
        private readonly ShippingAgencyRepository $shippingAgencyRepository,
        private readonly AgencyJoinRequestRepository $agencyJoinRequestRepository,
        private readonly AgencyUserJobRepository $agencyUserJobRepository,
        private readonly UserRepository $userRepository,
        private readonly AgencySalaryRepository $agencySalaryRepository,
        private readonly UserSalaryRepository $userSalaryRepository,
        private readonly TargetRepository $targetRepository,
        private readonly HistoryRepository $historyRepository,
        private readonly AdditionalInfoRepository $additionalInfoRepository,
        private readonly LiveTimeRepository $liveTimeRepository,
        private readonly GiftLogRepository $giftLogRepository,
        private readonly ProfileVisitorRepository $profileVisitorRepository,
        private readonly FollowRepository $followRepository,
        private readonly LeaveAgencyRequestRepository $leaveAgencyRequestRepository,
        private readonly AdminRepository $adminRepository,
        private readonly UsersJoinedAgencyRepository $usersJoinedAgencyRepository,


    ) {}


    public function joinAgency($user, $request)
    {
        $agencyId = $request->agency_id;
        $agency = $this->agencyRepository->findById($agencyId);
        if (!$agency) throw new Exception(__('api_responses.agency'));
        if ($agency->status == 0) throw new \Exception(__('api_responses.agencyDown'));
        if ($agency->type == 2) throw new \Exception(__('api_responses.shippingAgency'));

        $joined = $user->agency_id;
        if ($joined) throw new \Exception(__('api_responses.you_are_already_under_agency'));
//        $countRequest = $this->agencyJoinRequestRepository->countByMonth($user->id);
        // if ($countRequest > 5)   throw new \Exception(__('api_responses.you_have_+5_requests_not_allowed_to_request_other_more'));
        $agency_request = $this->agencyJoinRequestRepository->countByAgency($user->id, $agencyId);
        if ($agency_request > 0)  throw new \Exception(__('api_responses.you_already_send_request_to_this_agency'));

        $data = [
            'user_id' => $user->id,
            'agency_id' => $agencyId,
            'whatsapp' => $request->whatsapp,

        ];
        $this->agencyJoinRequestRepository->create($data);

        $requests = $this->agencyJoinRequestRepository->getByUser($user->id);
        CustomNotification::agencyJoinRequest($agency, $user);

        return $requests;
    }

    public function find($agencyId)
    {
        $agency = $this->agencyRepository->findById($agencyId);
        if (!$agency) throw new Exception(__('api_responses.agency'));
        return $agency;
    }

    public function gitOldAgencies($userId)
    {
        $user = $this->agencyRepository->gitOldAgencies($userId);
        return $user;
    }



    public function agencyTarget($userId, $user, $request)
    {
        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;
        $target = $this->userSalaryRepository->newUserSalary($user->id, $month, $year);
        $minValue = $this->targetRepository->getByUsd($target);
        $result = (@$minValue->agency_share / 100) * @$target;
        $usersTargetDetails = $this->userRepository->agencyUsers($userId, $month, $year, 10, $request->page);

        $hours = LiveTime::query()
            ->selectRaw('sum(hours) as hours, max(created_at) as date')
            ->where('uid', $user->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupBy(\DB::raw('date(created_at)'))
            ->limit(31)->sum('hours');
        $minutes = $hours * 60;
        $hasColor = Common::hasInPack(@$user->id, 18, true);


        return [
            'success' => true,
            'message' => 'successfully',
            'data' => [
                'user' => [
                    'days'    => $user->getTotalDays(),
                    'type'    => $user->type_user,
                    'minutes' => $minutes,
                    'image_color'          => @$user->color_image,
                    'id_image'             => @$user->specialId?->ware?->show_img ?? '',
                    'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? Common::wareUserVip(@$user->id, 18, 'color') : null),
                ],

                'target' => floor($target),
                'rate_percentage' => $result,
                'users_target' => AgencyUsersTargetResource::collection($usersTargetDetails),
            ],
            'status' => 200,
        ];
    }


    public function stars($agencyId, $request)
    {
        $timezone = getTimezone();
        $year = $request->year ?? Carbon::now($timezone)->year;
        $month = $request->month ?? Carbon::now($timezone)->month;

        $start = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth()->utc();
        $end = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->endOfMonth()->utc();

        return $this->giftLogRepository->getByAgency('receiver', $start, $end, $agencyId, 'receiver_id', 10, $request->page);
    }

    public function heroes($agencyId, $request)
    {
        $timezone = getTimezone();
        $year = $request->year ?? Carbon::now($timezone)->year;
        $month = $request->month ?? Carbon::now($timezone)->month;

        $start = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->startOfMonth()->utc();
        $end = Carbon::create($year, $month, 1, 0, 0, 0, $timezone)->endOfMonth()->utc();

        return $this->giftLogRepository->getByAgency('sender', $start, $end, $agencyId, 'sender_id', 10, $request->page);
    }

    public function agencyMembers($agencyId, $page = 1, $perPage = 20)
    {
        $agency = $this->agencyRepository->findById($agencyId);
        if (!$agency) throw new Exception(__('api_responses.agency'));
        return $this->agencyRepository->members($agency, $perPage, $page);
    }

    public function showRequests($userId)
    {
        $admin = $this->agencyUserJobRepository->findByUserId($userId);
        if ($admin) {
            $agency = $this->agencyRepository->findById($admin->agency_id);
        } else {
            $agency = $this->agencyRepository->findAgencyByOwnerId($userId);
        }
        if (!$agency) throw new Exception('u_not_have_agncy');
        $requestList = $this->agencyJoinRequestRepository->getByAgencyId($agency->id);

        return $requestList;
    }

    public function requestAction($owner, $request)
    {
        $accept    = $request->accept;
        $user = $this->userRepository->findById($request->user_id);
        if (!$user) throw new Exception('user not found');

        $admin = $this->agencyUserJobRepository->findByUserId($owner->id);
        if ($admin) {
            $agency = $this->agencyRepository->findById($admin->agency_id);
        } else {
            $agency = $this->agencyRepository->findByOwner($owner->id);
        }
        if (!$agency) throw new Exception('u_not_owner_agncy');
        if ($agency->type == 2) throw new \Exception(__('api_responses.shippingAgency'));

        if ($user->agency_id) throw new Exception(__('user joined agency before'));

        $action = $this->agencyJoinRequestRepository->findRequest($user->id, $agency->id);


        if (!$action) throw new Exception('Request not found');

        if ($accept === 0 || $accept === false) {
            $action->status = 2;
            $action->save();
            CustomNotification::rejectAgency($agency, $user);
        } elseif ($accept === 1 || $accept === true) {
            $action->status = 1;
            $action->save();
            $this->userRepository->update(['agency_id' => $agency->id, 'type_user' => 1], $user->id);
            $userMonthlyDiamond =  MonthlyDiamondReceive::where('user_id', $user->id)->where('month', now()->month)->where('year', now()->year)->first();
            if ($userMonthlyDiamond) $userMonthlyDiamond->update(['monthly_diamond_received' => 0]);
            $this->userRepository->updateTypeUser($user);
            // $checkAgencyUser = $this->usersJoinedAgencyRepository->exist($user->id, $agency->id);
            // if (!$checkAgencyUser) {
            $joinAgencyData = [
                'user_id' =>  $user->id,
                'agency_id' => $agency->id,
                'type' => 2,
                'join_date' => now(),
                'status' => 'Joined'
            ];
            $this->usersJoinedAgencyRepository->create($joinAgencyData);
            // }
            // add vip to user
            // UserCommon::userVip($user,'request-action-agency');
            CustomNotification::acceptAgencyApp($agency, $user);
            MilestoneHelper::grantMilestoneToUser($user, 'host');
        }
        return true;
    }

    public function listOption($agencyId)
    {
        $agency = $this->agencyRepository->getWithSelectMonthAndYear($agencyId);

        $createdAt   = $agency->created_at;
        $currentDate = now();

        $monthsToInclude = [];
        while ($createdAt <= $currentDate) {
            $month = $createdAt->format('m');
            $year  = $createdAt->format('Y');

            // Only add if the month and year are not the same as the current date
            if ($month !== $currentDate->format('m') || $year !== $currentDate->format('Y')) {
                $monthsToInclude[] = [
                    'month' => $month,
                    'year' => $year,
                ];
            }
            // Clone the date object before adding the month
            $createdAt = clone $createdAt;
            $createdAt->addMonth(); // Move to the next month
        }

        // Add the current month and year
        $monthsToInclude[] = [
            'month' => $currentDate->format('m'),
            'year' => $currentDate->format('Y'),
        ];

        return $monthsToInclude;
    }


    public function historySearch($agencyId, $request)
    {
        $month        = $request->month;
        $year         = $request->year;
        $CurrentMonth = date('m'); // Get the current month as a two-digit number (e.g., 08 for August)
        $CurrentYear  = date('Y');
        if ($CurrentYear == $year && $CurrentMonth == $month) {
            $perPage = 15;                 // Number of items per page
            $page    = request('page', 1); // Get the current page number from the request, default to 1
            $dataQuery = $this->userRepository->findUsersByAgencyIdI($agencyId);
            $paginatedData = $dataQuery->paginate($perPage, ['*'], 'page', $page);
            $data =  $dataQuery->with('userSallary')->get();
            $totalDiamond = $data->sum('monthly_diamond_received');
        } else {
            $data = $this->historyRepository->getByMonthAndYear($agencyId, $month, $year);
            $totalDiamond = $data->sum('diamond');
            $perPage = 15;                 // Number of items per page
            $page    = request('page', 1); // Get the current page number from the request, default to 1
            $paginatedData   = $data->paginate($perPage, ['*'], 'page', $page);
        }
        $transformedData = AgancyCurantMonthResource::collection($paginatedData);

        $target   = $this->userSalaryRepository->getSumByMonthAndYear($agencyId, $month, $year, 'agency_sallary');

        $minValue = $this->targetRepository->getByUsd($target);


        $totalusd = $this->userSalaryRepository->getSumByMonthAndYear($agencyId, $month, $year, 'sallary');
        $total    = (@$minValue->agency_share / 100) * $totalusd;

        $agencySalary = $this->agencySalaryRepository->findByMonthAndYear($agencyId, $month, $year);
        $total = $agencySalary?->sallary ?? 0;
        $responseData = [
            'sum'             => $totalDiamond ?: 0,
            'sum_usd' => $totalusd ?: 0, // 'owner_usd' => $curant?:0,
            'Total_owner_usd' => $total ?: 0,
            'users' => $transformedData ?: 0,

        ];
        return $responseData;
    }

    public function update($userId, $agencyId, $request)
    {

        $agency = $this->agencyRepository->findById($agencyId);
        if (!$agency) throw new Exception(__('api_responses.agency'));

        if ($agency->app_owner_id != $userId)  throw new Exception(__('api_responses.agency_app_owner'));


        if ($request->name != null) {
            $agency->name = $request->name;
        }

        if ($request->contents != null) {
            $agency->contents = $request->contents;
        }

        if ($request->get('content') != null) {
            $agency->notice = $request->get('content');
        }

        if ($request->hasFile('img')) {
            if ($agency->img && Storage::exists($agency->img)) {
                Storage::delete($agency->img);
            }

            $img = $request->file('img');
            $image = Common::upload('agency', $img);
            $agency->img = $image;
        }

        $agency->save();

        return $agency;
    }

    public function userHandlingRequest($userId, $agencyId, $type = null)
    {
        $operator = $this->userRepository->findById($userId);
        if (!$operator) {
            throw new CValidationException(__('User not found'));
        }
        // if ($agencyId != $operator->agency_id) throw new CValidationException('يجب ان يكون المستخدم في الوكاله!');

        if (!empty($type) && $type == 'remove') {
            $this->agencyUserJobRepository->deleteAdmin($operator->id, $agencyId);
            $tokens_notfacion[] = $operator->notification_id;
            $title = $operator->name;
            $body = 'تم ازالتك من مشرفين الوكالة';
            $type = $message->type ?? 'text';
            CustomNotification::agencyRemoveAdmin($agencyId, $operator);
            return 'تم ازالة  المستخدم بنجاح';
        }

        if ($this->agencyUserJobRepository->exists($userId, $agencyId)) {
            throw new CValidationException(__('This user already has an agency job requested!'));
        }

        $data = [
            'agency_id' => $agencyId,
            'user_id' => $operator->id,
            'type' => "requestManger",
        ];
        $this->agencyUserJobRepository->create($data);
        CustomNotification::agencyAddAdmin($agencyId, $operator);

        return 'تم اضافه المستخدم بنجاح';
    }

    public function RuserHandlingRequest($userId, $agencyId)
    {
        $operator = $this->userRepository->findById($userId);

        if ($agencyId != $operator->agency_id) throw new CValidationException('يجب ان يكون المستخدم في الوكاله!');

        if ($this->agencyUserJobRepository->exists($userId, $agencyId)) {
            throw new CValidationException(__('This user already has an agency job requested!'));
        }

        $data = [
            'agency_id' => $agencyId,
            'user_id' => $operator->id,
            'type' => "requestManger",
        ];
        $this->agencyUserJobRepository->create($data);
        return true;
    }


    public function create($userId, $request)
    {
        $checkAgency = $this->agencyRepository->findAgencyByOwnerId($userId, 0);
        if ($checkAgency)   throw new Exception('لقد قمت بتقديم طلب من قبل ولم يتم اتخاذ اي اجراء فيه!');

        $checkUserAgency =  $this->agencyRepository->findAgencyByOwnerId($userId, 1);

        if ($checkUserAgency) throw new Exception('انت تملك وكاله بالفعل');

        if ($request->hasFile('img')) {
            $img = $request->file('img');
            $image = Common::upload('agency', $img);
        }

        $data = [
            'app_owner_id' => $request->user()->id,
            'name' => $request->input('name'),
            'notice' => $request->input('notice'),
            'status' => 0,
            'phone' => $request->input('phone'),
            'img' => $image ?? null,
            'type' => 1,
        ];
        $agency = $this->agencyRepository->create($data);

        // Server-side letter avatar: host agencies always have a real stored image.
        if (empty($agency->img)) {
            $generated = app(\App\Services\LetterAvatarService::class)
                ->generate('agency', $agency->name, $agency->id);
            if ($generated) {
                $agency->update(['img' => $generated]);
            }
        }

        if ($request->hasFile('face_image')) {
            $img = $request->file('face_image');
            $face_image_nationalId = Common::upload('nationalId', $img);
        }
        if ($request->hasFile('back_image')) {
            $img = $request->file('back_image');
            $back_image_nationalId = Common::upload('nationalId', $img);
        }
        $user = $this->userRepository->searchUser($request->user_id);

        if ($request->hasFile('video')) {
            $data        = $request->file('video');
            $video = RealsService::upload($data);
        }
        $dataInfo = [
            'agency_id' => $agency->id,
            'gmail' => $request->input('email'),
            'status' => 0,
            'face_image_nationalId' =>  $face_image_nationalId ?? '',
            'back_image_nationalId' => $back_image_nationalId ?? '',
            'country' => $request->input('country'),
            'history_app_info' => $request->input('apps'),
            'salary' => $request->input('salary'),
            'host' => $request->input('host'),
            'user_id' => $user->id ?? null,
            'video' => $video ?? null,
            'owner_id' =>  $request->user()->id,
        ];
        $this->additionalInfoRepository->create($dataInfo);
        $agencyWithAdditionalInfo = $this->agencyRepository->findById($agency->id);
        return $agencyWithAdditionalInfo;
    }

    public function actionRequestAgency($request)
    {
        $agency = $this->agencyRepository->findById($request->agency_id);
        if (!$agency)  throw new Exception('agency not found');
        $user = $this->userRepository->findById($agency->app_owner_id);

        if ($request->status != 1) {

            if ($agency->additionalInfo->gmail) {
                Notification::route('mail',  $agency->additionalInfo->gmail)->notify(new RefuseAgency());
            }
            $agency->delete();

            CustomNotification::refuseRequestAgency($user);
            return true;
        }
        $this->agencyRepository->updateStatus($agency, $request->status);

        $additionalInfo = $this->additionalInfoRepository->findByAgencyId($agency->id);
        $additionalInfo->status = $request->status;
        $additionalInfo->save();

        $Host_agency = $agency->Host_agency;
        if ($Host_agency == 1) {
            $user->type_user = 2;
            $user->agency_id = $agency->id;
            $user->is_host = 1;
            $user->save();
            uploadMonthlyDiamondReceive($user->id, 0);
        }
        if ($agency->additionalInfo->gmail) {
            Notification::route('mail',  $agency->additionalInfo->gmail)->notify(new AcceptAgency());
        }
        ///  Common::createUserAdmin($agency->app_owner_id);
        $checkAgencyUser = $this->usersJoinedAgencyRepository->exist($user->id, $agency->id);
        if (!$checkAgencyUser) {
            $joinAgencyData = [
                'user_id' =>  $user->id,
                'agency_id' => $agency->id,
                'type' => 1,
                'join_date' => now(),
            ];
            $this->usersJoinedAgencyRepository->create($joinAgencyData);
        }
        CustomNotification::acceptRequestAgency($user);
        return true;
    }

    public function allRequest()
    {
        return $this->agencyRepository->getByAdditionalInfo();
    }

    public function historyLastThirtyDays($userUUId)
    {
        $user = $this->userRepository->searchUser($userUUId);
        $endDate = now();
        $startDate = now()->subDays(30);
        $data = [];

        $total_days = 0;
        $total_hours = 0;
        $total_diamonds = 0;
        for ($date = $startDate; $date->lessThanOrEqualTo($endDate); $date->addDay()) {
            $day        = $this->liveTimeRepository->sumDays($user->id, $startDate, $endDate, $date->toDateString());
            $hour       = $this->liveTimeRepository->SumHours($user->id, $startDate, $endDate, $date->toDateString());

            $diamond    = $this->giftLogRepository->sumGiftPriceByReceiver($user->id, $startDate, $endDate, $date->toDateString());


            $total_days += $day;
            $total_hours += $hour;
            $total_diamonds += $diamond;
            $data[] = [
                'date'      =>  $day,
                'days'      =>  $day,
                'hours'     =>  $hour,
                'diamonds'  =>  $diamond,
            ];
        }
        $data[] = [
            'total_days'    => $total_days,
            'total_hours'   => $total_hours,
            'total_diamonds' => $total_diamonds,
        ];

        return $data;
    }

    public function agencyReport($agencyId)
    {
        $userIds      = $this->userRepository->getIdsByAgencyId($agencyId);

        [$diamonds, $days, $hours, $visitors, $follows, $friends] = $this->details($userIds);

        return  $data = [
            'diamonds'    =>  $diamonds,
            'hours'       =>  $hours,
            'days'        =>  $days,
            'visitors'    =>  $visitors,
            'friends'     =>  $friends,
            'follows'     =>  $follows,
        ];
    }

    public function details($userIds)
    {
        $diamonds   = $this->giftLogRepository->totalUsersGiftPrice($userIds);
        $days       = $this->liveTimeRepository->totalUsersHoursDays($userIds, 'days');

        $hours       = $this->liveTimeRepository->totalUsersHoursDays($userIds, 'hours');
        $visitors   = $this->profileVisitorRepository->countUsersByYearAbdMonth($userIds);
        $follows    = $this->followRepository->countFollows($userIds);
        $friends    =  $this->followRepository->countFriends($userIds);
        return [$diamonds, $days, $hours, $visitors, $follows, $friends];
    }

    public function leaveAgency($userId, $agency)
    {
        $check = $this->leaveAgencyRequestRepository->getOldRequest($agency->id, $userId);
        if ($check) throw new Exception("هناك طلب من قبل !");
        $data = [
            'agency_id' =>  $agency->id,
            'user_id'   =>  $userId,
            'admin_id'  =>  $agency->owner_id,
            'status'    =>  0,
        ];
        $this->leaveAgencyRequestRepository->create($data);
        $userJoin = $this->usersJoinedAgencyRepository->findByUser($userId, $agency->id);
        if ($userJoin) {
            $userJoin->leave_date = now();
            $userJoin->status = 'leaving agency';
            $userJoin->save();
        } else {
            $joinAgencyData = [
                'user_id' =>  $userId,
                'agency_id' => $agency->id,
                'type' => 2,
                'join_date' => now(),
                'leave_date' => now(),
                'status' => 'leaving agency',
            ];
            $this->usersJoinedAgencyRepository->create($joinAgencyData);
        }
        return true;
    }

    public function handlingRequest($agencyId, $userId)
    {
        $operator = $this->userRepository->findById($userId);

        if ($agencyId != $operator->agency_id) throw new Exception('يجب ان يكون المستخدم في الوكاله!');
        $data = [
            'agency_id' => $agencyId,
            'user_id' => $operator->id,
            'type' => "requestManger",
        ];
        $this->agencyUserJobRepository->create($data);
        return true;
    }

    public function kickAgency($auth, $userId)
    {
        $user_kicked = $this->userRepository->searchUserById($userId);
        if (!$user_kicked) throw new Exception('user not found');
        if ($user_kicked->agency_id != $auth->ownAgency->id || $user_kicked->id == $auth->ownAgency->app_owner_id) throw new Exception('لا يمكنك ازاله هذا المستخدم!');
        UserHandling::kickUserFromAgency($user_kicked, 1);
        $joinedAgency = UsersJoinedAgency::where(['agency_id' =>   $user_kicked->agency_id, 'user_id' => $user_kicked->id])->first();
        if ($joinedAgency) UsersJoinedAgency::where(['agency_id' =>   $user_kicked->agency_id, 'user_id' => $user_kicked->id])->update(['leave_date' => now(), 'status' => 'kicked off']);
        MilestoneHelper::removeReward($user_kicked, 'host');
        return true;
    }

    public function filter($keyword)
    {
        $agencies = $this->agencyRepository->getAgencyByFilter($keyword);

        $agencyManger = $this->userRepository->getAgencyMangerByFilter($keyword);
        return [$agencies, $agencyManger];
    }

    public function filterV2($keyword)
    {
        return $this->agencyRepository->getAgencyByFilter($keyword);
    }

    public function agencyMangers($keyword)
    {
        return $this->userRepository->getAgencyMangerByFilter($keyword);
    }

    public function dailyReport($user, $month, $year, $agencyId = null)
    {
        $timezone = Common::timeZone();
        $member = AgencyJoinRequest::where('user_id', $user->id)->where('status', 1)->first();
        $owner = Agency::where('app_owner_id', $user->id)->where('status', 1)->first();
        $joinedAgency = $member ??  $owner;
        if (! $joinedAgency) {
            return [];
        }
        $joinRecord = UsersJoinedAgency::where('user_id', $user->id)
            ->where('agency_id', $user->agency_id)
            ->latest('join_date')
            ->first();

        if (!$joinRecord) {
            $joinRecord =  null;
        }

        $timezone = getTimezone();
        //        $firstDay = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
        $nowInTimezone = Carbon::now($timezone);


        [$startOfMonth, $endOfMonth] = Carbon::startAndEndOfMonthUTC($year, $month, $timezone);

        [$startDate, $endDate, $joinedDate, $leaveDate] = $this->getReportDateRange(
            $year,
            $month,
            $timezone,
            $joinRecord
        );



        $reportStart = 1;


        $isThisMonth = $month === $nowInTimezone->month && $year === $nowInTimezone->year;
        $endDay = $isThisMonth ? $nowInTimezone->day : $endOfMonth->day;


        // $startDate = ($joinedDate && $joinedDate->greaterThan($startOfMonth)) ? $joinedDate : $startOfMonth;
        // $endDate = ($leaveDate && $leaveDate->lessThan($endOfMonth)) ? $leaveDate : $endOfMonth;
        $dailyDiamonds = $this->giftLogRepository->getByDaily($user->id, $agencyId, $startDate, $endDate, $timezone);
        $dailyTimes = $this->liveTimeRepository->getByDaily($user->id, $startDate, $endDate);

        $dailyDiamonds = $dailyDiamonds->map(function ($data) use ($timezone) {
            // Date is already in the correct timezone from repository
            $data->day = Carbon::parse($data->date)->day;
            return $data;
        });
        $dailyTimes = $dailyTimes->map(function ($data) use ($timezone) {
            // $data->day = Carbon::parse($data->date)->day;
            $data->day = Carbon::parse($data->date, 'UTC')->setTimezone($timezone)->day;
            return $data;
        });

        $totalDays = $user->getTotalDaysJoinedAgency($startDate);

        $saMonth = ltrim($month, '0');
        $userInfoArray =  $user->getSallaryInfoByMonth2($saMonth, $year, $agencyId);

        $totalSalary = @$userInfoArray['total_salary'] ?? 0;
        $totalCutAmount = @$userInfoArray['total_cut_amount'] ?? 0;

        $hours = $dailyTimes->sum('hours');
        $minutes = $hours * 60;

        $minutes = (float) $minutes;
        $totalSeconds = (int) round($minutes * 60);

        $hours = floor($totalSeconds / 3600);
        $minutesPart = floor(($totalSeconds % 3600) / 60);
        $secondsPart = $totalSeconds % 60;

        $formatted = sprintf('%02d:%02d:%02d', $hours, $minutesPart, $secondsPart);



        $data = [
            'user_salary' => [
                'cut_amount' => (int)$totalCutAmount,
                'salary' => doubleval($totalSalary),
            ],
            'request_leave_agency' => $this->leaveAgencyRequestRepository->getRequest($user->id, $agencyId),
            'diamonds' => numToStringNew($dailyDiamonds->sum('diamonds')),
            'live_minutes' => (string)$formatted,
            'active_days' => (string)$totalDays,
            'daly_reports' => []
        ];
        $hours_days = \Cache::get('hours_days') ?? 2;

        for ($startDay = $reportStart; $startDay <= $endDay; $startDay++) {

            $dailyHours = $dailyTimes->where('day', $startDay)->first()?->hours ?? 0;
            $dailyMinutes = $dailyHours * 60;
            $dailyTotalSeconds = (int) round($dailyMinutes * 60);

            $dailyHoursPart = floor($dailyTotalSeconds / 3600);
            $dailyMinutesPart = floor(($dailyTotalSeconds % 3600) / 60);
            $dailySecondsPart = $dailyTotalSeconds % 60;

            $dailyFormatted = sprintf('%02d:%02d:%02d', $dailyHoursPart, $dailyMinutesPart, $dailySecondsPart);


            $diamonds = $dailyDiamonds->where('day', $startDay)->first()?->diamonds ?? 0;




            $data['daly_reports'][] = [
                'day' => sprintf('%02d-%02d', $startDay, $month),
                'live_minutes' => (int)$dailyMinutes,
                'live_minutes_formatted' => (string)$dailyFormatted,
                'diamonds' => numToString((int)$diamonds),
                'is_active_day' => $dailyHours >= $hours_days,

            ];
        }

        return $data;
    }


    protected function getReportDateRange($year, $month, $timezone, $joinRecord)
    {
        $firstDayLocal = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
        $startOfMonth  = $firstDayLocal->copy()->startOfDay();
        $endOfMonth    = $firstDayLocal->copy()->endOfMonth()->endOfDay();

        if ($joinRecord) {
            $joinedDate = Carbon::parse($joinRecord->join_date, $timezone);

            $leaveDate  = $joinRecord->leave_date
                ? Carbon::parse($joinRecord->leave_date, $timezone)
                : $endOfMonth;
        } else {
            $joinedDate = null;
            $leaveDate  = $endOfMonth;
        }

        $startDate = ($joinedDate && $joinedDate->greaterThan($startOfMonth))
            ? $joinedDate
            : $startOfMonth;

        $endDate = ($leaveDate && $leaveDate->lessThan($endOfMonth))
            ? $leaveDate
            : $endOfMonth;

        return [$startDate, $endDate, $joinedDate, $leaveDate];
    }

    public function dataAgency()
    {
        $user = $this->get_user(request());
        if (!$user)  throw new Exception('لا يوجد مستخدم!');

        if (!$user->ownAgency)  throw new Exception('هذا المستخدم لا يمتلك وكاله!');
        $agency = $this->agencyRepository->findById($user->agency_id);
        $userIds    = $this->agencyRepository->userMembers($agency);

        [$diamonds, $days, $hours, $visitors, $follows, $friends] = $this->details($userIds);

        $hosts = $agency->mempers->where("type_user", '!=', 0);
        if (request('host_id')) {
            $hosts = $hosts->where('id', request('host_id'));
        }
        $AllHosts = AgencyHostResource::collection($hosts);
        $monthlyHost = $this->userRepository->getUsersByJoinAgency($agency->id);
        $month_hosts = AgencyMonthlyHostResource::collection($monthlyHost);
        $totalSalary = $agency->salary;
        $last_salary = $agency->last_month_salary;
        $current_salary = $agency->agencySalary ? $agency->agencySalary->sum(\DB::raw('sallary - cut_amount')) : 0;

        $total_hosts_achieve = $this->userSalaryRepository->sum($hosts->pluck("id")->toArray(), 'sallary');

        $total_hosts_percentages = $this->userSalaryRepository->sum($hosts->pluck("id")->toArray(), 'agency_sallary');

        return [
            'id'                =>  $agency->id,
            'name'              =>  $agency->name,
            'notice'            =>  $agency->notice,
            'status'            =>  $agency->status,
            'phone'             =>  $agency->phone,
            'url'               =>  $agency->url,
            'img'               =>  $agency->img,
            'contents'          =>  $agency->contents,
            'diamonds'          =>  $diamonds,
            'hours'             =>  $hours,
            'days'              =>  $days,
            'visitors'          =>  $visitors,
            'friends'           =>  $friends,
            'follows'           =>  $follows,
            'total_profit'      =>  $agency->getTotalSallaryAgency(),
            'total_salary'      =>  $totalSalary,
            'last_salary'       =>  $last_salary,
            'current_salary'    =>  $current_salary,
            'host_sallary'    =>  $total_hosts_achieve,
            'host_percentage'    =>  $total_hosts_percentages,
            'hosts'             =>  $AllHosts,
            'monthly_hosts'     =>  $month_hosts,
        ];
    }

    public function hostReport($id)
    {
        $user = $this->get_user(request());
        if (!$user)  throw new Exception('لا يوجد مستخدم!');
        $host = $this->userRepository->findById($id);
        if (!$host)  throw new Exception('لا يوجد هذا المضيف!');
        if ($host->agency_id != $user->agency_id)  throw new Exception('هذا المستخدم ليس في وكالتك!');


        $today = Carbon::today();
        $previousMonth = $today->subMonth();
        $userSalary = $this->userSalaryRepository->getByUser($host->id, $previousMonth->format('m'), $previousMonth->format('Y'));
        $joinDate = $this->agencyJoinRequestRepository->findByUsersAndAgency($host->id, $user->agency_id)?->updated_at;

        $last_month_di = 0;
        if ($userSalary) {
            $stringWithoutSpaces = str_replace(' ', '', $userSalary->diamond);
            $parts = explode('/', $stringWithoutSpaces);
            $last_month_di = intval($parts[0]);
        }

        $start_date = now()->startOfMonth();
        $end_date = now();
        $dAilyReport = [];
        $total_hours = 0;
        $total_total_hours = 0;
        $total_diamonds = 0;
        $days = 0;
        for ($date = $start_date; $date <= $end_date; $date->addDay(1)) {
            $hours = $this->liveTimeRepository->sumUserHoursByDate($host->id, $date->toDateString());
            $total_hours += $hours;

            if ($total_hours >= 2) {
                $days++;
                $total_hours = 0;
                $total_total_hours += $hours;
            }
            $diamonds = $this->giftLogRepository->getByDate($host->id, $date);
            $total_diamonds +=  $diamonds?->total ?? 0;
            $dAilyReport[] = [
                'date'          => $date->toDateString(),
                'total_hours'   => $hours,
                'total_days'    => $days,
                'diamond'    => $diamonds?->total ?? 0,
            ];
        }


        return [
            'monthly_diamond' => $host->monthly_diamond_received,
            'last_month_diamond' => $last_month_di,
            'date_of_join' => $joinDate,
            'last_active' =>  Carbon::parse($host->online_time)->toDateTimeString(),
            'days' =>  $days,
            'total_hours' =>  $total_total_hours,
            'total_diamonds' =>  $total_diamonds,
            'dailyReport' =>  $dAilyReport,
            'host' => [
                'id' => $host->id,
                'uuid' => $host->uuid,
                'name' => $host->name,
                'img' => $host->profile->avatar,
            ]
        ];
    }

    public function hostDailyReport($request)
    {
        $user = $this->get_user($request);
        if (!$user)  throw new Exception('لا يوجد مستخدم!');

        if (!$user->agency)  throw new Exception('لا تملك وكاله!');

        $hosts = $this->userRepository->getByHost($user->agency_id, $request->host_id);

        return $hosts;
    }

    public function editAgency($request)
    {
        $user = $this->get_user(request());
        if (!$user)  throw new Exception('لا يوجد مستخدم!');
        if (!$user->ownAgency)  throw new Exception('هذا المستخدم لا يمتلك وكاله!');
        $agency = $this->agencyRepository->findById($user->agency_id);
        $data = [
            'name' => $request->name,
            'notice' => $request->notice,
            'phone' => $request->phone,
        ];
        $this->agencyRepository->update($data, $agency->id);
        return $agency;
    }



    public function get_user($request)
    {
        if ($request->user_id) {
            $admin = $this->adminRepository->findById($request->user()->id);
            if (isset($admin) && $admin->isRole("admin")) {
                $user = $this->userRepository->findById($request->user_id);
            } else {
                $user = null;
            }
        } else {
            $user = $this->userRepository->findById($request->user()->id);
        }
        return $user;
    }

    public function showAgencyRequest($user, $type)
    {
        // التحقق مما إذا كان المستخدم هو المدير
        $admin = $this->agencyRepository->getAdminByUserId($user->id);
        if ($admin) {
            $agency = $this->agencyRepository->getAgencyById($admin->agency_id);
        } else {
            $agency = $this->agencyRepository->getAgencyByOwnerId($user->id);
        }

        if (!$agency) {
            return Common::apiResponse(0, __('api_responses.notAdmin'));
        }

        $agency_id = $agency->id;
        $list_req = $this->agencyRepository->getJoinRequests($agency_id);

        if ($type == "application") {
            $list_req1 = $list_req->where('status', 0)->with('user')->get();
            $list_req = MyDataForAgencyNewResource::collection($list_req1, 'application');
        } elseif ($type == "record") {
            $list_req = $list_req->where('status', '!=', 0)->with('user', 'admin')->get();
            $list_req = MyDataForAgencyNewResource::collection($list_req, 'record');
        }

        if ($list_req) {
            return $list_req;
        }

        return [];
    }

    public function allRequests($id, $uuid, $perPage, $page, $status, $action)
    {
        return $this->agencyRepository->getByAdditionalInfoPaginate($id, $uuid, $perPage, $page, $status, $action);
    }

    public function activeAgencies($id, $perPage, $page)
    {
        return $this->agencyRepository->getActiveAgency($id, $perPage, $page);
    }

    public function allActiveAgencies($id)
    {
        return $this->agencyRepository->getAllActiveAgency($id);
    }

    public function agencyById($id)
    {
        return $this->agencyRepository->agencyById($id);
    }

    public function deleteAgency($id)
    {
        $agency = $this->agencyRepository->findOrFail($id);
        $owner = User::find($agency->app_owner_id);
        if ($owner) {
            // Use centralized method to remove owner
            \App\Facades\UserHandling::changeUserAgency($owner, 0, 0);
        }
        $agency->delete();
        return true;
    }

    public function changeAgencyMembers($oldAgencyId, $newAgencyId)
    {
        $this->userRepository->changeAgencyForHost($oldAgencyId, $newAgencyId);
        $this->userSalaryRepository->changeAgencyId($oldAgencyId, $newAgencyId);
        return true;
    }

    public function AllAgencyExceptOld($oldAgencyId, $search, $perPage, $page)
    {
        return $this->agencyRepository->agencies($oldAgencyId, $search, $perPage, $page);
    }

    public function createAgencyUtd($request)
    {
        $existingAgency = $this->agencyRepository->getAgencyByOwnerIdAndType($request->app_owner_id, $request->type);
        if ($existingAgency) {
            throw new Exception(__('This user is already an owner of a :type agency: :name', [
                'type' => $request->type == 1 ? 'host' : 'shipping',
                'name' => $existingAgency->name
            ]));
        }

        if ($request->hasFile('img')) {

            $image = Common::upload('agency', $request->file('img'));
        }
        $data = [
            'app_owner_id' => $request->app_owner_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'notice' => $request->notice,
            'url' => $request->url,
            'img' => $image ?? '',
            'contents' => $request->contents,
            'type' => $request->type,
            // 'Shipping_agency' => $request->Shipping_agency,
        ];

        $agency =  $this->agencyRepository->create($data);
        //Common::createUserAdmin($request->app_owner_id);

        if ($request->type == 1) {

            $userType = 2;
        } elseif ($request->type == 2) {
            $userType = 3;
        }

        $owner = User::find($request->app_owner_id);
        if ($owner) {
            // Use centralized method to assign owner
            \App\Facades\UserHandling::changeUserAgency($owner, $agency->id, $userType);
        }

        return true;
    }

    public function updateAgencyUtd($id, $request)
    {

        $agency = $this->agencyRepository->findOrFail($id);

        if ($agency->app_owner_id != $request->app_owner_id) {
            $existingAgency = $this->agencyRepository->getAgencyByOwnerIdAndType($request->app_owner_id, $request->type);
            if ($existingAgency && $existingAgency->id != $id) {
                throw new Exception(__('This user is already an owner of a :type agency: :name', [
                    'type' => $request->type == 1 ? 'host' : 'shipping',
                    'name' => $existingAgency->name
                ]));
            }

            $oldOwner = User::find($agency->app_owner_id);
            if ($oldOwner) {
                // Use centralized method to remove old owner
                \App\Facades\UserHandling::changeUserAgency($oldOwner, 0, 0);
                Admin::where('username', $oldOwner->uuid)->delete();
            }
            //Common::createUserAdmin($request->app_owner_id);
        }

        if ($request->type == 1) {

            $userType = 2;
        } elseif ($request->type == 2) {
            $userType = 3;
        }

        $newOwner = User::find($request->app_owner_id);
        if ($newOwner) {
            // Use centralized method to assign new owner
            \App\Facades\UserHandling::changeUserAgency($newOwner, $agency->id, $userType);
        }
        $dataAgency = [
            'app_owner_id' => $request->app_owner_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'notice' => $request->notice,
            'url' => $request->url,
            'contents' => $request->contents,
            'type' => $request->type,
        ];
        if ($request->hasFile('img')) {

            $dataAgency['img'] = Common::upload('agency', $request->file('img'));
        }
        $this->agencyRepository->update($dataAgency, $id);
        return true;
    }

    public function show($id)
    {
        return $this->agencyRepository->findOrFail($id);
    }

    public function destroy($id)
    {
        $data = $this->agencyRepository->findOrFail($id);
        $data->delete();
        return true;
    }

    public function getAllRequestUtd($status, $agencyId, $id, $perPage, $page)
    {
        return  $this->agencyJoinRequestRepository->allRequests($status, $agencyId, $id, $perPage, $page);
    }

    public function showAgencyJoinRequest($id)
    {
        return $this->agencyJoinRequestRepository->findOrFail($id);
    }

    public function updateAgencyJoinRequest($id, $request)
    {
        $user = $this->userRepository->findOrFail($request->user_id);
        if (!$user) throw new \Exception(' user not found');
        if (($user->agency_id != 0) || ($user->agency_id != null)) throw new \Exception('user already in agency');
        $agency = $this->agencyRepository->findById($request->agency_id);
        if (!$agency) throw new \Exception(' agency not found');
        $data = [
            'user_id' => $request->user_id,
            'agency_id' => $request->agency_id,
            'status' => $request->status,
        ];
        $this->agencyJoinRequestRepository->update($data, $id);
        if ($request->status == 1) {
            // UserCommon::userVip($user , 'updatea-gency-join-request');
            $this->userRepository->update(['type_user' => 1, 'monthly_diamond_received' => 0], $user->id);
        }
        return true;
    }

    public function allAgencyCharged($id)
    {
        return $this->shippingAgencyRepository->getChargeAgency($id);
    }
}
