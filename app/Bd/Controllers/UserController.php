<?php

namespace App\Bd\Controllers;

use App\Admin\Controllers\MainController;
use App\Admin\Services\AgencyService;
use App\Admin\Services\UserService;
use App\Models\UserCoinLog;
use Carbon\Carbon;
use App\Models\Pack;
use App\Models\User;
use App\Models\Agency;
use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\Country;
use App\Models\GiftLog;
use Modules\Vip\Entities\UserVip;
use App\Models\UserSallary;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use App\Admin\Widgets\InfoBox;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use Illuminate\Validation\Rule;
use App\Admin\Forms\ProfileForm;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use App\Models\ChangeLevelHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Admin\Selectable\ImageColors;
use Illuminate\Support\Facades\Cache;
use App\Admin\Actions\DeletePackAction;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use App\Admin\Actions\ChangeAgencyAction;
use App\Admin\Actions\ChargeSwitchAction;
use App\Admin\Actions\InviteSwitchAction;
use App\Admin\Actions\KickOfAgencyAction;
use App\Admin\Actions\KickOfFamilyAction;
use App\Admin\Actions\CanPlaySwitchAction;
use App\Admin\Actions\DeleteUserVipAction;
use App\Admin\Actions\EditPackExpireAction;
use App\Models\Profile;
use App\Models\UsersJoinedAgency;

class UserController extends MainController
{
    public $permission_name = 'users';
    public $hiddenColumns = [
        'is_host',
        'status',
        'is_gold_id',
        'id',
        'email',
        'phone',
        'di',
        'gold',
        'coins',
        'actions'
    ];
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = 'Users';
    }

    public function show($id, Content $content,)
        {

            $month = request('month'); // e.g., "5" for May
            $year = request('year');
            $start = request('start_at');
            $end = request('end_at');
            $tab = request('tab') ?? 'salary';
            $joinDate = request('join_date');

            $bdId = Auth::id();
            // Contract: a BD sees only users belonging to its own agencies, never the
            // whole country. The prior orWhere('country_id', ...) widened this to any
            // user in the BD's country; removed so the scope stays agency-only.
            $user = User::with('profile')
                ->where('id', $id)
                ->whereIn('agency_id', Agency::where('bd_id', $bdId)->select('id'))
                ->first();

            if (!$user) {
                abort(404);
            }

            $type = request('type') ?? 4;
            $agencyId = request('agency_id');

            $userJoinAgencies = UsersJoinedAgency::where('user_id', $id)->with('agency')->when(isset($joinDate), function ($query) use ($joinDate) {
                $query->whereDate('join_date', $joinDate);
            })->paginate(10, ['*'], 'user_agency_page');

            $packs = Pack::where('user_id', $id)->where('type', $type)->with('admin', 'userVip')->whereHas('ware')->with(['ware' => function ($q) {
                $q->select('id', 'show_img');
            }])->orderByDesc('is_used')->paginate(10, ['*'], 'pack_page');
            $userVips = UserVip::where('user_id', $id)->paginate(10, ['*'], 'vip_page');
            $hasVip = UserVip::where('user_id', $id)
                ->where('is_used', 1)
                ->exists();

            $salaries = UserSallary::where('user_id', $id)
                ->with('agency')
                ->when(isset($year), function ($query) use ($year) {
                    $query->where('year', $year);
                })->when(isset($month), function ($query) use ($month) {
                    $query->where('month', $month);
                })->orderByDesc('id')->paginate(10, ['*'], 'salary_page');

            $typeMap = PACK_USER;

            $types =  collect($typeMap);
            $userPackTypes = Pack::where('user_id', $id)->pluck('type')->unique()->toArray();
            // $userPackTypes = $this->typesByLevel($id);
            $currentType = request()->get('type', $types->keys()->first());
            if ($userPackTypes) {
                $types = collect($typeMap)->filter(function ($name, $key) use ($userPackTypes) {
                    return in_array($key, $userPackTypes);
                });
            } else {
                $types = $types;
            }
            $chargeTabType = request()->get('type', 'receiver');
            $giftType = request()->get('gift_type', 'receiver');

            $charges = Charge::query()
                ->when($chargeTabType == 'receiver', function ($q) use ($id) {
                    $q->where('user_id', $id)->where('user_type', 'user');
                })
                ->when($chargeTabType == 'charger', function ($q) use ($id) {
                    $q->where('charger_id', $id)->where('charger_type', 'user');
                })
                ->with(Common::chargerRelationsQuery())
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'charges_page');


            $giftSLogs = GiftLog::when($giftType == 'receiver', function ($q) use ($id) {
                $q->where('receiver_id', $id);
            })->when($giftType == 'sender', function ($q) use ($id) {
                $q->where('sender_id', $id);
            })->with('receiver', 'sender', 'gift', 'room', 'agency')->when(isset($start) && isset($end), function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [
                    Carbon::parse($start)->startOfDay(),
                    Carbon::parse($end)->endOfDay()
                ]);
            })->when(isset($agencyId), function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->orderByDesc('id')->paginate(10, ['*'], 'gift_page');

            $diamonds = GiftLog::when($giftType == 'receiver', function ($q) use ($id) {
                $q->where('receiver_id', $id);
            })->when($giftType == 'sender', function ($q) use ($id) {
                $q->where('sender_id', $id);
            })->when(isset($start) && isset($end), function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [
                    Carbon::parse($start)->startOfDay(),
                    Carbon::parse($end)->endOfDay()
                ]);
            })->when(isset($agencyId), function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })->selectRaw('SUM(giftPrice) AS total')->value('total');

            $userJoinAgencies = UsersJoinedAgency::with(['kickedByApp', 'kickedByAdmin'])->where('user_id', $id)->with('agency')->when(isset($joinDate), function ($query) use ($joinDate) {
                $query->whereDate('join_date', $joinDate);
            })->orderByDesc('id')->paginate(10, ['*'], 'user_agency_page');

            \DB::enableQueryLog(); 

            $usersCoins = UserCoinLog::where('user_id', $id)
                ->when(request('from_date'), fn($q) => $q->whereDate('from_date', '>=', request('from_date')))
                ->when(request('to_date'), fn($q) => $q->whereDate('to_date', '<=', request('to_date')))
                ->when(request('sub_type'), fn($q) => $q->where('sub_type', request('sub_type')))
                ->orderByDesc('id')->paginate(10, ['*'], 'coins_page');

                session(['back_url' => url()->previous()]);


            $countries = $this->countries();
            $data = compact('user', 'packs', 'userVips', 'salaries', 'userJoinAgencies', 'types', 'currentType', 'charges', 'tab', 'chargeTabType', 'giftSLogs', 'giftType', 'diamonds', 'hasVip', 'usersCoins', 'countries');
            return $content
                ->title(__('user profile'))
                ->view('bd_user_profile', $data
                );
        }

        public function countries()
        {
            $ops       = [null => __('no country')];
            $countries = Country::all();
            foreach ($countries as $country) {
                $ops[$country->id] = App::isLocale('en') ? $country->e_name : $country->name;
            }
            return $ops;
        }
    
}