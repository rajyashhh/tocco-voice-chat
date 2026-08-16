<?php

namespace App\Admin\Controllers;

use App\Models\Bd;
use Carbon\Carbon;
use App\Models\Pack;
use App\Models\User;
use App\Models\Agency;
use App\Models\Charge;
use App\Models\Setting;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Helpers\Common;
use App\Models\Country;
use App\Models\GiftLog;
use App\Models\Profile;
use App\Models\UserCoinLog;
use App\Models\UserSallary;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use Illuminate\Validation\Rule;
use App\Admin\Forms\ProfileForm;
use Encore\Admin\Layout\Content;
use App\Models\UsersJoinedAgency;
use Encore\Admin\Auth\Permission;
use Modules\UsersWallet\Entities\WalletLog;
use Modules\Vip\Entities\UserVip;
use App\Models\ChangeLevelHistory;
use Illuminate\Support\Facades\DB;
use App\Admin\Services\UserService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Admin\Services\AgencyService;
use Illuminate\Support\Facades\Cache;
use Modules\Badge\Entities\UserBadge;
use Illuminate\Support\Facades\Redirect;
use App\Admin\Actions\ChangeAgencyAction;
use App\Admin\Actions\ChargeSwitchAction;
use App\Admin\Actions\InviteSwitchAction;
use App\Admin\Actions\KickOfAgencyAction;
use App\Admin\Actions\KickOfFamilyAction;

class UserController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
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

    public function index0(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-users');
        }


        $forms = [
            'one' => ProfileForm::class,
            'tow' => ProfileForm::class,
        ];

        return $content
            ->title(__($this->title))
            ->body(Tab::forms($forms));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__($this->title))
            ->body($this->form()->edit($id)));
    }

    public function close_open_gift(Request $request)
    {
        if (!Admin::user()->can('*')) {
            abort(403);
        }

        $value = $request->make_rooms_top == "true" ? "1" : "0";

        \App\Helpers\Common::withMoneyKeyWrite(fn() => settings()->set("close_open_gifts", $value));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__($this->title))
            ->body($this->form()));
    }

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-users');
        }

        $content = $content->title(__($this->title));

        // Add the second row unconditionally
        $content = $content->row(function ($row) {
            $row->column(12, $this->grid());
        })->row(view('admin.same_device_users_modal'));

        return $content;
    }


    protected function grid()
    {
        $countryID = Common::filterCountryIds();

        $grid = new Grid(new User());
        $haveCoins = (request()->have_coins == 1);

        $grid->model()
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->select(['id', 'name', 'sender_level', 'received_level', 'device_token', 'agency_id', 'family_id', 'uuid', 'special_id', 'di', 'can_play', 'huawei_version', 'android_version', 'ios_version', 'country_id', 'transfer_salary', 'is_bd'])
            ->withCount(['sameDeviceUsers' => fn($q) => $q->whereNotNull('device_token')])
            ->with([
                'profile',
                'agency',
                'userSetting',
                'country',
                'senderLevel',
                'receiverLevel',
                'monthlyDiamondReceive',
                'shippingAgency',
                'packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ]);

        if (request()->signups == 'today') {
            $grid->model()->whereDate('created_at', today());
        }

        if (request()->signups == 'week') {
            $grid->model()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        if (request()->signups == 'month') {
            $grid->model()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        if (request()->has('messages') && request()->messages == 'today') {
            $grid->model()->whereHas('chatMessages', fn($q) => $q->whereDate('created_at', today())->where('status', 'sent'));
        }

        if (request()->has('messages') && request()->messages == 'month') {
            $grid->model()->whereHas(
                'chatMessages',
                fn($q) => $q->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('status', 'sent')
            );
        }

        if (request()->never_send == 1) {
            $grid->model()->doesntHave('chatMessages');
        }

        if (request()->sent_messages == 1) {
            $grid->model()->has('chatMessages');
        }

        if (request()->agencyMembers == 1) {
            $grid->model()->where('agency_id', '!=', 0)
                ->whereHas('agency', function ($q) use ($countryID) {
                    $q->where('country_id', $countryID);
                });
        }

        if (request()->online == 1) {
            $grid->model()->where('online', 1);
        } else if ($haveCoins) {
            $grid->model()->where('di', '>', 0)->orderByDesc('di');
        } else {
            $grid->model()->orderByDesc('id');
        }

        // ─── Quick Search ───
        $grid->quickSearch();

        // ─── Filters ───
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->where('family_id', $input)
                        ->orWhereHas('family', function ($q) use ($input) {
                            $q->where('name', 'like', "%{$input}%");
                        });
                }, __('Family ID or Name'));
                $filter->column(1 / 2, function ($filter) {
                    $filter->where(function ($query) {
                        $input = $this->input;
                        $query->where(function ($q) use ($input) {
                            $q->where('name', 'like', "%$input%")
                                ->orWhere('uuid', 'like', "%$input%")
                                ->orWhere('special_id', 'like', "%$input%")
                                ->orWhere('nickname', 'like', "%$input%")
                                ->orWhere('email', 'like', "%$input%");
                        });
                    }, __('User'))->placeholder(__('Search by name , UUID , nickname and email'));

                    $filter->equal('UserVip.vip_id', __('vip'))->select(Common::by_ovip_filter());
                });

                $filter->column(1 / 2, function ($filter) {
                    $locale = app()->getLocale();
                    $column = $locale === 'ar' ? 'name' : 'e_name';
                    $countries = \App\Models\Country::query()->pluck($column, 'id');

                    $filter->where(function ($query) {
                        if ($this->input) {
                            $query->where('country_id', $this->input);
                        }
                    }, __('Country'))->select($countries);
                });
            });
        });

        // ─── Columns ───
        $grid->column('id', __('Id'))->display(function ($value) {
            return "<span class='ug-id-badge'>{$value}</span>";
        });

        if ($haveCoins) {
            $grid->column('di', __('coins'))->display(function ($value) {
                $formatted = number_format($value);
                $color = $value > 10000 ? '#10b981' : ($value > 1000 ? '#f59e0b' : '#6b7280');
                return "<div class='ug-coins'>
                            <i class='fa fa-diamond' style='color:{$color};margin-right:4px;'></i>
                            <span style='color:{$color};font-weight:700;'>{$formatted}</span>
                        </div>";
            });
        }

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this);
        });

        $grid->column('agency_id', __('Agency'))
            ->display(function () {
                $agency = $this->agency;
                if (!$agency) {
                    return '<span class="ug-no-agency"><i class="fa fa-minus-circle"></i> ' . __('None') . '</span>';
                }
                return app(AgencyService::class)->adminAgencyData($agency);
            });

        $grid->column('shipping_agency_id', __('shipping agency'))
            ->display(function () {
                $agency = $this->shippingAgency;
                if (!$agency) {
                    return '<span class="ug-no-agency"><i class="fa fa-minus-circle"></i> ' . __('None') . '</span>';
                }
                return app(AgencyService::class)->adminShippingAgencyData($agency);
            });

        $grid->column('custom_button2', __('accounts number'))->display(function () {
            $count = (int) ($this->same_device_users_count ?? 0);
            $badgeClass = $count > 1 ? 'ug-device-warn' : 'ug-device-ok';
            $icon = $count > 1 ? 'fa-exclamation-triangle' : 'fa-mobile';
            return "<button class='ug-device-btn {$badgeClass} show-same-device-modal' data-user-id='{$this->id}'>
                        <i class='fa {$icon}'></i>
                        <span class='ug-device-count'>{$count}</span>
                    </button>";
        });

        $grid->column('versions', __('versions'))->display(function () {
            $platforms = [
                'Android' => $this->android_version,
                'iOS' => $this->ios_version,
                'Huawei' => $this->huawei_version,
            ];

            $rows = '';
            foreach ($platforms as $label => $version) {
                if (empty($version)) {
                    continue;
                }
                $version = e($version);
                $rows .= "<span class='ug-version-badge'>{$label} <b>{$version}</b></span>";
            }

            if ($rows === '') {
                return "<span class='ug-no-agency'>—</span>";
            }

            return "<div class='ug-versions'>{$rows}</div>";
        });

        Admin::style('
            .ug-versions { display:flex; flex-direction:column; gap:2px; }
            .ug-version-badge {
                display:inline-block; font-size:11px; line-height:1.4;
                background:#f3f4f6; color:#374151; border-radius:4px;
                padding:1px 6px; white-space:nowrap;
            }
            .ug-version-badge b { color:#111827; }
        ');

        $permission = $this->permission_name;

        // ─── Inject all styles ───
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        // ─── Script for same-device modal ───
        Admin::script("
            $(document).on('click', '.show-same-device-modal', function() {
                var userId = $(this).data('user-id');
                loadSameDeviceUsers(userId, 1);
            });

            $(document).on('click', '.ajax-pagination', function(e) {
                e.preventDefault();
                var userId = $(this).data('user-id');
                var page = $(this).data('page');
                if (!$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                    loadSameDeviceUsers(userId, page);
                }
            });

            function loadSameDeviceUsers(userId, page) {
                $('#sameDeviceUsersModal .modal-body').html('<div class=\"ug-loader\"><i class=\"fa fa-spinner fa-spin fa-2x\"></i><p>" . __('Loading') . "...</p></div>');
                $('#sameDeviceUsersModal').modal('show');
                $.get('/admin/users/' + userId + '/same-device-users-table', { page: page }, function(html) {
                    $('#sameDeviceUsersModal .modal-body').html(html);
                });
            }

            // Copy to clipboard fallback
            if (typeof copyToClipboard === 'undefined') {
                window.copyToClipboard = function(elemId) {
                    var text = document.getElementById(elemId).textContent;
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text).then(function() {
                            toastr.success('" . __('Copied!') . "');
                        });
                    } else {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        toastr.success('" . __('Copied!') . "');
                    }
                };
            }
        ");

        // ─── Actions ───
        $grid->actions(function ($actions) use ($permission) {
            $model = $actions->row;

            if (Admin::user()->can('charge-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new ChargeSwitchAction());
            }
            if (Admin::user()->can('invite-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new InviteSwitchAction());
            }
            if (Admin::user()->can('can-Play-switch-' . $permission) || Admin::user()->can('*')) {
                $row = $actions->row;
                $actions->add(new \App\Admin\Actions\CanPlaySwitchAction($row['can_play']));
            }
            if ($model->agency_id >= 1 && (Admin::user()->can('kick-agency-switch-' . $permission) || Admin::user()->can('*'))) {
                $actions->add(new KickOfAgencyAction());
            }
            if ($model->family_id >= 1 && (Admin::user()->can('kick-family-switch-' . $permission) || Admin::user()->can('*'))) {
                $actions->add(new KickOfFamilyAction());
            }
            if ($model->agency_id >= 1 && (Admin::user()->can('chang-agency-switch-' . $permission) || Admin::user()->can('*'))) {
                $actions->add(new ChangeAgencyAction($model->id));
            }
            if ($model->phone == '+201000100010') {
                $actions->disableDelete();
            }
            if (!Admin::user()->can('delete-' . $permission) && !Admin::user()->can('*')) {
                $actions->disableDelete();
            }
            if (!Admin::user()->can('edit-' . $permission) && !Admin::user()->can('*')) {
                $actions->disableEdit();
            }
            if (!Admin::user()->can('show-' . $permission) && !Admin::user()->can('*')) {
                $actions->disableView();
            }
        });

        if (config('app.env') == 'production') $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();

        // Add Cleanup Duplicate Devices button
        $grid->tools(function ($tools) {
            $tools->append('
                <a href="/admin/cleanup-duplicate-devices/preview" class="btn btn-warning btn-sm" style="margin-left:5px;">
                    <i class="fa fa-trash"></i> تنظيف الحسابات المكررة
                </a>
            ');
        });

        return $grid;
    }


    public function stop_charge(Request $request)
    {
        if (!Admin::user()->can('*')) {
            abort(403);
        }

        $value = $request->stop_charge == "false" ? "0" : "1";

        \App\Helpers\Common::withMoneyKeyWrite(fn() => Setting::updateOrCreate(
            ['key' => 'stop_charge'],
            ['value' => $value]
        ));

        Cache::forget('stop_charge');
    }

    public function make_rooms_top(Request $request)
    {
        $value = $request->make_rooms_top == "true" ? "1" : "0";
        settings()->set("make_rooms_top", $value);
        Cache::forever('rooms_make_rooms_top', $value);
    }

    public function transferSalary(Request $request)
    {
        if ($request->transfer_salary == "true") {
            settings()->set("transfer_salary", "1");
        } else {
            settings()->set("transfer_salary", "0");
        }
    }


    protected function showColSearch()
    {
        $form = new Box();
        $form->view('admin.grid.users.userChargeView');

        return $form;
    }

    public function update($id)
    {

        unset(request()['level']);
        unset(request()['worth']);


        return $this->form()->update($id);
    }



    public function show($id, Content $content)
    {
        $timezone   = Common::timeZone();
        $month      = request('month');
        $year       = request('year');
        $start      = request('start_at');
        $end        = request('end_at');
        $joinDate   = request('join_date');
        $type       = request('type', 4);
        $agencyId   = request('agency_id');
        $chargeTabType = request('type', 'receiver');
        $giftType   = request('gift_type', 'receiver');
        $packs = null;
        $types = collect();
        $currentType = null;
        $userVips = $hasVip = null;
        $salaries = null;
        $charges = null;
        $giftSLogs = $diamonds = null;
        $totalGiftPrice = 0;
        $actualGiftPrice = $luckyGiftTotal = $regularGiftTotal = 0;
        $userJoinAgencies = null;
        $usersCoins = null;
        $badges = null;
        $walletLogs = null;
        $totalGiftCoins = 0;

        
        // Decide active tab early so we only eager load what we need
        $activeTab = request('tab', 'packs');

        /* =========================
     | USER (ONE QUERY ONLY) — conditional eager loading + select
     ========================= */
        $userQuery = User::query()->select(['id', 'name', 'uuid', 'special_id', 'type_user', 'country_id', 'di', 'email', 'sender_level', 'exchange_diamonds','received_level', 'phone', 'bio', 'total_diamond_send', 'agency_id', 'family_id', 'can_play', 'charge_level', 'transfer_salary', 'online']);
        $with = [
            'images',
            'profile:id,user_id,avatar,gender',
            'country:id,name,flag,language,e_name,phone_code,iso,iso_numeric,currency_numeric',
            'senderLevel:id,level,type,img',
            'receiverLevel:id,level,type,img',
            'userSetting',
            "chargeLevel:id,level,type,img",
        ];

        // Only load packs when viewing packs tab
        if ($activeTab === 'packs') {
            $with['packs'] = function ($q) {
                $q->where('type', 25)
                    ->where('is_used', true)
                    ->where(fn($q) => $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp))
                    ->with('ware:id,value');
            };
        }

        $user = $userQuery->with($with)->findOrFail($id);
        $covers = $user->images;

        // Avoid duplicate wallet calls
        $availableBalance = $curantBalance = wallet_available_by_user($id);
        /* =========================
        | USER IMAGE
        ========================= */
        $defaultImage = asset('images/businessman-icon.jpg');
        $avatar = optional($user->profile)->avatar;
        $user->display_image = isImageExists(getImagePath($avatar)) ? getImagePath($avatar) : $defaultImage;
        // $activeTab already set above
        switch ($activeTab) {

            case 'packs':
                $types = collect(PACK_USER);
                $packBase = Pack::with(['userVip.admin:id,name,avatar', 'admin:id,name,avatar', 'userVip', 'sender', 'ware:id,show_img'])
                    ->where('user_id', $id)
                    ->whereHas('ware')
                    ->whereNull('deleted_at');

                $packs = (clone $packBase)
                    ->where('type', request('type', 4))
                    ->orderByDesc('is_used')
                    ->latest()
                    ->paginate(10, ['*'], 'pack_page');

                $userPackTypes = (clone $packBase)->pluck('type')->unique()->toArray();
                $types = $types->filter(fn($_, $key) => in_array($key, $userPackTypes));
                $currentType = request('type', $types->keys()->first());
                break;

            case 'vips':
                $userVips = UserVip::where('user_id', $id)->paginate(10, ['*'], 'vip_page');
                $hasVip = $userVips->contains('is_used', 1);
                break;

            case 'salary':
                $year = request('year');
                $month = request('month');
                $salaries = UserSallary::with('agency:id,name')
                    ->where('user_id', $id)
                    ->when($year, fn($q) => $q->where('year', $year))
                    ->when($month, fn($q) => $q->where('month', $month))
                    ->orderByDesc('id')
                    ->paginate(10, ['*'], 'salary_page');
                break;

            case 'charge':
                $chargeTabType = request('type', 'receiver');
                $charges = Charge::with(Common::chargerRelationsQuery())
                    ->when($chargeTabType === 'receiver', fn($q) => $q->where('user_id', $id)->where('user_type', 'user'))
                    ->when($chargeTabType === 'charger', fn($q) => $q->where('charger_id', $id)->where('charger_type', 'user'))
                    ->orderByDesc('id')
                    ->paginate(10, ['*'], $chargeTabType === 'receiver' ? 'receiver_page' : 'charger_page');
                break;

            case 'gift-log':
                $giftType = request('gift_type', 'receiver');
                $start = request('start_at');
                $end = request('end_at');
                // Only default agency_id on initial load (no date filter)
                // When user submits filter form, only filter by agency if explicitly selected
                if ($start || $end) {
                    $agencyId = request()->filled('agency_id') ? request('agency_id') : null;
                } else {
                    $agency_id = $giftType === 'receiver' ? $user->agency_id : null;
                    $agencyId = request('agency_id', $agency_id);
                }

                // Convert empty string or "0" to null
                if ($agencyId === '' || $agencyId === '0' || $agencyId === 0) {
                    $agencyId = null;
                }

                $timezone = Common::timeZone();

                // Convert dates to UTC for database query (support single date too)
                $startUtc = $start ? Carbon::parse($start, $timezone)->startOfDay()->utc() : null;
                $endUtc = $end ? Carbon::parse($end, $timezone)->endOfDay()->utc() : null;

                $giftBaseQuery = GiftLog::query()
                    ->when($giftType === 'receiver', fn($q) => $q->where('receiver_id', $id))
                    ->when($giftType === 'sender', fn($q) => $q->where('sender_id', $id))
                    ->when($startUtc && $endUtc, fn($q) => $q->whereBetween('created_at', [$startUtc, $endUtc]))
                    ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId));

             

                $giftSLogs = (clone $giftBaseQuery)
                    ->with([
                        'receiver:id,name,uuid,special_id',
                        'sender:id,name,uuid,special_id',
                        'gift:id,name,price,e_name,img,type,gift_category_id',
                        'gift.category:id,title',
                        'room:id,room_name,room_cover',
                        'agency:id,name',
                    ])
                    ->orderByDesc('id')
                    ->paginate(10, ['*'], 'gift_page');

           

                // For receiver: just sum giftPrice
                // For sender: calculate SUM(total * giftNum)
                if ($giftType === 'receiver') {
                    $totalGiftCoins = (clone $giftBaseQuery)->sum('giftPrice') ?? 0;
                } else {
                    $totalGiftCoins = (clone $giftBaseQuery)
                        ->selectRaw('SUM(CAST(total AS DECIMAL(20,2)) * CAST(giftNum AS DECIMAL(20,2))) as total')
                        ->value('total') ?? 0;
                }

                $diamonds = (clone $giftBaseQuery)->sum('giftPrice');

                break;

            case 'user-agency':
                $joinDate = request('join_date');
                $userJoinAgencies = UsersJoinedAgency::with(['agency:id,name,type', 'kickedByApp:id,name', 'kickedByAdmin:id,name'])
                    ->where('user_id', $id)
                    ->when($joinDate, fn($q) => $q->whereDate('join_date', $joinDate))
                    ->orderByDesc('id')
                    ->paginate(10, ['*'], 'user_agency_page');
                break;

            case 'user-coins':
                $usersCoins = UserCoinLog::where('user_id', $id)
                    ->when(request('from_date'), fn($q) => $q->whereDate('from_date', '>=', request('from_date')))
                    ->when(request('to_date'), fn($q) => $q->whereDate('to_date', '<=', request('to_date')))
                    ->when(request('sub_type'), fn($q) => $q->where('sub_type', request('sub_type')))
                    ->orderByDesc('id')
                    ->paginate(10, ['*'], 'coins_page');
                break;

            case 'badges':
                $badges = UserBadge::with('admin:id,name')
                    ->where('user_id', $id)
                    ->orderByRaw("CASE WHEN expire = 0 THEN 0 WHEN expire >= ? THEN 0 ELSE 1 END", [now()->timestamp])
                    ->orderByDesc('expire')
                    ->paginate(10, ['*'], 'badges_page');
                break;

            case 'wallet_logs':
                $year = request('year');
                $month = request('month');
                $walletLogs = WalletLog::where('user_id', $id)
                    ->when($year, fn($q) => $q->whereYear('created_at', $year))
                    ->when($month, fn($q) => $q->whereMonth('created_at', $month))
                    ->orderByDesc('id')
                    ->paginate(20, ['*'], 'wallet_logs_page')
                    ->appends(['tab' => 'wallet_logs', 'year' => $year, 'month' => $month]);
                break;
        }
        $countries = $this->countries();

        /* =========================
     | VIEW
     ========================= */
        $permission = $this->permission_name;

        $data = compact(
            'user',
            'covers',
            'countries',
            'packs',
            'types',
            'giftType',
            'currentType',
            'userVips',
            'chargeTabType',
            'hasVip',
            'salaries',
            'charges',
            'giftSLogs',
            'diamonds',
            'totalGiftPrice',
            'userJoinAgencies',
            'usersCoins',
            'badges',
            'type',
            'walletLogs',
            'activeTab',
            'availableBalance',
            'curantBalance',
            'totalGiftCoins',
            'permission'
        );

        // For AJAX tab requests (not PJAX), return only the rendered view (no admin layout)
        if (request()->ajax() && request()->has('tab') && !request()->header('X-PJAX')) {
            return view('user_profile', $data)->render();
        }

        return parent::show($id, $content->title(__('user profile'))->view('user_profile', $data));
    }




    public function countries()
    {
        $ops = [null => __('no country')];
        $countries = Country::select('id', 'name', 'e_name')->get();
        foreach ($countries as $country) {
            $ops[$country->id] = App::isLocale('en') ? $country->e_name : $country->name;
        }
        return $ops;
    }

    public static function typesByLevel($id)
    {
        $userVipLevels = UserVip::where('user_id', $id)
            ->with(['OVip.privilegs'])
            ->get()
            ->filter(fn($vip) => $vip->OVip)
            ->groupBy(fn($vip) => $vip->OVip->level);

        $typesByLevel = [];

        foreach ($userVipLevels as $level => $vips) {
            $types = $vips
                ->flatMap(function ($vip) {
                    return $vip->OVip->privilegs->pluck('type');
                })
                ->unique()
                ->values();

            $typesByLevel[$level] = $types->toArray();
        }

        return $typesByLevel;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());
        $this->disableFormTools($form);

        if ($form->isEditing()) {
            $userId = request()->route('user');
            $user = User::findOrFail($userId);
            $oldDiValue = $user->getOriginal('di');
            $oldDiamoundValue = $user->getOriginal('user_diamond');
        } else {
            $oldDiValue = null;
            $oldDiamoundValue = null;
        }


        $loggedInUserId = Admin::user()->id;
        if ($form->isEditing()) {
            $form->display('id', __('id'));
        }

        // $form->hidden('transfer_salary', __('transfer_salary'))->default(0);

        $form->text('name', __('Name'));
        if ($form->isEditing()) {
            $form->hidden('oldDiValue')->default($oldDiValue);
            $form->hidden('oldDiamoundValue')->default($oldDiamoundValue);
        }
        $form->text('original_uuid', __('uuid'))
            ->creationRules([
                'required',
                Rule::unique('users', 'uuid'),
                function ($attribute, $value, $fail) {
                    if (DB::table('wares')->where('value', $value)->exists()) {
                        return $fail(__('لا يمكنك استخدام معرف المميز هذا'));
                    }
                }
            ])
            ->updateRules(['required', "unique:users,uuid,{{id}}"]);

        // $form->switch('is_gold_id', trans('	is_gold_id'))->states (Common::getSwitchStates());

        $form->image('photo', __('profile photo'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        });

        $form->image('profile.image_id', __('identity photo'));
        // stop upload image
        // Admin::script(
        //     <<<'JS'
        //         $(function() {
        //             // For every file/image input (fileinput plugin)
        //             $('.btn-file').hide(); // Hide browse/upload buttons (common Bootstrap Fileinput class)
        //             $('.fileinput-upload').hide(); // Hide upload buttons if present
        //             $('input[type="file"]').prop('disabled', true); // Prevent any file selection

        //         });
        //     JS
        // );

        if (!Admin::user()->can('delete-profile-switch-' . $this->permission_name)) {
            Admin::script(
                <<<JS
                    $(document).ready(function() {
                        $('input[name="photo"]').closest('.form-group').find('.fileinput-remove').hide();
                    });
                JS
            );
        }

        $form->html('<div class="full-column-width">');
        $form->hasMany('images', __('Profile Images'), function ($form) {
            $form->image('img', __('Image'));
        })->useTable()->disableCreate()->disableDelete();
        $form->html('</div>');

        Admin::style('
            .has-many-images .has-many-images-forms {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 20px !important;
            }

            .has-many-images .form-group {
                margin-bottom: 0 !important;
            }

            .has-many-images .file-preview-image {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                border-radius: 8px !important;
            }
        ');

        if (!Admin::user()->can('delete-profile-switch-' . $this->permission_name) && !Admin::user()->can('*')) {
            Admin::script(
                <<<JS
        $(document).ready(function() {
            // Hide 'remove' button on main image
            $('input[name="photo"]').closest('.form-group').find('.fileinput-remove').hide();

            // Hide 'remove' (×/close) button in hasMany images block, try these selectors:
            $('.has-many-images .has-many-remove').hide();
            $('.has-many-images .remove').hide();
            $('.has-many-images .close').hide();
            // Try direct selector as fallback for any <a> close button in hasMany block
            $('.has-many-images a.close').hide();
        });
        JS
            );
        }
        //        $form->multipleImage('images', 'Images');
        //
        //        if (!Admin::user()->can('delete-profile-switch-' . $this->permission_name)) {
        //            Admin::script(
        //                <<<JS
        //        $(document).ready(function() {
        //            $('input[name="images[]"]').closest('.form-group').find('.fileinput-remove').hide();
        //        });
        //        JS
        //            );
        //        }


        $form->select('country_id', trans('country'))->options(function () {
            $ops = [null => __('no country')];
            $countries = Country::all();
            foreach ($countries as $country) {
                $ops[$country->id] = App::isLocale('en') ? ($country->e_name ?? $country->name) : $country->name;
            }
            return $ops;
        });

        $form->select('profile.gender', __('gender'))->options([0 => __('female'), 1 => __('male')]);
        $form->email('email', __('Email'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly');
        $form->password('password', __('Password'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly')->creationRules('required');
        $form->text('phone', __('phone'))->creationRules(['nullable', "unique:users,phone,{{id}}"])->updateRules(['nullable', "unique:users,phone,{{id}}"]);

        $form->saving(function (Form $form) use ($oldDiValue, $oldDiamoundValue) {
            $type_user = request()->type_user;
            $model = $form->model();
            $user_id = $model->id;
            $form->model()->uuid = $form->original_uuid;
            // $user = User::find($user_id);
            // $originalProfile = $user->profile;
            // $newAvatar = request()->input('profile.avatar'); // still okay if tightly coupled

            // if ($originalProfile && $newAvatar && $originalProfile->avatar !== $newAvatar) {
            //     $newCount = $user->profile_count + 1;
            //     $user->profile_count = $newCount;
            //     $user->save();

            //     // Upload and update avatar
            //     $form->model()->profile->avatar =  Common::uploadProfileUser('profile', $newAvatar, $originalProfile->id, $newCount);
            //     // dd($newImagePath);

            // }
            if ($form->oldDiValue != $oldDiValue) {
                $form->di = $oldDiValue;
            }

            if ($form->oldDiamoundValue != $oldDiamoundValue) {
                $form->user_diamond = $oldDiamoundValue;
            }

            $agancy = Agency::where('app_owner_id', $user_id)->first();
            if ($agancy) {


                if (in_array(intval($type_user), [0, 1, 5]) && $model->isDirty('type_user')) {
                    admin()->error(__('يملك هذا المستخدم وكالة. الرجاء مسح الوكالة واخراج المضيفين اولا قبل تغيير نوع المستخدم'));
                    return false;
                }


                switch ($type_user) {


                    case 2:
                        User::where('id', $user_id)->update(['type_user' => 2]);
                        break;
                    case 3:
                        User::where('id', $user_id)->update(['type_user' => 3]);
                        break;
                    case 4:
                        User::where('id', $user_id)->update(['type_user' => 4]);
                        break;

                    default:

                        // dd();

                        break;
                }
            }
        });


        return $form;
    }


    public function request_invite_code(Request $request)
    {
        if (!Admin::user()->can('*')) {
            abort(403);
        }

        $value = $request->stop_invite_code == "true" ? "1" : "0";

        \App\Helpers\Common::withMoneyKeyWrite(fn() => settings()->set("stop_invite_code", $value));

        return true;
    }


    public function deletePack($id)
    {
        $pack = Pack::find($id);

        if (!$pack) {
            return response()->json([
                'status' => 404,
                'message' => __('not_found'),
            ], 404);
        }

        $pack->delete();

        return Redirect::back();
    }

    public function free(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:packs,id',
            'type' => 'required|in:0,1',
            'days' => 'required|integer|min:1',

        ]);

        $pack = Pack::find($request->id);
        $ex = ($request->days ?: 0);
        if (empty($pack->expire)) {
            $pack->days += $ex;
            $pack->save();
            return Redirect::back();
        }

        if ($request->type == 0) {
            $pack->expire += $ex * 86400;
        } else {
            $pack->expire -= $ex * 86400;
        }
        $pack->save();
        return Redirect::back();
    }

    public function deleteUserVip($id)
    {
        $userVip = UserVip::find($id);
        $userVip->packs()->delete();
        $user = User::query()->find($userVip->user_id);
        if ($user) {
            if ($user->vip == $userVip->id) {
                $uvip = UserVip::query()->where('user_id', $user->id)->where('id', '!=', $userVip->id)->orderByDesc('level')->first();
                if ($uvip) {
                    $user->vip = $uvip->id;
                    $user->save();
                }
            }
        }
        $userVip->delete();
        return Redirect::back();
    }


    public function editLevelUser(Request $request)
    {
        $user = User::find($request->id);

        ChangeLevelHistory::create([
            'user_id' => $user->id,
            'admin_id' => Auth::id(),
            'old_total_sender_level' => $user->total_sender_level,
            'new_total_sender_level' => $request->total_sender_level,
            'old_total_received_level' => $user->total_received_level,
            'new_total_received_level' => $request->total_received_level,

        ]);

        $user->total_sender_level = $request->total_sender_level;
        $user->total_received_level = $request->total_received_level;
        $user->save();
        return Redirect::back();
    }


    public function updateUsers(Request $request)
    {
        $user = User::findOrFail($request->id);

        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'uuid' => ['sometimes', Rule::unique('users', 'uuid')->ignore($user->id)],
            'phone' => ['nullable', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'country_id' => ['nullable', 'exists:countries,id'],
            'bio' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:0,1'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        // Update user fields directly from request
        $user->name = $request->input('name', $user->name);
        $user->uuid = $request->input('uuid', $user->uuid);
        $user->email = $request->filled('email') ? $request->input('email') : null;
        $user->phone = $request->filled('phone') ? $request->input('phone') : null;
        $user->bio = $request->input('bio', $user->bio);
        $user->country_id = $request->filled('country_id') ? $request->input('country_id') : null;
        $user->save();


        // Update or create profile
        $profile = $user->profile;
        if (!$profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;
        }

        if ($request->has('gender')) {
            $profile->gender = $request->input('gender');
        }

        if ($request->hasFile('image')) {
            $profile->avatar = Common::upload('images', $request->file('image'));
        }

        $profile->save();

        return Redirect::back();
    }

    // app/Admin/Controllers/UsersAppController.php

    public function ajaxSameDeviceUsersTable($id, Request $request)
    {
        $user = User::findOrFail($id);
        $perPage = 10;
        $page = $request->get('page', 1);

        if (empty($user->device_token)) {
            return '<div class="alert alert-warning text-center">
            This user has no device identifier.
        </div>';
        }

        $users = User::with('profile')
            ->where('device_token', $user->device_token)
            ->paginate($perPage, ['*'], 'page', $page);

        $rows = $users->map(function ($user) {
            $path = $user->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($user->id, $url, 40, 40);

            $nameColumn = "
            <div style='display: flex; align-items: center; gap: 10px;'>
                $image
                <div>
                     <a  style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                        <span cursor: pointer;'>$user->name</span>
                    </a>
                    <span style='color: #aaa; font-size: smaller;'>UUID: $user->uuid</span>
                </div>
            </div>
        ";

            return [
                'name' => $nameColumn,
                'phone' => $user->phone,
                'createdAt' => $user->created_at,
            ];
        });

        $table = new Table([__('Name'), __('phone'), __('created_at')], $rows->toArray());
        $pagination = $this->buildAjaxPagination($users, $id);
        // Return just table's HTML (your AJAX will inject this)
        return $table->render() . $pagination;
    }

    private function buildAjaxPagination($paginator, $userId)
    {
        if ($paginator->lastPage() <= 1) {
            return '';
        }

        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        $html = '<nav aria-label="Page navigation" style="margin-top: 15px;">';
        $html .= '<ul class="pagination justify-content-center">';

        // Previous button
        $prevDisabled = $currentPage == 1 ? 'disabled' : '';
        $prevPage = $currentPage - 1;
        $html .= "<li class='page-item {$prevDisabled}'>";
        $html .= "<a class='page-link ajax-pagination' href='#' data-user-id='{$userId}' data-page='{$prevPage}'>&laquo;</a>";
        $html .= "</li>";

        // Page numbers
        for ($i = 1; $i <= $lastPage; $i++) {
            $active = $i == $currentPage ? 'active' : '';
            $html .= "<li class='page-item {$active}'>";
            $html .= "<a class='page-link ajax-pagination' href='#' data-user-id='{$userId}' data-page='{$i}'>{$i}</a>";
            $html .= "</li>";
        }

        // Next button
        $nextDisabled = $currentPage == $lastPage ? 'disabled' : '';
        $nextPage = $currentPage + 1;
        $html .= "<li class='page-item {$nextDisabled}'>";
        $html .= "<a class='page-link ajax-pagination' href='#' data-user-id='{$userId}' data-page='{$nextPage}'>&raquo;</a>";
        $html .= "</li>";

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    public function removeBD($id)
    {

        Bd::where('app_id', $id)->update(['app_id' => 0]);
        $user = User::findOrFail($id);
        $user->is_bd = 0;
        $user->save();

        return redirect()->back();
    }

    public function deleteBadge($id)
    {
        UserBadge::where('id', $id)->delete();
        return redirect()->back();
    }

    /**
     * Toggle transfer salary for a user (profile action)
     */
    public function toggleTransferSalary($id)
    {
        $user = User::findOrFail($id);
        $user->transfer_salary = !$user->transfer_salary;
        $user->save();

        $msg = $user->transfer_salary
            ? __('Enabled Transfer Salary!')
            : __('Disabled Transfer Salary!');

        return response()->json(['status' => true, 'message' => $msg]);
    }

    /**
     * Toggle invite code visibility for a user (profile action)
     */
    public function toggleInviteCode($id)
    {
        $user = User::findOrFail($id);
        $userSetting = $user->userSetting;

        if (!$userSetting) {
            return response()->json(['status' => false, 'message' => __('User setting not found')], 404);
        }

        $userSetting->show_invite_code = !$userSetting->show_invite_code;
        $userSetting->save();

        $msg = $userSetting->show_invite_code
            ? __('Show invite code has been enabled!')
            : __('Show invite code has been disabled!');

        return response()->json(['status' => true, 'message' => $msg]);
    }

    /**
     * Toggle can play for a user (profile action)
     */
    public function toggleCanPlay($id)
    {
        $user = User::findOrFail($id);
        $user->can_play = $user->can_play == 2 ? 3 : 2;
        $user->save();

        if ($user->online) {
            $can_play = $user->can_play ?? 0;
            $show_invite_code = $user->show_invite_code ?? 0;
            broadcast(new \App\Events\UserStatus(
                $can_play == 2,
                $show_invite_code == 1,
                $user->id
            ));
        }

        $msg = $user->can_play == 2
            ? __('Can play has been enabled!')
            : __('Can play has been disabled!');

        return response()->json(['status' => true, 'message' => $msg]);
    }

    /**
     * Kick user from agency (profile action)
     */
    public function kickAgency($id)
    {
        $user = User::findOrFail($id);

        if (\App\Facades\UserHandling::checkIfUserOwnerOfAgency($user)) {
            return response()->json(['status' => false, 'message' => __('This user is the agency owner and cannot be deleted')], 422);
        }

        \App\Facades\UserHandling::kickUserFromAgency($user);

        return response()->json(['status' => true, 'message' => __('dashboard.successful')]);
    }

    /**
     * Kick user from family (profile action)
     */
    public function kickFamily($id)
    {
        $user = User::findOrFail($id);

        if (\App\Facades\UserHandling::checkIfUserOwnerOfFamily($user->id)) {
            return response()->json(['status' => false, 'message' => __('This User is the host Of family can\'t delete it go to remove family first')], 422);
        }

        $user->family_id = null;
        \App\Models\FamilyUser::where('user_id', $user->id)->delete();
        $user->save();

        return response()->json(['status' => true, 'message' => __('dashboard.successful')]);
    }

    /**
     * Change user agency (profile action)
     */
    public function changeAgency($id, Request $request)
    {
        $request->validate([
            'agency_id' => 'required|exists:agencies,id',
        ]);

        $user = User::findOrFail($id);

        $agencyOwner = Agency::where('owner_id', $id)
            ->orWhere('app_owner_id', $id)
            ->exists();

        if ($agencyOwner) {
            return response()->json(['status' => false, 'message' => __('This user is the agency owner and cannot be deleted')], 422);
        }

        return DB::transaction(function () use ($user, $request) {
            $oldAgencyId = $user->agency_id;

            uploadMonthlyDiamondReceive($user->id, 0);

            // Handle salaries
            $timezone = getTimezone();
            $currentMonth = now($timezone)->month;
            $currentYear = now($timezone)->year;
            $userSalary = UserSallary::where('user_id', $user->id)
                ->where('user_agency_id', $oldAgencyId)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->where('is_finished', 0)
                ->first();
            if ($userSalary) {
                $userSalary->update(['is_finished' => 1]);
            }

            // Clear agency logs
            GiftLog::where('receiver_id', $user->id)
                ->where('agency_id', $oldAgencyId)
                ->update(['is_finished' => 1]);
            \App\Models\AgencyUserJob::where(['user_id' => $user->id, 'agency_id' => $oldAgencyId])->delete();

            // Update previous agency joined
            $checkAgencyUser = UsersJoinedAgency::where([
                'user_id' => $user->id,
                'agency_id' => $oldAgencyId,
            ])->whereNull('leave_date')->first();

            if ($checkAgencyUser) {
                $checkAgencyUser->update([
                    'leave_date' => now(),
                    'status' => 'change agency by admin',
                    'kicked_by_admin' => Auth::id()
                ]);
            } else {
                UsersJoinedAgency::create([
                    'user_id' => $user->id,
                    'agency_id' => $oldAgencyId,
                    'type' => 2,
                    'join_date' => now(),
                    'leave_date' => now(),
                    'status' => 'change agency by admin',
                    'kicked_by_admin' => Auth::id(),
                ]);
            }

            // Create new join record
            UsersJoinedAgency::create([
                'user_id' => $user->id,
                'agency_id' => $request->agency_id,
                'type' => 2,
                'join_date' => now(),
                'status' => 'Joined',
            ]);

            $user->agency_id = $request->agency_id;
            $user->save();

            return response()->json(['status' => true, 'message' => __('dashboard.successful')]);
        });
    }

    /**
     * Clean up devices with more than allowed accounts
     * Deletes newest accounts until only allowed number remain per device
     *
     * STRICT POLICY: Counts ALL accounts (even logged out ones) to match login restrictions
     */
    public function cleanupDuplicateDevices()
    {
        DB::beginTransaction();

        try {
            $register_account = (int)(Common::getSettingValue('register_account') ?? 3);

            // STRICT POLICY: Get all device tokens that have more than allowed accounts (ALL users, not just active)
            // This matches the strict login policy where we count all accounts
            $deviceTokens = User::select('device_token', DB::raw('COUNT(*) as user_count'))
                ->whereNotNull('device_token')
                ->where('device_token', '!=', '')
                ->groupBy('device_token')
                ->having('user_count', '>', $register_account)
                ->get();

            $totalDeleted = 0;
            $devicesProcessed = 0;
            $deletedUsers = [];
            $totalUserAccountsDeleted = 0;
            $totalTokensDeleted = 0;

            foreach ($deviceTokens as $deviceData) {
                $deviceToken = $deviceData->device_token;
                $userCount = $deviceData->user_count;

                // STRICT POLICY: Get ALL users for this device (including logged out), ordered by created_at DESC (newest first)
                $users = User::where('device_token', $deviceToken)
                    ->orderByDesc('created_at')
                    ->get();

                // Calculate how many to delete
                $deleteCount = $userCount - $register_account;

                // Delete the newest accounts (first N records since ordered DESC)
                $usersToDelete = $users->take($deleteCount);

                foreach ($usersToDelete as $user) {
                    // 1. Delete from user_accounts (SwitchAccount module)
                    $userAccountsDeleted = \Modules\SwitchAccount\Entities\UserAccount::where(function($q) use ($user) {
                        $q->where('parent_user_id', $user->id)
                          ->orWhere('child_user_id', $user->id);
                    })->delete();
                    $totalUserAccountsDeleted += $userAccountsDeleted;

                    // 2. Delete all user tokens (Sanctum)
                    $tokensDeleted = $user->tokens()->count();
                    $user->tokens()->delete();
                    $totalTokensDeleted += $tokensDeleted;

                    // 3. Soft delete the user (same as dashboard)
                    $user->delete();

                    \Log::info('User deleted in cleanup', [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'uuid' => $user->uuid,
                        'device_token' => $deviceToken,
                        'user_accounts_deleted' => $userAccountsDeleted,
                        'tokens_deleted' => $tokensDeleted,
                    ]);

                    $deletedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'uuid' => $user->uuid,
                        'device_token' => $deviceToken,
                        'created_at' => $user->created_at instanceof \Carbon\Carbon
                            ? $user->created_at->format('Y-m-d H:i:s')
                            : $user->created_at,
                    ];

                    $totalDeleted++;
                }

                // 4. Update devices_token_histories count
                $record = \App\Models\DevicesTokenHistory::where('device_token', $deviceToken)->first();
                if ($record) {
                    // Decrement count by number of deleted users
                    $record->count = max(0, $record->count - $deleteCount);
                    $record->save();
                }

                $devicesProcessed++;
            }

            DB::commit();

            \Log::info('Device cleanup completed', [
                'devices_processed' => $devicesProcessed,
                'total_users_deleted' => $totalDeleted,
                'total_user_accounts_deleted' => $totalUserAccountsDeleted,
                'total_tokens_deleted' => $totalTokensDeleted,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Cleanup completed successfully',
                'data' => [
                    'devices_processed' => $devicesProcessed,
                    'total_users_deleted' => $totalDeleted,
                    'total_user_accounts_deleted' => $totalUserAccountsDeleted,
                    'total_tokens_deleted' => $totalTokensDeleted,
                    'deleted_users' => $deletedUsers,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error during cleanup: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview devices with duplicate accounts (PREVIEW ONLY - NO DELETION)
     * Shows what would be deleted without actually deleting
     */
    public function cleanupDuplicateDevicesPreviewPage(Content $content)
    {
        try {
            $register_account = (int)(Common::getSettingValue('register_account') ?? 3);

            $previewColumns = ['id', 'name', 'phone', 'email', 'uuid', 'created_at', 'status', 'is_logout'];
            $previewUserLimit = 50;

            // Get all device tokens that have more than allowed accounts (paginated)
            $deviceTokens = User::select('device_token', DB::raw('COUNT(*) as user_count'))
                ->whereNotNull('device_token')
                ->where('device_token', '!=', '')
                ->groupBy('device_token')
                ->having('user_count', '>', $register_account)
                ->paginate(25);

            // Global total-to-delete across ALL devices (not just the current page),
            // preserving the original metric meaning.
            $totalToDelete = (int) User::query()
                ->whereNotNull('device_token')
                ->where('device_token', '!=', '')
                ->groupBy('device_token')
                ->havingRaw('COUNT(*) > ?', [$register_account])
                ->selectRaw('COUNT(*) - ? as delete_count', [$register_account])
                ->get()
                ->sum('delete_count');

            $previewData = [];

            foreach ($deviceTokens as $deviceData) {
                $deviceToken = $deviceData->device_token;
                $userCount = $deviceData->user_count;

                $deleteCount = $userCount - $register_account;

                // Load only the columns needed for the preview, capped to a preview limit.
                $usersToDelete = User::select($previewColumns)
                    ->where('device_token', $deviceToken)
                    ->orderByDesc('created_at')
                    ->limit(min($deleteCount, $previewUserLimit))
                    ->get();
                $usersToKeep = User::select($previewColumns)
                    ->where('device_token', $deviceToken)
                    ->orderByDesc('created_at')
                    ->offset($deleteCount)
                    ->limit($previewUserLimit)
                    ->get();

                $previewData[] = [
                    'device_token' => $deviceToken,
                    'total_accounts' => $userCount,
                    'allowed_accounts' => $register_account,
                    'to_delete_count' => $deleteCount,
                    'users_to_delete' => $usersToDelete,
                    'users_to_keep' => $usersToKeep,
                ];
            }

            return $content
                ->title('معاينة الحسابات المكررة')
                ->description('عرض الحسابات التي سيتم حذفها')
                ->body(view('admin.cleanup_duplicate_devices_preview', [
                    'devices' => $previewData,
                    'paginator' => $deviceTokens,
                    'total_devices' => $deviceTokens->total(),
                    'total_to_delete' => $totalToDelete,
                    'allowed_accounts' => $register_account,
                ]));

        } catch (\Exception $e) {
            return $content
                ->title('خطأ')
                ->body("<div class='alert alert-danger'><i class='fa fa-exclamation-triangle'></i> {$e->getMessage()}</div>");
        }
    }

    public function cleanupDuplicateDevicesPreview()
    {
        try {
            $register_account = (int)(Common::getSettingValue('register_account') ?? 3);

            $previewColumns = ['id', 'name', 'phone', 'email', 'uuid', 'created_at', 'status', 'is_logout'];
            $previewUserLimit = 50;

            // Get all device tokens that have more than allowed accounts (paginated)
            $deviceTokens = User::select('device_token', DB::raw('COUNT(*) as user_count'))
                ->whereNotNull('device_token')
                ->where('device_token', '!=', '')
                ->groupBy('device_token')
                ->having('user_count', '>', $register_account)
                ->paginate(25);

            // Global total-to-delete across ALL devices (not just the current page),
            // preserving the original metric meaning.
            $totalToDelete = (int) User::query()
                ->whereNotNull('device_token')
                ->where('device_token', '!=', '')
                ->groupBy('device_token')
                ->havingRaw('COUNT(*) > ?', [$register_account])
                ->selectRaw('COUNT(*) - ? as delete_count', [$register_account])
                ->get()
                ->sum('delete_count');

            $previewData = [];

            foreach ($deviceTokens as $deviceData) {
                $deviceToken = $deviceData->device_token;
                $userCount = $deviceData->user_count;

                $deleteCount = $userCount - $register_account;

                // Load only the columns needed for the preview, capped to a preview limit.
                $usersToDelete = User::select($previewColumns)
                    ->where('device_token', $deviceToken)
                    ->orderByDesc('created_at')
                    ->limit(min($deleteCount, $previewUserLimit))
                    ->get();
                $usersToKeep = User::select($previewColumns)
                    ->where('device_token', $deviceToken)
                    ->orderByDesc('created_at')
                    ->offset($deleteCount)
                    ->limit($previewUserLimit)
                    ->get();

                $previewData[] = [
                    'device_token' => $deviceToken,
                    'total_accounts' => $userCount,
                    'allowed_accounts' => $register_account,
                    'to_delete_count' => $deleteCount,
                    'users_to_delete' => $usersToDelete->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'phone' => $user->phone,
                            'email' => $user->email,
                            'uuid' => $user->uuid,
                            'created_at' => $user->created_at instanceof \Carbon\Carbon
                                ? $user->created_at->format('Y-m-d H:i:s')
                                : $user->created_at,
                            'is_logout' => $user->is_logout,
                            'status' => $user->status,
                        ];
                    })->toArray(),
                    'users_to_keep' => $usersToKeep->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'phone' => $user->phone,
                            'email' => $user->email,
                            'uuid' => $user->uuid,
                            'created_at' => $user->created_at instanceof \Carbon\Carbon
                                ? $user->created_at->format('Y-m-d H:i:s')
                                : $user->created_at,
                        ];
                    })->toArray(),
                ];
            }

            return response()->json([
                'status' => true,
                'message' => 'Preview generated successfully (NO DELETION PERFORMED)',
                'data' => [
                    'total_devices_affected' => $deviceTokens->total(),
                    'total_users_to_delete' => $totalToDelete,
                    'allowed_accounts_per_device' => $register_account,
                    'devices' => $previewData,
                    'pagination' => [
                        'current_page' => $deviceTokens->currentPage(),
                        'per_page' => $deviceTokens->perPage(),
                        'last_page' => $deviceTokens->lastPage(),
                        'total' => $deviceTokens->total(),
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error during preview: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute cleanup of duplicate device accounts (ACTUAL DELETION)
     * This performs the actual deletion after reviewing the preview
     */
    public function cleanupDuplicateDevicesRunPage(Content $content)
    {
        try {
            $result = $this->cleanupDuplicateDevices();
            $data = $result->getData();

            if ($data->status) {
                return $content
                    ->title('تم الحذف بنجاح')
                    ->description('نتائج عملية الحذف')
                    ->body(view('admin.cleanup_duplicate_devices_run', [
                        'devices_processed' => $data->data->devices_processed,
                        'total_users_deleted' => $data->data->total_users_deleted,
                        'total_user_accounts_deleted' => $data->data->total_user_accounts_deleted,
                        'total_tokens_deleted' => $data->data->total_tokens_deleted,
                        'deleted_users' => $data->data->deleted_users,
                    ]));
            } else {
                return $content
                    ->title('خطأ')
                    ->body("<div class='alert alert-danger'><i class='fa fa-exclamation-triangle'></i> {$data->message}</div>");
            }

        } catch (\Exception $e) {
            return $content
                ->title('خطأ')
                ->body("<div class='alert alert-danger'><i class='fa fa-exclamation-triangle'></i> {$e->getMessage()}</div>");
        }
    }

    public function cleanupDuplicateDevicesRun()
    {
        // Just call the existing cleanup function
        return $this->cleanupDuplicateDevices();
    }

    /**
     * Display the cleanup duplicate devices page
     */
}
