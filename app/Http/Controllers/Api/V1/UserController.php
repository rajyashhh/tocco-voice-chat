<?php

namespace App\Http\Controllers\Api\V1;

use Auth;
use Exception;
use App\Models\Ban;
use App\Models\Gift;
use App\Models\Pack;
use App\Models\PackLog;
use App\Models\User;
use App\Models\Ware;
use App\Models\Agency;
use App\Models\Config;
use App\Enums\UserType;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\UserSallary;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Facades\UserHandling;
use App\Jobs\RefreshUserPresence;
use App\Services\UserService;
use Modules\Vip\Entities\Vip;
use App\Enums\UserCoinLogType;
use App\helper\TryCatchHelper;
use App\Helpers\UserPackHelper;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Helpers\UserCoinLogHelper;
use App\Helpers\FirebaseValidate;
use App\Models\UserCodeInvitation;
use App\Models\UserEarnInvitation;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use App\Services\FilterChargeService;
use Illuminate\Support\Facades\Cache;
use App\helper\InvitationWalletHelper;
use App\Http\Resources\CpUserResource;
use App\helper\InvitationEarningHelper;
use App\Http\Resources\MyDataUtdResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\AppSettingResource;
use App\Http\Resources\UserVipUtdResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\UserPackUtdResource;
use App\Http\Resources\UserPackVipResource;
use App\Http\Resources\Api\V1\MyDataResource;
use App\Http\Resources\Api\V1\OnlineResource;
use App\Http\Resources\UserVisitRoomResource;
use App\Http\Resources\Api\V1\MyStoreResource;
use App\Http\Services\OtpProviderService;
use App\Http\Services\ProfileRelationsService;
use App\Http\Resources\Api\V1\AllUsersResource;
use App\Http\Resources\Api\V1\DataUserResource;
use App\Http\Resources\Api\V1\ShowUserResource;
use App\Http\Resources\Api\V1\UserPlayResource;
use App\Http\Resources\Api\V1\UserTypeResource;
use App\Http\Resources\Api\V1\LevelUserResource;
use App\Http\Resources\Api\V1\UserResourceSerche;
use App\Http\Resources\Api\V1\UserTargetResource;
use App\Http\Resources\Api\V1\DeviceTokenResource;
use Modules\WhatsappAuth\Services\WhatsappWebhook;
use Modules\FixedTarget\Services\FixedTargetService;
use App\Http\Resources\Api\V1\ShowUserSettingResource;
use Modules\SwitchAccount\Entities\UserDevicesHistory;
use App\Http\Resources\Api\V1\UserLevelHistoryResource;
use Modules\Achievement\Http\Services\UserAchievementService;
use Modules\Achievement\Transformers\UserAchievementLevelsResource;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function userGifts()
    {
        $user = auth()->user();

        if (!$user) {
            return Common::apiResponse(false, 'Unauthorized', []);
        }
        $user->update(['new_gift' => false]);

        $gifts = Gift::where('price', '<=', $user->di)->paginate(10);
        return Common::apiResponse(true, '', $gifts);
    }

    public function chargerAgency(Request $request, ProfileRelationsService $profileRelationsService)
    {

        $users = $this->userService->userCharge();
        $usersType = UserTypeResource::collection($users);
        [$senderLevels, $receivedImage] = $profileRelationsService->getLevelsSenderAndReceiver($usersType);
        UserTypeResource::initializeData($senderLevels, $receivedImage, null);
        return Common::apiResponse(1, '', $usersType);
    }
    public function userRoom()
    {

        $user = User::with('room')->where('id', Auth::id())->first();

        return Common::apiResponse(true, 'Success', $user);
    }
    public function checkPhone(Request $request)
    {
        $request->validate([
            'phone' => 'required'
        ]);
        $exists = User::where('phone', $request->phone)->first();

        if ($exists) {
            return Common::apiResponse(true, 'Success', true);
        }
        return Common::apiResponse(true, 'Success', false);
    }

    public static function checkPack($userId, $type, $dress = null)
    {
        return Pack::query()->with('ware')
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where(function ($q) {
                $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
            });
        //        if ($dress != null) $pack->where('target_id', $dress);
        //        return $pack;
    }
    public function image_intro($id)
    {
        if (!$id) return Common::apiResponse(false, 'messing user id parameter', 400);
        $user = User::find($id);
        if (! $user) return Common::apiResponse(false, 'user not found', 400);
        $dr = '';
        $pack = self::checkPack($user->id, 6, $user->dress_3);
        $pack = $pack->pluck('target_id');

        //        if (!$pack) return Common::apiResponse(false, 'active product not found', 400);
        $ware = Ware::query()
            ->whereIn('id', $pack)
            ->where('type', 6)
            ->get();
        if (! $ware) return Common::apiResponse(false, ' not found', 400);
        if (!$ware->isEmpty()) {
            $dr = $ware->map(function ($w) use ($user) {
                $usageCount = PackLog::where('user_id', $user->id)
                    ->where('target_id', $w->id)
                    ->where('use_type', 1)
                    ->sum('get_nums');
                return [
                    'image' => $w->show_img,
                    'id' => $w->id,
                    'count' => $usageCount,
                ];
            });
        }


        if ($dr == '') {
            return Common::apiResponse(true, 'Success', []);
        }
        return Common::apiResponse(true, 'Success', $dr);
    }
    public function showSetting(Request $request)
    {
        $user = $request->user();
        $sitting = $this->userService->setting($user->id, $request);

        return Common::apiResponse(1, 'تم التعديل بنجاح', new ShowUserSettingResource($sitting));
    }

    public function user_statistic(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'date' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            // Other validation rules...
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(false, 'date format error');
        }
        $user = $request->user();

        try {
            $date = \Carbon\Carbon::parse($request->date);
        } catch (Exception $e) {
        }

        $month = $date?->month ?? now()->month;
        $year = $date?->year ?? now()->year;

        if (now()->month == $month && now()->year == $year) {
            $cacheKey = 'cache-data-my-store-' . $user->id;
            if (Cache::add($cacheKey, true, now()->addSeconds(30))) {
                $targetService = new FixedTargetService($user);
                ($targetService)->calculateTarget();
            }
        }
        $data = $this->userService->userStatic($user,  $month, $year);
        return Common::apiResponse(true, '', $data, 200);
    }

    public function appSetting(): JsonResponse
    {
        return Common::apiResponse(true, '', $this->buildAppSetting(), 200);
    }

    /**
     * Read-only builder for the app-setting payload. No DB writes.
     * Returns the resource passed to apiResponse so my-data/bootstrap stay identical.
     */
    private function buildAppSetting(): AppSettingResource
    {
        $now = now();

        $user = auth()->user()
            ->load([
                'userSetting',
                'bans' => fn($q) => $q->where('ban_type_id', 7)->where('type', 'action')->whereRaw("DATE_ADD(created_at, INTERVAL duration HOUR) > ?", [$now]),
                'salaryRequests' => fn($q) => $q->where('status', 2),
            ]);

        return AppSettingResource::make($user);
    }

    public function charges(Request $request): JsonResponse
    {
        $type = $request->type;
        $key = $request->q;
        $perPage = 10;
        $currentPage = request()->has('page') ? request()->page : 1;

        $filterCharges = (new FilterChargeService($perPage, $currentPage))->result($type, $key);

        if (! $filterCharges) {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        return response()->json($filterCharges);
    }

    public function search(Request $request)
    {
        $key = $request->search;
        $family = $request->family;
        $users = $this->userService->searchUsers($key, $family);


        $users = $users->through(function ($user) {
            $user->level = Common::level_centerSerch($user->id);

            return $user;
        });
        return response()->json($users);
    }

    public function search2(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchUsersWithPage($key, $page);

        return response()->json($users);
    }

    public function searchOwnerRoomWithPage(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchOwnerRoomWithPage($key, $page);

        return response()->json($users);
    }

    public function usersAudioRoom(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchUsersAudioWithPage($key, $page);

        return response()->json($users);
    }

    public function usersLiveRoom(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchUsersLiveWithPage($key, $page);

        return response()->json($users);
    }

    public function search2_new(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchUsersWithPageNew($key, $page);

        return response()->json($users);
    }
    public function userAgency(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchUsersInAgency($key, $page);

        return response()->json($users);
    }
    public function bdCountryUsers(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        // Pass the client-supplied country_id through the actor's country scope
        // (fail-closed) instead of trusting it raw — matching every sibling search
        // handler. An out-of-scope request collapses to [0] and returns nothing.
        $countryIds = $this->scopedCountryIds($request->get('country_id'));
        $users = $this->userService->bdCountryUsers($key, $page, $countryIds);

        return response()->json($users);
    }


    public function user_bd(Request $request)
    {
        $key = $request->q;

        $page = $request->get('page', 1);
        $users = $this->userService->user_bd($key, $page);

        return response()->json($users);
    }

    public function user_bd2(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->user_bd2($key, $page);

        return response()->json($users);
    }

    /**
     * Resolve the scoped area-manager server-side from the authenticated admin,
     * ignoring any client-supplied area_manager_id. The session preview override
     * (area_manager_id) is honoured only for a super admin; a real area manager is
     * always pinned to their own id. Mirrors Common::areaCountries().
     */
    private function scopedAreaManagerId()
    {
        $admin = Admin::user();

        if (!$admin) {
            return null;
        }

        if ($admin->isAdministrator() || $admin->can('*')) {
            return session('area_manager_id') ?? $admin->id;
        }

        return $admin->id;
    }

    /**
     * Country ids in scope for the authenticated admin, derived server-side and
     * fail-closed. Covers every admin role that reaches the search dropdowns:
     *   - No admin session                → [0] (zero rows)
     *   - Unrestricted admin (super/*)    → [] (no restriction)
     *   - Area manager / sub area manager → Common::areaCountries()
     *   - Country manager / sub super     → [own country_id]
     * A client-supplied country id is only ever INTERSECTED with this scope, never
     * used to replace it: an out-of-scope value yields [0] (zero rows). Return
     * shape suits the `->when($ids, fn($q) => $q->whereIn('country_id', $ids))`
     * idiom, where [] means "all".
     */
    private function scopedCountryIds($requestedCountryId = null): array
    {
        $admin = Admin::user();

        if (!$admin) {
            return [0];
        }

        if ($admin->isAdministrator() || $admin->can('*')) {
            $scope = [];
        } else {
            $scope = Common::areaCountries();
            if (empty($scope)) {
                $scope = $admin->country_id ? [(int) $admin->country_id] : [0];
            }
        }

        $requested = empty($requestedCountryId) ? [] : array_map('intval', (array) $requestedCountryId);

        if (empty($requested)) {
            return $scope;
        }

        if (empty($scope)) {
            return $requested;
        }

        $allowed = array_values(array_intersect($scope, $requested));

        return empty($allowed) ? [0] : $allowed;
    }

    public function userBdByCountries(Request $request)
    {
        $areaManager = $this->scopedAreaManagerId();
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->userBdByCountries($areaManager, $key, $page);

        return response()->json($users);
    }

    public function superAdminUsers(Request $request)
    {
        $key = $request->q;
        $selectedId = $request->selected_id;
        $page = $request->get('page', 1);
        $countryIds = $this->scopedCountryIds();
        $users = $this->userService->superAdminUsers($key, $page, $selectedId, $countryIds);

        return response()->json($users);
    }

    public function subSuperAdminUsers(Request $request)
    {
        $key = $request->q;

        $page = $request->get('page', 1);
        $countryIds = $this->scopedCountryIds();
        $users = $this->userService->subSuperAdminUsers($key, $page, $countryIds);

        return response()->json($users);
    }

    public function subAreaManager(Request $request)
    {
        $key = $request->q;

        $page = $request->get('page', 1);
        $countryIds = $this->scopedCountryIds();
        $users = $this->userService->subAreaManager($key, $page, $countryIds);

        return response()->json($users);
    }

    public function usersAreaManager(Request $request)
    {
        $key = $request->q;
        $selectedId = $request->selected_id;
        $page = $request->get('page', 1);
        $countryIds = $this->scopedCountryIds();
        $users = $this->userService->usersAreaManager($key, $page, $selectedId, $countryIds);

        return response()->json($users);
    }

    public function superAdminUsers2(Request $request): JsonResponse
    {
        $key = $request->q;

        $page = $request->get('page', 1);
        $countryIds = $this->scopedCountryIds();
        $users = $this->userService->superAdminUsers2($key, $page, $countryIds);

        return response()->json($users);
    }

    public function usersByCountry(Request $request): JsonResponse
    {
        $superAdminId = $request->get('super_admin_id');
        $key = $request->get('q'); // search keyword
        $page = $request->get('page', 1);
        $countryIds = $this->scopedCountryIds();

        $users = $this->userService->usersByCountry($superAdminId, $key, $page, $countryIds);

        return response()->json($users);
    }

    public function usersByCountries(Request $request): JsonResponse
    {
        $areaManager = $this->scopedAreaManagerId();
        $key = $request->get('q'); // search keyword
        $page = $request->get('page', 1);

        $users = $this->userService->usersByCountries($areaManager, $key, $page);

        return response()->json($users);
    }

    public function agencies(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchInAgency($key, $page);

        return response()->json($users);
    }

    public function superAdminAgencies(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $countryIds = $this->scopedCountryIds($request->get('country_id'));

        $users = $this->userService->superAdminAgencies($key, $page, $countryIds);

        return response()->json($users);
    }

    public function hostAgencies(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchInHostAgency($key, $page);

        return response()->json($users);
    }

    public function userAgencyShipping(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchUsersInAgencyShipping($key, $page);

        return response()->json($users);
    }

    public function userFamily(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $users = $this->userService->searchUsersInFamily($key, $page);

        return response()->json($users);
    }

    public function joinAccount(Request $request)
    {
        $user = $request->user();
        try {
            $this->userService->bind($user, $request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, 'bind successful', new UserResource($user));
    }

    public function my_data(Request $request)
    {
        $user = Auth::user();
        try {
            $userWithMedals = $this->userService->processUserData($user, $request->header('X-Device-Token'), $request->header('lat'), $request->header('long'), $request->header('iso'));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

        return Common::apiResponse(true, '', $this->buildMyData($userWithMedals), 200);
    }

    /**
     * Read-only builder for the my-data payload. No DB writes.
     * Receives the already-resolved user-with-medals so callers control the
     * source query (my_data reuses processUserData's result; bootstrap reads fresh).
     */
    private function buildMyData($userWithMedals): MyDataResource
    {
        request()->default_background = Cache::remember('default_background', 300, function () {
            return \DB::table('backgrounds')->where('enable', 1)->orderBy('id', 'asc')->first()?->img;
        });

        return new MyDataResource($userWithMedals);
    }

    public function update_user_multi_images($id, Request $request)
    {
        $user = $request->user();

        $request->validate([
            'new_multi_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            if ($request->hasFile('new_multi_image')) {
                $imagePath = Common::upload('profile', $request->file('new_multi_image'));
            } else {
                return Common::apiResponse(false, 'image required ', [], 422);
            }

            $userWithMedals = $this->userService->update_user_multi_images($user, $id, $imagePath);

            return Common::apiResponse(true, 'successful', [], 200);
        } catch (\Exception $exception) {
            return Common::apiResponse(false, $exception->getMessage(), null, 400);
        }
    }



    public function userFriend(Request $request)
    {
        $user = $request->user();
        $keyword = $request->keywords ?? '';
        return $this->userService->handleUserRelations($user, $request->type, $keyword);
    }

    public function follow(Request $request)
    {
        return $this->userService->followUser($request);
    }

    public function unFollow(Request $request)
    {
        return $this->userService->unFollowUser($request);
    }

    public function my_store_all(Request $request)
    {
        return Common::apiResponse(true, '', $this->buildMyStore($request), 200);
    }

    /**
     * Builder for the my-store payload. Mirrors my_store_all exactly. NOTE: not
     * strictly read-only — UserService::myStore runs a salary recalc that writes
     * (UserSallary / RoomSalary), self-gated by a 30s cache lock. A 304 on this
     * endpoint still runs that gated write before the body is dropped.
     */
    private function buildMyStore(Request $request): MyStoreResource
    {
        $user = $request->user();
        $user = $this->userService->myStore($user, $request);
        return new MyStoreResource($user);
    }

    /**
     * Cold-start merge endpoint: collapses my-data + my-store + user-app-setting
     * into a single GET so weak devices on bad networks do 1 round-trip, not 3.
     * The my-data presence/geo/device-token writes the standalone my_data does
     * synchronously are instead done async via RefreshUserPresence (so the GET
     * stays non-blocking) — this REQUIRES a worker on the 'default' queue, else
     * presence/push-token/geo updates silently never apply for bootstrap callers.
     * my-store still runs its 30s-gated salary recalc synchronously (see
     * buildMyStore). Mirrors my_data's error contract (400 on failure).
     */
    public function bootstrap(Request $request)
    {
        try {
            RefreshUserPresence::dispatch(
                (int) $request->user()->id,
                $request->header('X-Device-Token'),
                $request->header('lat'),
                $request->header('long'),
                $request->header('iso'),
                app()->getLocale()
            );

            $userWithMedals = $this->userService->showUser($request->user()->id);

            return Common::apiResponse(true, '', [
                'my_data'     => $this->buildMyData($userWithMedals),
                'my_store'    => $this->buildMyStore($request),
                'app_setting' => $this->buildAppSetting(),
            ], 200);
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function ranking_room(Request $request)
    {
        $toArray =  $this->userService->roomRanking($request);
        return Common::apiResponse(1, '', $toArray);
    }

    public function show(Request $request, $id)
    {
        $isVisit = @$request->is_visit == 'true' ? true : false;

        try {
            $this->userService->showUserCheck($id, $isVisit);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }

        $user = $this->userService->showUser($id);
        $response = (new UserResource($user))->toArray($request);

        $authUserId = auth()->id();
        $user->load([
            'chatRoomsAsUser' => function ($q) use ($authUserId) {
                $q->where('user_id2', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            },
            'chatRoomsAsUser2' => function ($q) use ($authUserId) {
                $q->where('user_id', $authUserId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($authUserId) {
                        $query->where('user_id', '<>', $authUserId)
                            ->where('status', '<>', 'seen');
                    }]);
            },
        ]);

        $chatRoom = $user->chatRoomsAsUser->first() ?? $user->chatRoomsAsUser2->first() ?? null;

        $unreadMessagesCount = $chatRoom?->unread_messages_count ?? 0;

        $response['chat_id'] = $chatRoom->id ?? null;
        $response['unread_messages_count'] = $unreadMessagesCount;

        return Common::apiResponse(true, '', $response, 200);
    }

    public function showUsersDetails(Request $request)
    {

        $users_ids =  explode(',', $request->users_ids);

        $user = $this->userService->showUsers($users_ids);
        $response = (UserResource::collection($user))->toArray(request());

        return Common::apiResponse(true, '', $response, 200);
    }

    public function vTwoshow(Request $request, $id)
    {
        $isVisit = @$request->is_visit == 'true' ? true : false;
        $auth   = $request->user();
        try {
            $user = $this->userService->vTwoshowUser($id, $auth, $request, $isVisit);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(true, '', new \App\Http\Resources\Api\V2\UserResource($user), 200);
    }

    public function changePhoneWhatsapp(Request $request, WhatsappWebhook $whatsappWebhook)
    {
        $phone = $request->phone;
        if (!$phone) return Common::apiResponse(0, 'missing params', null, 422);
        $user = $request->user();
        $rules = [
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->withoutTrashed()->ignore($user->id),
            ],
        ];
        if ($user->phone == $phone) return Common::apiResponse(0, 'Old phone is wrong', null, 404);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Common::apiResponse(0, 'Validation failed', $validator->errors(), 422);
        }

        $whatsappWebhookValidate = $whatsappWebhook->getLastValidatedPhone($phone);
        if (!$whatsappWebhookValidate) {
            return Common::apiResponse(false, __('current phone not verified'));
        }

        $user->phone = $phone;

        $user->save();
        return Common::apiResponse(1, 'reset successful', new UserResource($user));
    }

    public function resetWhatsapp(Request $request, WhatsappWebhook $whatsappWebhook)
    {
        $phone = $request->phone;
        if (!$phone || !$request->password) return Common::apiResponse(0, 'missing params', null, 422);
        $user = $request->user();


        if ($user->phone != $phone) return Common::apiResponse(0, 'phone number not register with your account', null, 404);

        $rules = [
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Common::apiResponse(0, 'Validation failed', $validator->errors(), 422);
        }
        try {
            $this->userService->resetWhatsapp($request, $whatsappWebhook);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, 'reset successful', new UserResource($user));
    }

    public function userWithSearch(Request $request)
    {
        $data = $this->userService->allUsers($request->search);
        return Common::apiResponse(1, '', $data);
    }

    public function userInfoWithRole(Request $request) {}


    public function logout(Request $request)
    {
        $user = $request->user();
        $user->is_logout = 1;
        $user->save();
        $user->currentAccessToken()->delete();
        return Common::apiResponse(1, 'logged out');
    }

    public function user_agency_information()
    {
        $user   =   Auth::user();
        $month  =   \request('month');
        $year  =   \request('year');

        // Use withCount instead of loading full collections (fixes N+1)
        $agency = Agency::query()
            ->with(['owner.profile'])
            ->where('app_owner_id', $user->id)
            ->withCount(['joinRequests', 'mempers'])
            ->first();
        if (!$agency) return Common::apiResponse(0, __("api_responses.u_not_owner_agncy"), []);
        $total_host_target = UserSallary::where('user_agency_id', $agency->id);

        if ($month != null && $year != null) {
            $total_host_target = $total_host_target->where('month', $month)
                ->where('year', $year);
        }
        $total_host_target = $total_host_target->sum('sallary');
        $owner =       $agency->owner;
        $owner->avatar = $agency->owner->avatar;
        $data = [
            'id'                => $agency->id,
            'name'              => $agency->name,
            'image'             => $agency->img,
            'pio'               => $agency->contents,
            'num_of_hosts'      => $agency->mempers_count,
            'total_salary'      => $total_host_target,
            'agency_target'     => $agency->getSalary($month, $year),
            'number_request'              => $agency->join_requests_count,
            'owner' => $owner
        ];
        return Common::apiResponse(1, '', $data);
    }

    public function changePhone(Request $request)
    {
        $otpProvider = new OtpProviderService();

        if ($otpProvider->usesServerCode()) {
            if (!$request->phone || !$request->current_phone || !$request->code) return Common::apiResponse(0, 'missing params', null, 422);
        } else {
            if (!$request->phone || !$request->current_phone || !$request->firebase_id_token) return Common::apiResponse(0, 'missing params', null, 422);
        }
        $user = $request->user();
        $rules = [
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
        ];
        if ($user->phone != $request->current_phone) return Common::apiResponse(0, 'Old phone is wronge', null, 404);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Common::apiResponse(0, 'Validation failed', $validator->errors(), 422);
        }
        if ($otpProvider->usesServerCode()) {
            // The OTP must have been sent to the NEW phone (ownership proof).
            if (!$otpProvider->verify($request->phone, (string) $request->code)) {
                return Common::apiResponse(false, __('api_responses.invalid_new_code'));
            }
        } else {
            try {
                FirebaseValidate::validateIdToken($request->firebase_id_token);
            } catch (\Throwable $e) {
                return Common::apiResponse(false, __('api_responses.invalid_new_code'));
            }
        }

        $user->phone = $request->phone;


        $user->save();

        if ($otpProvider->usesServerCode()) {
            $otpProvider->consume($request->phone);
        }

        return Common::apiResponse(1, 'reset successful', new UserResource($user));
    }

    public function get_users_support()
    {
        $userId = request('user_id');
        $results = $this->userService->supporter($userId);
        $achievementService = new UserAchievementService();

        $previousTotal = null;

        $data = $results->map(function ($result) use ($achievementService, &$previousTotal) {
            $sender = $result->sender;

            if (!$sender) {
                return null;
            }

            $senderId = $sender->id;
            $dressId = $sender->dress_1;

            $image = $sender?->profile?->avatar ?? '';

            $currentTotal = $result->total;
            $totalDiff = isset($previousTotal) ? $previousTotal - $currentTotal : 0;
            $previousTotal = $currentTotal;

            $frame = Common::getUserDress($senderId, $dressId, 4, 'img2', true)
                ?: Common::getUserDress($senderId, $dressId, 4, 'img1', true);

            return [
                'id'        => $sender->id,
                'uuid'      => $sender->uuid,
                'name'      => $sender->name,
                'image'     => $image,
                'gender'    => $sender->gender,
                'country'   => [
                    'id'   => $sender->country->id ?? 0,
                    'name' => $sender->country->name ?? '',
                    'flag' => $sender->country->flag ?? '',
                ],
                'achievements' => UserAchievementLevelsResource::collection(
                    $achievementService->getUserAchievement($sender)
                ),
                'total'      => numToString($currentTotal),
                'total_diff' => $totalDiff,
                'frame'      => $frame,
                'frame_id'   => $frame ? $dressId : 0,
                'colored_name'   => UserPackHelper::getColorName($sender),
            ];
        })->filter()->values()->all(); // filter to remove nulls

        $count = count($data);
        $arr = [
            'top'   => $count < 4 ? $data : array_slice($data, 0, 3),
            'other' => $count < 4 ? [] : array_slice($data, 3),
            'count' => $count,
        ];

        return Common::apiResponse(1, '', $arr);
    }

    public function delete(Request $request)
    {
        $user = $request->user();

        if (UserHandling::checkIfUserOwnerOfAgency($user)) {
            return Common::apiResponse(0, 'This User is the host Of agency can\'t delete it');
        }
        $user->tokens()->delete();
        $user->delete();
        return Common::apiResponse(1, 'account deleted successfully');
    }



    public function switchAccountAnonymous(Request $request)
    {
        $user = $request->user();
        try {
            [$user, $token] = $this->userService->anonymous($user, $request);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        if (!$this->canLogin($user)) {
            return Common::apiResponse(false, 'you are blocked', [], 408);
        }
        $user->auth_token = $token;
        return Common::apiResponse(
            true,
            __('api_responses.logged'),
            [
                'id'            => $user->id,
                'is_first'      => @(bool)$user->is_points_first,
                'auth_token'    => $user->auth_token
            ]
        );
        return Common::apiResponse(true, 'logged in successfully', new MyDataResource($user), 200);
    }

    public function canLogin($user)
    {
        $status = $user instanceof User ? $user->status : ($user['status'] ?? null);

        return $status == 1;
    }


    public function explain_invitation()
    {
        $lang = app()->getLocale();



        if ($lang == "en") {
            $data = Common::getSettingValue('invitation_content_en');
        } else {
            $data = Common::getSettingValue('invitation_content_ar');
        }

        return Common::apiResponse(true, '', $data, 200);
    }

    public function UserEarnFromInvitationStatistics()
    {
        $userId             = Auth::id();
        $parentInvitations  = UserEarnInvitation::where("parent_id", $userId);
        $UserCodeInvitation = UserCodeInvitation::where("user_id", $userId);
        if ($parentInvitations != null) {
            $data = [
                "totalEarned"  => $parentInvitations->sum("amount"),
                "earnedDay"    => $parentInvitations->whereDate("created_at", date("Y-m-d"))->sum("amount"),
                "TotalInvited" => $UserCodeInvitation->count(),
                "invitedDay"   => $UserCodeInvitation->whereDate("created_at", date("Y-m-d"))->count(),
            ];
            return Common::apiResponse(true, '', $data, 200);
        }
        return Common::apiResponse(true, '', $data = [], 200);
    }

    public function parentUser()
    {
        $userId = Auth::id();
        $data   = UserCodeInvitation::with("user")->where("invited_id", $userId)->first();
        $lang   = app()->getLocale();
        if ($lang == 'ar') {
            $mes_user_not_found = 'لم يتم العثور علي المستخدم';
            $success_mes        = 'لا يوجد بيانات';
        } else {
            $mes_user_not_found = 'It was not found on the user';
            $success_mes        = 'not found data';
        }

        if ($data) {
            if ($data->user) {
                return Common::apiResponse(true, '', new MyDataResource($data->user), 200);
            }
            return Common::apiResponse(false, $mes_user_not_found, $data = [], 200);
        }
        return Common::apiResponse(false, $success_mes, $data = [], 200);
    }

    public function UserEarnFromInvitation()
    {
        $userId = Auth::id();
        $data   = UserEarnInvitation::with("user:id,name,uuid")->select("id", "user_id", "parent_id", "updated_at", "user_charge", "parent_percentage")->where("parent_id", $userId)->orderByDesc('created_at')->get();
        return Common::apiResponse(true, '', $data, 200);
    }

    public function AddCodeInvitation(Request $request)
    {
        if (self::isStopInvitationValid()) {
            return Common::apiResponse(false, __('invitation.stopped'), null, 403);
        }

        $userId     = Auth::id();
        $userParent = $this->getUserByCode($request->code);
        $existing   = UserCommon::CheckUserParent($userId);
        $isNew      = UserCommon::CheckUserNew($userId);

        if (!$this->isDeviceUniqueForUser($userId, Auth::user()->device_token)) {
            return Common::apiResponse(false, __('invitation.device_in_use'), null, 403);
        }

        if (!$userParent) {
            return Common::apiResponse(false, __('invitation.user_not_found'), $existing, 404);
        }

        if (!$isNew) {
            return Common::apiResponse(false, __('invitation.validation_failed'), $existing, 422);
        }

        if ($existing !== null) {
            return Common::apiResponse(false, __('invitation.already_registered'), $existing, 409);
        }

        // Use transaction to prevent race condition
        try {
            DB::transaction(function () use ($userId, $userParent) {
                // Lock for update to prevent concurrent requests
                $existingInvitation = UserCodeInvitation::where('invited_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if ($existingInvitation) {
                    throw new \Exception('already_invited');
                }

                // Records the inviter↔invitee link plus two UNCLAIMED ledger rows
                // (first_join_reward_host, first_join_reward_invitee). No coins are
                // credited here anymore — the inviter extracts the host reward together
                // with their commission, and the invitee claims their one-time bonus
                // from the invite screen (after which it disappears).
                $invitation = $this->createInvitation($userParent->id, $userId);
            });
        } catch (\Exception $e) {
            if ($e->getMessage() === 'already_invited') {
                return Common::apiResponse(false, __('invitation.already_registered'), null, 409);
            }
            throw $e;
        }

        CustomNotification::codeInvitationUses($userParent, Auth::user(), $this->getValue('invitation_invitee_reward'));

        return Common::apiResponse(true, __('invitation.success'), $request->code, 200);
    }

    // private static function isStopInvitationValid()
    // {
    //     return settings()->get('stop_invite_code');
    // }

    private static function isStopInvitationValid()
    {
        return getSettingCash('invite_code') ?? 0;
    }
    private function getUserByCode(string $code): ?User
    {
        return User::where("uuid", $code)->first();
    }

    private function createInvitation(int $parentId, int $invitedId): UserCodeInvitation
    {
        InvitationWalletHelper::updateInvitationWallet($this->getValue('invitation_host_reward'));
        InvitationWalletHelper::updateInvitationWallet($this->getValue('invitation_invitee_reward'));

        InvitationEarningHelper::addEarning(
            parentId: $parentId,
            userId: $invitedId,
            sourceType: 'first_join_reward_host',
            amount: $this->getValue('invitation_host_reward')
        );
        InvitationEarningHelper::addEarning(
            parentId: $parentId,
            userId: $invitedId,
            sourceType: 'first_join_reward_invitee',
            amount: $this->getValue('invitation_invitee_reward')
        );

        return UserCodeInvitation::create([
            "user_id"    => $parentId,
            "invited_id" => $invitedId,
        ]);
    }

    private function getValue(string $key, $default = 0)
    {
        return Config::where('name', $key)->value('value') ?? $default;
    }
    private function isDeviceUniqueForUser(int $userId, ?string $deviceToken): bool
    {
        if (!$deviceToken) {
            return true;
        }

        return !UserDevicesHistory::where('device_token', $deviceToken)
            ->where('user_id', '!=', $userId)
            ->exists();
    }

    public function CreateCodeInvitation()
    {
        $user_id       = Auth::id();
        $generatedCode = random_int(1, 100000);
        $existingCode  = UserCodeInvitation::where('code', $user_id . $generatedCode)->exists();
        if ($existingCode) {
            $generatedCode = random_int(1, 100000);
        }
        $data = UserCodeInvitation::create([
            "user_id" => $user_id,
            "code"    => $user_id . $generatedCode,
        ]);
        return Common::apiResponse(true, 'تم انشاء الكود', $data->code, 200);
    }

    public function userLevel(Request $request)
    {
        $trashed = $this->userService->userLevel($request->per_page, $request->Page, $request->uuid);
        return Common::apiResponse(true, 'success', LevelUserResource::collection($trashed));
    }

    public function updateUserLevel($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'total_sender_level'         => 'required|numeric',
            'total_received_level'         => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $this->userService->updateUserLevel($id, $request);
        return Common::apiResponse(true, ' updated successfully');
    }

    public function userLevelHistory(Request $request)
    {
        $data = $this->userService->levelHistory($request->per_page, $request->page);
        return Common::apiResponse(true, ' successfully', UserLevelHistoryResource::collection($data));
    }

    public function usersDeviceToken(Request $request)
    {
        $data = $this->userService->userDeviceToken($request->per_page, $request->Page, $request->device_token, $request);
        return Common::apiResponse(true, 'success', DeviceTokenResource::collection($data));
    }


    public function deleteDeviceToken($id)
    {
        try {
            $this->userService->deleteDeviceToken($id);
            return Common::apiResponse(true, 'delete successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function usersTarget(Request $request)
    {
        $data = $this->userService->usersTargets($request->per_page, $request->Page);
        return Common::apiResponse(true, 'success', UserTargetResource::collection($data));
    }

    public function allUsers(Request $request)
    {
        $users = $this->userService->allUser($request->per_page, $request->Page, $request->family_id, $request->agency_id, $request->search, $request->host);
        return Common::apiResponse(true, 'done', AllUsersResource::collection($users));
    }

    public function kickAgency($id)
    {
        try {
            $this->userService->kickAgency($id);
            return Common::apiResponse(true, 'removed');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function kickFamily($id)
    {
        try {
            $this->userService->kickFamily($id);
            return Common::apiResponse(true, 'removed');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function changeAgency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'         => 'required|integer|exists:users,id',
            'agency_id'         => 'required|integer|exists:agencies,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->userService->changeAgency($request);
            return Common::apiResponse(true, 'changed');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateSwitch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'key' => 'required|string|in:charge_status,transfer_salary,can_play',
            'value'   => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    if (in_array($request->key, ['charge_status', 'transfer_salary']) && !in_array($value, [0, 1])) {
                        $fail(__('The :attribute must be a boolean value for charge_status or transfer_salary.'));
                    }

                    if ($request->key === 'can_play' && !in_array($value, [2, 3])) {
                        $fail(__('The :attribute must be either 2 or 3 when the setting is can_play.'));
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->userService->updateSwitch($request);
            return Common::apiResponse(true, 'changed');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }



    public function updateUserSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'         => 'required|integer|exists:users,id',
            'key' => 'required|string|in:hide_chat,show_invite_code',
            'value' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->userService->updateUserSetting($request);
            return Common::apiResponse(true, 'changed');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }


    public function create(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'uuid'         => 'required',
            'name'         => 'required|string',
            'nickname'         => 'nullable|string',
            'charge_status'         => 'required|boolean',
            'transfer_salary'         => 'required|boolean',
            'can_play'         => 'required|integer|in:0,2,3',
            'country_id'         => 'nullable|integer|exists:countries,id',
            'di'    => 'nullable|integer',
            'user_diamond' => 'nullable|integer',
            'total_sender_level' => 'nullable|integer',
            'total_received_level' => 'nullable|integer',
            'salary' => 'nullable|integer',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'facebook_id' => 'nullable',
            'google_id' => 'nullable',
            'huawei_id' => 'nullable',
            'status' => 'required|boolean',
            'type_user' => 'required|integer',
            'manger_type_id' => 'nullable',
            'avatar' => 'nullable',
            'image_id' => 'nullable',
            'gender' => 'nullable',
            'show_invite_code'         => 'required|boolean',
            'hide_chat'         => 'required|boolean',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->userService->create($request);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function showDataUser($id)
    {

        try {
            $user  = $this->userService->showDataUser($id);
            return Common::apiResponse(true, 'done', new ShowUserResource($user));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateDataUser($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid'         => [
                'required',
                'exists:users,uuid',
                Rule::unique('users', 'uuid')->ignore($id),
            ],
            'name'         => 'required|string',
            'charge_status'         => 'required|boolean',
            'transfer_salary'         => 'required|boolean',
            'can_play'         => 'required|integer|in:0,2,3',
            'country_id'         => 'nullable|integer|exists:countries,id',
            'user_diamond' => 'nullable|integer',
            'total_sender_level' => 'nullable|integer',
            'total_received_level' => 'nullable|integer',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'status' => 'required|boolean',
            'type_user' => 'required|integer',
            'manger_type_id' => 'nullable',
            'avatar' => 'nullable',
            'image_id' => 'nullable',
            'gender' => 'nullable',
            'show_invite_code'         => 'required|boolean',
            'hide_chat'         => 'required|boolean',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->userService->update($id, $request);
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allCodes(Request $request)
    {
        $data = $this->userService->allCods($request->id, $request->per_page, $request->page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function userType()
    {
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => (object) UserType::list(),
        ]);
    }

    public function myData($id)
    {
        try {
            $user  = $this->userService->showDataUser($id);

            return Common::apiResponse(true, 'done', new MyDataUtdResource($user));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function userSalaryWithHisAgency($id, Request $request)
    {
        try {
            $data = $this->userService->userSalary($id, $request->month, $request->year);
            return Common::apiResponse(true, 'done',  $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function userPacksAndVip($id)
    {
        try {
            $data = $this->userService->userPacksAndVip($id);
            return Common::apiResponse(true, 'done', new UserPackVipResource($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function userPacks($id, Request $request)
    {
        try {
            $data = $this->userService->userPacks($request->type, $id, $request->per_page, $request->page);
            return Common::apiResponse(true, 'done',  UserPackUtdResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function userVip($id)
    {
        try {
            $data = $this->userService->userPacksAndVip($id);
            return Common::apiResponse(true, 'done', UserVipUtdResource::collection($data->userHaveVip));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function userVisitRooms($id)
    {
        $data = $this->userService->VisitRoom($id);
        return Common::apiResponse(true, 'done', UserVisitRoomResource::collection($data));
    }

    public function allCpUser($id)
    {
        $data = $this->userService->allUserCp($id);
        return Common::apiResponse(true, 'done', CpUserResource::collection($data));
    }

    public static function by_user_filter()
    {
        $ops = [0 => 'no agency'];
        $app_owner_id = Agency::query()->where('status', 1)->pluck('app_owner_id');
        $users = User::whereIn('id', $app_owner_id)->pluck('name', 'id');
        foreach ($users as $id => $name) {
            $ops[$id] = $name;
        }
        return $ops;
    }

    public function updateGame(Request $request)
    {
        $this->userService->updateGame($request->user()->id);
        return Common::apiResponse(true, 'done', [], 200);
    }

    public function allUsersPlayGame()
    {
        $data = $this->userService->allUsersPlayGame();
        return Common::apiResponse(true, 'done', UserPlayResource::collection($data));
    }

    public function online()
    {
        $data = $this->userService->online();
        return Common::apiResponse(true, 'done', OnlineResource::collection($data));
    }

    public function friends(): JsonResponse
    {
        $friends = $this->userService->friends();

        return Common::apiResponse(true, 'done', UserResource::collection($friends));
    }

    public function sendPack(Request $request)
    {
        $user = $request->user();
        return $this->userService->sendPack($user, $request);
    }

    public function userLevels(Request $request)
    {
        $user         = $request->user();
        $data = $this->userService->userChargeLevel($user);
        return Common::apiResponse(true, 'success', $data);
    }

    //    public function dataUser(Request $request)
    //    {
    //        $id = $request->id;
    //        if (!$id) return Common::apiResponse(0, __('api_responses.validation_error'), 400);
    ////        $data = $this->userService->dataUser($id);
    //        $data = Cache::remember("user_data_{$id}",600, function () use ($id) {
    //            $user = $this->userService->dataUser($id);
    //            return new DataUserResource($user);
    //        });
    //
    //        request()->merge(['user_id' => $id]);
    //        return Common::apiResponse(true, 'done', $data);
    //    }

    public function dataUser(Request $request)
    {
        // id arrives as a string; cast to int so findOrFail(int) never gets a non-numeric string (TypeError)
        $id = (int) $request->id;
        if (!$id) return Common::apiResponse(0, __('api_responses.validation_error'), 400);

        return Cache::remember("data_user_{$id}", 600, function () use ($id) {
            $data = $this->userService->dataUser($id);
            request()->merge(['user_id' => $id]);
            return Common::apiResponse(true, 'done', new DataUserResource($data));
        });
    }

    public function userLevelDetails(Request $request)
    {
        $user = $request->user()->fresh();

        $currentLevel = $user->senderLevel;
        $expLevel = $user->total_sender_diamonds;

        if ($currentLevel) {
            $secondLevel = Vip::where('type', 2)
                ->where('level', '>', $currentLevel->level)
                ->orderBy('level')
                ->first();
        } else {
            $secondLevel = Vip::where('type', 2)->orderBy('level')->first();
        }

        $expPercentages = \Illuminate\Support\Facades\Config::get('exp_percentages') ?? [1, 1];
        $multiplier = $expPercentages['exp_sender_percentage'] ?? 0.2;

        if ($secondLevel != null && $currentLevel != null) {
            $currentExp = $expLevel * $multiplier;

            $remaining = max(0, ($secondLevel?->exp ?? 0) - $currentExp);
            $exactlyValue = @$secondLevel?->exp;
            $progressCurrent = max(0, $currentExp - ($currentLevel->exp ?? 0));
            $progressNext = max(1, ($secondLevel->exp ?? 0) - ($currentLevel->exp ?? 0));

            $prog = $progressNext != 0 ? ($progressCurrent / $progressNext) : 0;

            if ($prog >= 1) {
                $bar = 1;
            } else {
                $bar = round($prog, 1);
            }
            $progress = $exactlyValue == 0 ? 1 : $bar;
        } elseif ($currentLevel != null) {
            $exactlyValue = $secondLevel?->exp ?? 0;
            $progressCurrent = $expLevel - $currentLevel?->exp ?? 0;
            $progressNext = @$secondLevel?->exp - $currentLevel?->exp ?? 0;

            $progress = 1;
            $remaining = 0;
        } else {
            $progress = 1;
            $remaining = 0;
        }
        $vipsData = DB::table('vips')->get()->groupBy('type');
        $gold_level = $user->total_sender_level ?? 0;
        $diamondSend = $user->total_sender_diamonds ?? 0;
        $current_gold_num = Common::getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);
        $sender_div = max(1, ($nextGoldData['next_exp'] ?? 1) - $current_gold_num);
        $senderNum = floor($diamondSend * ($expPercentages['exp_sender_percentage'] ?? 1));
        $per = min(1, max(0, ($senderNum - $current_gold_num) / $sender_div));

        $data = [
            'receiver_img' => $user->receiverLevel?->img ?? '',
            'exp_receiver' => $user->receiverLevel?->exp ?? 0,
            'sender_img' => $user->senderLevel?->img ?? '',
            'sender_level' => intval($user->senderLevel?->level),
            'next_sender_level' => intval($user->next_sender_level_info['next_level'] ?? 0),
            'remaining_to_next_level' => $remaining ?? 0,
            'sender_per' => round(Common::userLevelPer($user), 6),  // 6 decimals for high levels
        ];
        return Common::apiResponse(true, 'success', $data);
    }




    public function syncBD()
    {
        $result = $this->userService->syncBDUsers();
        return response()->json($result);
    }




    public function stats(Request $request, $id = null)
    {
        return TryCatchHelper::handle(function () use ($id, $request) {
            return $this->userService->getUserStats($id ?? $request->user()->id);
        });
    }

    public function rooms(Request $request, $id = null)
    {
        return TryCatchHelper::handle(function () use ($id, $request) {
            $userId = $id ?? $request->user()->id;
            // Cache for 5 minutes (300 seconds) to fix 3s-4s latency reported in DevOps report
            // User rooms don't change frequently
            return Cache::remember("user_rooms_{$userId}", 300, function () use ($userId) {
                return $this->userService->getUserRooms($userId);
            });
        });
    }

    public function vipLevel(Request $request, $id = null)
    {
        return TryCatchHelper::handle(function () use ($id, $request) {
            return $this->userService->getUserVipLevel($id ?? $request->user()->id);
        });
    }

    public function frames(Request $request, $id = null)
    {
        return TryCatchHelper::handle(function () use ($id, $request) {
            return $this->userService->getUserFrames($id ?? $request->user()->id);
        });
    }

    public function invitationsEarnings(Request $request)
    {
        return TryCatchHelper::handle(function () use ($request) {
            $parentId = $request->user()->id;
            return $this->userService->getEarningsForParent($parentId);
        });
    }

    public function invitationsEarningsClaim(Request $request, int $id)
    {
        return TryCatchHelper::handle(function () use ($request, $id) {
            $parentId = $request->user()->id;
            return $this->userService->claimEarning($parentId, $id);
        });
    }

    public function invitationSummary(Request $request)
    {
        return TryCatchHelper::handle(function () use ($request) {
            return $this->userService->getInvitationSummary($request->user()->id);
        });
    }

    public function invitationExtract(Request $request)
    {
        return TryCatchHelper::handle(function () use ($request) {
            return $this->userService->extractInvitationEarnings($request->user()->id);
        });
    }

    public function invitationBonusClaim(Request $request)
    {
        return TryCatchHelper::handle(function () use ($request) {
            return $this->userService->claimInviteeBonus($request->user()->id);
        });
    }
}
