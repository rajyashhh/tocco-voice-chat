<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use Encore\Admin\Auth\Permission;
use App\Admin\Controllers\MainController;
use App\Admin\Selectable\ImageColors;
use App\Admin\Services\AgencyService;
use App\Admin\Services\UserService;
use App\Models\Agency;
use App\Models\UserCoinLog;
use Carbon\Carbon;
use App\Models\Pack;
use App\Models\User;
use App\Models\Charge;
use App\Helpers\Common;
use App\Models\Country;
use App\Models\GiftLog;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Modules\Vip\Entities\UserVip;
use App\Models\UserSallary;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\App;
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

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-users');
        }

        $content = $content->title(__($this->title));

        $content = $content->row(function ($row) {
            $row->column(12, $this->grid());
        })->row(view('admin.same_device_users_modal'));

        return $content;
    }

    // public function edit($id, Content $content)
    // {
    //     return $content
    //     ->title(__($this->title));

    //         // ->body($this->form()->edit($id));
    // }

    // public function create(Content $content)
    // {
    //     return $content
    //         ->title(__($this->title));

    //         // ->body($this->form());
    // }

    public function destroy($id)
    {
        User::where('country_id', auth()->user()->country_id)->findOrFail($id);
        return $this->form()->destroy($id);
    }

    public function show($id, Content $content,)
    {
        $month = request('month'); // e.g., "5" for May
        $year = request('year');
        $start = request('start_at');
        $end = request('end_at');
        $tab = request('tab') ?? 'salary';
        $joinDate = request('join_date');
        $user = User::with('profile')->where('country_id', auth()->user()->country_id)->findOrFail($id);
        $type = request('type') ?? 4;
        $agencyId = request('agency_id');

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
            ->view('super_user_profile', $data
            );
    }

    public function countries()
    {
        $ops       = [null => __('no country')];
        $countries = Country::select(['id', 'name', 'e_name'])->get();
        foreach ($countries as $country) {
            $ops[$country->id] = App::isLocale('en') ? $country->e_name : $country->name;
        }
        return $ops;
    }

    protected function grid()
    {
        $grid = new Grid(new User());
        $haveCoins = (request()->have_coins == 1);
        $authCountryId = Admin::user()->country_id;

        $grid->model()
            ->where('country_id', $authCountryId)
            ->select(['id', 'name', 'sender_level', 'received_level', 'device_token', 'agency_id', 'uuid', 'special_id', 'di','can_play', 'huawei_version', 'android_version', 'ios_version', 'transfer_salary', 'is_bd'])
            ->with([
                'profile',
                'agency',
                'userSetting',
                'senderLevel',
                'receiverLevel',
                'monthlyDiamondReceive',
                'packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ])->withCount('sameDeviceUsers');

        if (request()->signups == 'today') {
            $grid->model()->whereDate('created_at', today());
        }

        if (request()->signups == 'week') {
            $grid->model()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        if (request()->signups == 'month') {
            $grid->model()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        if (request()->messages == 'today') {
            $grid->model()->whereHas('chatMessages', fn($q) => $q->whereDate('created_at', today()));
        }

        if (request()->messages == 'month') {
            $grid->model()->whereHas('chatMessages', fn($q) =>
            $q->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
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
                ->whereHas('agency', function ($q) {
                    $q->where('country_id', auth()->user()->country_id);
                });
        }

        if (request()->online == 1) {
            $grid->model()->where('online', 1);
        }
        else if ($haveCoins) {
            $grid->model()->where('di', '>', 0)->orderByDesc('di');
        } else {
            $grid->model()->orderByDesc('id');
        }
        $grid->quickSearch();
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
            });
        });
        $grid->column('id', __('Id'))->sortable();
        if ($haveCoins) {
            $grid->column('di', __('coins'))->display(function ($value) {
                return number_format($value);
            });
        }

        $grid->column('name', __('Name'))
            ->display(function ($name) {
                $user = $this;
                if (! $user) {
                    return __('No User');
                }
                return app(UserService::class)->adminUserCard($user, false, superadmin_url("users/profile/{$user->id}"));
            })->sortable();

        $grid->column('agency_id', __('Agency'))
            ->display(function () {
                $agency = $this->agency;
                if (! $agency) {
                    return '';
                }

                return app(AgencyService::class)->adminAgencyData($agency);
            })->sortable();
               Admin::style(UserService::adminUserCardStyles() . gridStyles());

        Admin::style('.btn-circle {width: 30px; height: 30px; font-size:15px; border-radius: 50%; text-align: center; }');
        Admin::style("
            .modal-dialog {
                max-width: 90%;
            }

            .modal {
                top: 5%;
            }

            .modal-body {
                max-height: 70vh !important;
                overflow-y: auto !important;
            }
        ");

        $grid->column('custom_button2', __('Number of Accounts'))->display(function () {
            $count = $this->same_device_users_count;
            return "<button class='btn btn-sm btn-primary show-same-device-modal' data-user-id='{$this->id}'>$count</button>";
        });

        $grid->column('versions', __('versions'))->modal(__('versions'), function () {
            $data = [
                ['iOS',     $this->ios_version],
                ['Huawei',  $this->huawei_version],
                ['Android', $this->android_version],
            ];

            return new Table([__('Name'), __('Version')], $data);
        });

        Admin::script("
            $(document).on('click', '.show-same-device-modal', function() {
                console.log('here');
                var userId = $(this).data('user-id');
                $('#sameDeviceUsersModal .modal-body').html('Loading...');
                $('#sameDeviceUsersModal').modal('show');
                $.get('/superadmin/users/' + userId + '/same-device-users-table', function(html) {
                    $('#sameDeviceUsersModal .modal-body').html(html);
                });
            });
        ");

        // $grid->actions(function ($actions) {
        //     $model = $actions->row;
        //     $actions->add(new ChargeSwitchAction());
        //     $actions->add(new InviteSwitchAction());

        //     $row = $actions->row;
        //     $actions->add(new CanPlaySwitchAction($row['can_play']));

        //     $actions->add(new KickOfAgencyAction());
        //     $actions->add(new KickOfFamilyAction());

        //     $actions->add(new ChangeAgencyAction($model->id));
        // });
        $grid->disableActions();

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();


        return $grid;
    }

    protected function form()
    {
        $form = new Form(new User());
        $this->disableFormTools($form);

        if ($form->isEditing()) {
            $userId           = request()->route('user');
            $user             = User::findOrFail($userId);
            $oldDiValue       = $user->getOriginal('di');
            $oldDiamoundValue = $user->getOriginal('user_diamond');
        } else {
            $oldDiValue       = null;
            $oldDiamoundValue = null;
        }

        $form->display('id', __('id'));
        if (!$form->isEditing()) {
            $form->text('uuid', __('uuid'))->creationRules([
                'required',
                Rule::unique('users', 'uuid'),
                function ($attribute, $value, $fail) {
                    if (DB::table('wares')->where('value', $value)->exists()) {
                        return $fail(__('لا يمكنك استخدام معرف المميز هذا'));
                    }
                }
            ])
                ->updateRules([
                    'required',
                    Rule::unique('users', 'uuid')->ignore(request()->route('id')),
                    function ($attribute, $value, $fail) {
                        if (DB::table('wares')->where('value', $value)->exists()) {
                            return $fail(__('القيمة موجودة بالفعل في جدول wares.'));
                        }
                    }
                ]);
        }

        $form->belongsTo('image_color_id', ImageColors::class, __('Color'));

        $form->text('name', __('Name'));
        if ($form->isEditing()) {
            $form->hidden('oldDiValue')->default($oldDiValue);
            $form->hidden('oldDiamoundValue')->default($oldDiamoundValue);
        }
        $form->text('original_uuid', __('uuid'))->updateRules(['required', "unique:users,uuid,{{id}}"]);

        $form->image('photo', __('image'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        });

        $form->image('profile.image_id', __('image Id'));

        if (!Admin::user()->can('delete-profile-switch-' . $this->permission_name)) {
            Admin::script(
                <<<JS
                    $(document).ready(function() {
                        $('input[name="photo"]').closest('.form-group').find('.fileinput-remove').hide();
                    });
                    JS
            );
        }


        $form->hasMany('images', __('Profile Images'), function ($form) {
            $form->image('img', __('Image'));
        })->useTable()->disableCreate()->disableDelete();

        if (!Admin::user()->can('delete-profile-switch-' . $this->permission_name) && !Admin::user()->can('*')) {
            Admin::script(
                <<<JS
        $(document).ready(function() {
            $('input[name="photo"]').closest('.form-group').find('.fileinput-remove').hide();
            $('.has-many-images .has-many-remove').hide();
            $('.has-many-images .remove').hide();
            $('.has-many-images .close').hide();
            $('.has-many-images a.close').hide();
        });
        JS
            );
        }
        $form->select('profile.gender', __('gender'))->options([0 => __('female'), 1 => __('male')]);
        $form->email('email', __('Email'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly');
        $form->password('password', __('Password'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly')->creationRules('required');
        $form->text('phone', __('phone'))->creationRules(['nullable', "unique:users,phone,{{id}}"])->updateRules(['nullable', "unique:users,phone,{{id}}"]);
        $form->hidden('country_id')->default(auth()->user()->country_id);


        if (Session::has('show_alert')) {
            $form->html('<script>
            $(document).ready(function () {
                alert(" يملك هذا المستخدم وكالة   . الرجاء مسح الوكالة واخراج المضيفين اولا قبل تغيير نوع المستخدم");
            });
        </script>');
        }

        $form->saving(function (Form $form) use ($oldDiValue, $oldDiamoundValue) {
            $type_user = request()->type_user;
            $model     = $form->model();
            $user_id   = $model->id;
            $form->model()->uuid = $form->original_uuid;
            if ($form->oldDiValue != $oldDiValue) {
                $form->di = $oldDiValue;
            }

            if ($form->oldDiamoundValue != $oldDiamoundValue) {
                $form->user_diamond = $oldDiamoundValue;
            }

            $agancy = Agency::where('app_owner_id', $user_id)->first();
            if ($agancy) {
                if (in_array(intval($type_user), [0, 1, 5]) && $model->isDirty('type_user')) {
                    session()->flash('show_alert', 'Your alert message');
                    return redirect()->back();
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
                        break;
                }
            }
        });


        return $form;
    }

    public function ajaxSameDeviceUsersTable($id)
    {
        $user = User::with(['sameDeviceUsers.profile'])->where('country_id', auth()->user()->country_id)->findOrFail($id);
        $users = $user->sameDeviceUsers;

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
        // Return just table's HTML (your AJAX will inject this)
        return $table->render();
    }
}
