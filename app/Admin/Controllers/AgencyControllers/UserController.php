<?php

namespace App\Admin\Controllers\AgencyControllers;


use Encore\Admin\Auth\Permission;
use Session;
use App\Models\User;
use App\Models\Agency;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\Country;
use Encore\Admin\Widgets\Tab;
use App\Models\UsersJoinedAgency;
use App\Facades\UserHandling;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use Illuminate\Validation\Rule;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use App\Admin\Services\UserService;
use Illuminate\Support\Facades\App;
use App\Admin\Selectable\ImageColors;
use App\Admin\Services\AgencyService;
use Illuminate\Support\Facades\Cache;
use App\Admin\Actions\ChangeAgencyAction;
use App\Admin\Actions\ChargeSwitchAction;
use App\Admin\Actions\InviteSwitchAction;
use App\Admin\Actions\KickOfAgencyAction;
use App\Admin\Actions\KickOfFamilyAction;
use App\Admin\Controllers\MainController;
use App\Admin\Actions\CanPlaySwitchAction;
use Modules\SwitchAccount\Entities\UserAccount;
use Modules\Achievement\Http\Services\UserAchievementService;

class UserController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;
    public $permission_name = 'hosts';



    public function __construct()
    {
        $this->title = 'Hosts';
    }


    public function index(Content $content)
    {
        // One page, two tabs (owner 2026-08-10): the live hosts roster (with its
        // actions) and the join/leave history log — previously a separate page
        // (UsersJoinedAgencyController) that only duplicated the sidebar and left
        // empty screens. Merged here with no loss of function: the roster keeps
        // every action/filter, the log becomes a tab beside it. Each grid is
        // named so its pagination/filter/export query params never collide.
        // Render each grid to HTML before handing it to the Tab widget: the tab
        // blade only special-cases Renderable, and Grid is neither Renderable nor
        // Stringable, so a raw Grid would not render inside a tab (unlike a Column,
        // which special-cases Grid). Grid::render() returns the finished HTML.
        $tab = new Tab();
        $tab->add(__($this->title), $this->grid()->render());
        $tab->add(__('Join History'), $this->joinLogGrid()->render());

        return parent::index($content
            ->title(__($this->title))
            ->row(function ($row) use ($tab) {
                $row->column(12, $tab);
                //$row->column(2, view('admin.grid.users.actions'));
            })->row(view('admin.same_device_users_modal')));
    }

    /**
     * Join/leave history log (formerly the standalone "Joined Users" page). Read
     * only audit rows from users_joined_agencies, country-scoped through the host
     * so a scoped manager only sees their own countries' history. Kept as a tab on
     * the Hosts page rather than a duplicate sidebar entry.
     */
    protected function joinLogGrid()
    {
        $grid = new Grid(new UsersJoinedAgency());
        $grid->setName('join_log');

        $countryID = Common::filterCountryIds();

        $grid->model()
            ->when($countryID, fn ($q) => $q->whereHas('user', fn ($u) => $u->whereIn('country_id', $countryID)))
            ->with(['user.profile', 'agency'])
            ->orderByDesc('id');

        $grid->filter(function ($filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('agency_id', __('agency'))->select(Common::by_agency_filter());
            });

            $filter->column(1 / 2, function ($filter) {
                // Read $this->input (the framework-injected, de-prefixed value)
                // instead of request(): with setName('join_log') the field is
                // submitted as `join_log_join_date`, so request('join_date') is
                // always null and the filter would silently no-op.
                $filter->where(function ($query) {
                    $date = $this->input;
                    if ($date) {
                        $converted = \App\Helpers\UserCommon::convertArabicNumbers($date);
                        try {
                            $parsedDate = \Carbon\Carbon::parse($converted)->toDateString();
                            $query->whereDate('join_date', $parsedDate);
                        } catch (\Exception $e) {
                            // ignore an unparseable date filter
                        }
                    }
                }, __('Join date'), 'join_date')->date();
            });
        });

        $grid->column('id', __('Id'));

        $grid->column('user.name', __('User'))->display(function ($name) {
            if (!$this->user) {
                return "<span>Unknown</span>";
            }
            $uid = @$this->user->uuid;
            $path = @$this->user?->profile?->avatar;
            $defaultImage = asset('images/businessman-icon.jpg');
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->user->id, $url, 40, 40);

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <strong>$name</strong><br>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                    </div>
                </div>
            ";
        });

        $grid->column('agency.name', __('Agency'))->display(function ($name) {
            if (!$this->agency) {
                return "<span>Unknown</span>";
            }
            $path = @$this->agency->img;
            $id = $this->agency->id;
            $defaultImage = asset('images/icon-agency.jpg');
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    <img src='{$url}' alt='agency' style='width: 40px; height: 40px; object-fit: cover; border-radius: 4px;'>
                    <span>{$name}</span>
                    <span>id: $id</span>
                </div>
            ";
        });

        $grid->column('status', __('status'))->display(fn ($status) => __((string) $status));
        $grid->column('join_date', __('Join date'));
        $grid->column('leave_date', __('Leave date'));

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableRowSelector();
        // Export is driven by a page-global `_export_` param; with two grids on one
        // page only the roster (rendered first) should own it. The log never had an
        // export button as a standalone page, so keep it off here too.
        $grid->disableExport();

        return $grid;
    }

    public function indexProfessionals(Content $content)
    {
        if (!session('preview_superadmin') && !session('filter_country_id') && !session('preview_area_manager')) {
            abort(404, __('not found'));
        }

        $content = $content->title(__($this->title));

        $content = $content->row(function ($row) {
            $row->column(12, $this->gridProfessional());
        })->row(view('admin.same_device_users_modal'));

        return $content;
    }
    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__($this->title))
            ->body($this->detail($id)));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__($this->title))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__($this->title))
            ->body($this->form()));
    }

    protected function gridProfessional()
    {
        $grid = new Grid(new User());
        $countryID = Common::filterCountryIds();
        $haveCoins = (request()->have_coins == 1);
        $grid->model()
            ->ofAgency()
            ->with(['profile', 'packs', 'agency.country', 'country'])
            ->where('is_host', 1)
            ->withCount('sameDeviceUsers')
            ->where(function ($query) use ($countryID) {
                $currentCountry = $countryID;
                $query->where(function ($q) use ($currentCountry) {
                    $q->whereIn('country_id', $currentCountry)
                        ->whereHas('agency', function ($a) use ($currentCountry) {
                            $a->whereNotIn('country_id',  $currentCountry);
                        });
                })->orWhere(function ($q) use ($currentCountry) {
                    $q->whereNotIn('country_id',  $currentCountry)
                        ->whereHas('agency', function ($a) use ($currentCountry) {
                            $a->whereIn('country_id', $currentCountry);
                        });
                });
            });

        $grid->quickSearch();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('agency_id', __('agency'))->select(Common::by_agency_filter());

                $filter->column(1 / 2, function ($filter) {
                    $filter->where(function ($query) {
                        $input = $this->input;
                        $query->where('name', 'like', "%$input%")
                            ->orWhere('uuid', 'like', "%$input%")->orWhere('special_id', 'like', "%$input%")->orWhere('nickname', 'like', "%$input%")->orWhere('email', 'like', "%$input%");
                    }, __('User'))->placeholder(__('Search by name , UUID , nickname and email'));
                });
            });
        });
        $grid->column('id', __('Id'));
        if ($haveCoins) {
            $grid->column('di', __('coins'))->display(function ($coin) {
                $icon = asset('images/coin.jpg'); // تأكد من وجود الصورة في هذا المسار
                return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
            });
        }

        $grid->column('name', __('Name'))
            ->display(function ($name) {
                if (request()->filled('_export_')) {
                    return $this->name;
                }
                $uid = @$this->uuid;
                $path = @$this->profile?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                $receiver_img = @$this->getImageReceiverOrSender('receiver_id', 1)?->img ?? '';
                $receiverImg = getImagePath($receiver_img) ?? $defaultImage;

                $sender_img = @$this->getImageReceiverOrSender('sender_id', 2)?->img ?? '';
                $senderImg = getImagePath($sender_img) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                $image = handleShowImageWithTypes($this->id, $url, 50, 50);
                $showUrl = url("superadmin/users/profile/{$this->id}");
                $country = app()->getLocale() == 'ar' ? $this->country?->name : $this->country?->e_name;

                return "
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                            $image
                            <div>
                                <strong>$name</strong><br>
                                <span style='font-size: smaller;'>UID: $uid</span><br>
                                <span style='font-size: smaller;'>Country: $country</span><br>
                                <img src='$receiverImg' style='width: 20px; height: 20px; border-radius: 50%;'>
                                <span style='font-size: smaller;'>Receiver Level</span><br>
                                <img src='$senderImg' style='width: 20px; height: 20px; border-radius: 50%;'>
                                <span style='font-size: smaller;'>Sender Level</span>
                            </div>
                        </div>
                        ";
            });

        $grid->column('agency', __('Agency'))
            ->display(function () {
                if (request()->filled('_export_')) {
                    return $this?->agency?->name ?: __('No agency');
                }
                if (!$this->agency) {
                    return "<span style='color: #aaa;'>No agency</span>";
                }

                $name = $this->agency->name ?? '';

                $cacheKey = "agency_image_{$this->agency_id}";
                $image = Cache::remember($cacheKey, 3600, function () {
                    $path = $this->agency->img;
                    $defaultImage = asset("images/icon-agency.jpg");
                    $url = getImagePath($path) ?? $defaultImage;

                    if (!isImageExists($url)) {
                        $url = $defaultImage;
                    }

                    return handleShowImageWithTypes($this->agency_id, $url, 40, 40);
                });

                $profileUrl = route('superadmin.agency.profile', ['id' => $this->agency_id]);
                $country = app()->getLocale() == 'ar' ? $this->agency->country?->name : $this->agency->country?->e_name;

                return "
                    <a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            {$image}
                            <div style='display: flex; flex-direction: column;'>
                                    <span style='text-decoration: underline; cursor: pointer;'>{$name}</span>
                                <span style='font-size: smaller;'>ID: {$this->agency_id}</span>
                                <span style='font-size: smaller;'>Country: $country</span><br>
                            </div>
                        </div>
                    </a>
                ";
            });

        Admin::style('.btn-circle {width: 30px; height: 30px; font-size:15px; border-radius: 50%; text-align: center; }');
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

        $grid->disableActions();

        $grid->disableCreateButton();

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

        $loggedInUserId = Admin::user()->id;
        $form->display('id', __('id'));
        if (!$form->isEditing()) {
            // Add a hidden field for 'uuid' in the edit form
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
                    // نفس الشيء هنا مع التحقق من عدم وجود القيمة في جدول wares
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
        $form->text('uuid', __('uuid'))->updateRules(['required', "unique:users,uuid,{{id}}"]);

        // $form->switch('is_gold_id', trans('	is_gold_id'))->states (Common::getSwitchStates());
        $form->image('profile.avatar', __('image'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        });

        $form->image('profile.image_id', __('image Id'));
        $state = [
            'on' => ['value' => 1, 'text' => 'open', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => 'close', 'color' => 'default'],
        ];

        $form->switch('charge_status', __("charge status"))->states($state);
        $form->switch('transfer_salary', __("transfer_salary"))->states($state);
        $form->switch('userSetting.show_invite_code', __("show invite code"))->states($state);
        $form->switch('userSetting.hide_chat', __("hide_chat"))->states($state);
        $form->select('country_id', trans('country'))->options(function () {
            $ops       = [null => __('no country')];
            $countries = Country::all();
            foreach ($countries as $country) {
                $ops[$country->id] = App::isLocale('en') ?  ($country->e_name ?? $country->name) : $country->name;
            }
            return $ops;
        });
        $states = [
            'default'  => ['value' => 0, 'text' => 'yes', 'color' => 'success'],
            'on'  => ['value' => 2, 'text' => 'yes', 'color' => 'success'],
            'off' => ['value' => 3, 'text' => 'no', 'color' => 'danger'],
        ];
        if ($form->isCreating()) {
            $form->switch('can_play', __('canPlay'))->default(0)->states($states);
        } elseif ($form->isEditing()) {
            $form->switch('can_play', __('canPlay'))->value(function ($can_play) {
                $can_play = UserHandling::chickLevelToPlay($this);
                return $can_play ? 'on' : 'off';
            })->states($states);
        }

        if ($loggedInUserId == 1 || $loggedInUserId == 2) {
            if ($form->isEditing()) {
                $form->number('di', __('Coins'))->default(0)
                    ->disable($form->isEditing());
            } else {
                $form->number('di', __('Coins'))->default(0);
            }
            $form->number('user_diamond', __('Diamonds'))->default(0);
            $form->number('total_sender_level', __('Sender Level'))->default(0);
            $form->number('total_received_level', __('Received Level'))->default(0);
            $form->number('total_charge_level', __('admin.charge_level'))->default(0);
            $form->number('salary', __('salary'))->disable();
        }
        $form->select('profile.gender', __('gender'))->options([0 => __('female'), 1 => __('male')]);
        $form->email('email', __('Email'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly');
        $form->password('password', __('Password'))->attribute('onfocus', "this.removeAttribute('readonly');")->attribute('readonly')->creationRules('required');
        $form->text('phone', __('phone'))->creationRules(['required', "unique:users,phone,{{id}}"])->updateRules(['required', "unique:users,phone,{{id}}"]);
        $form->switch('status', __('block status'))->options(Common::getSwitchStates2());
        $form->select('type_user', trans('User Type'))->options([
            $form->model()->type_user => $form->model()->type_user,
            0                         => 'مستخدم',
            1                         => 'مضيف',
            2                         => 'وكيل مضيفين',
            3                         => 'وكيل شحن',
            4                         => ' وكيل مصيفين ووكيل شحن',
            5                         => 'اداري',

        ])->default(0);

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

                        // dd();

                        break;
                }
            }
        });


        return $form;
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $countryID = Common::filterCountryIds();
        $grid = new Grid(new User());
        $haveCoins = (request()->have_coins == 1);
        $grid->model()->ofAgency()
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->select(['id', 'name', 'uuid', 'special_id', 'country_id', 'sender_level', 'received_level', 'agency_id', 'family_id',  'can_play', 'is_host', 'transfer_salary', 'is_bd', 'device_token', 'di'])
            ->with([
                'profile:id,user_id,avatar',
                'agency:id,name,img',
                'userSetting',
                'senderLevel:id,level,img',
                'receiverLevel:id,level,img',
                'country',
                'packs' => fn($q) => $q->where('is_used', true)
                    ->whereIn('type', [25])
                    ->with('ware:id,value'),
            ])->where('is_host', 1)->withCount('sameDeviceUsers');
        $grid->quickSearch();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('agency_id', __('agency'))->select(Common::by_agency_filter());

                $filter->column(1 / 2, function ($filter) {
                    $filter->where(function ($query) {
                        $input = $this->input;
                        $query->where('name', 'like', "%$input%")
                            ->orWhere('uuid', 'like', "%$input%")->orWhere('special_id', 'like', "%$input%")->orWhere('nickname', 'like', "%$input%")->orWhere('email', 'like', "%$input%");
                    }, __('User'))->placeholder(__('Search by name , UUID , nickname and email'));
                });
            });
        });
        $grid->column('id', __('Id'));
        if ($haveCoins) {
            $grid->column('di', __('coins'))->display(function ($value) {
                return number_format($value);
            });
        }

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('agency_id', __('Agency'))
            ->display(function () {
                $agency = $this->agency;
                if (! $agency) {
                    return '';
                }

                return app(AgencyService::class)->adminAgencyData($agency);
            });



        Admin::style('.btn-circle {width: 30px; height: 30px; font-size:15px; border-radius: 50%; text-align: center; }');
        Admin::style("
            .modal-dialog {
                max-width: 90%;
            }

            .modal-body {
                max-height: 70vh !important;
                overflow-y: auto !important;
            }
        ");

        $grid->column('custom_button2', __('accounts number'))->display(function () {
            $count = $this->same_device_users_count;

            return "<button class='btn btn-sm btn-primary show-same-device-modal' data-user-id='{$this->id}'>$count</button>";
        });

        Admin::script("
                    $(document).on('click', '.show-same-device-modal', function() {
                        console.log('here');
                        var userId = $(this).data('user-id');
                        $('#sameDeviceUsersModal .modal-body').html('Loading...');
                        $('#sameDeviceUsersModal').modal('show');
                        $.get('/admin/users/' + userId + '/same-device-users-table', function(html) {
                            $('#sameDeviceUsersModal .modal-body').html(html);
                        });
                    });
        ");


        $permission = $this->permission_name;

        $grid->actions(function ($actions) use ($permission) {
            $model = $actions->row;

            if (Admin::user()->can('charge-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new ChargeSwitchAction());
            }
            if (Admin::user()->can('invite-switch-' . $permission) || Admin::user()->can('*')) {

                $actions->add(new InviteSwitchAction());
            }
            if (Admin::user()->can('can-Play-switch-' . $permission) || Admin::user()->can('*')) {

                $actions->add(new CanPlaySwitchAction());
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

            if (! Admin::user()->can('delete-' . $permission) || !Admin::user()->can('*')) {
                $actions->disableDelete();
            }
        });


        $grid->disableCreateButton();

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('uuid', __('uuid'));
        $show->field('avatar', __('avatar'))->image('', 200);
        $show->field('name', __('Name'));
        $show->field('nickname', __('NickName'));
        $show->field('flag', __('country'))->image('', 50);
        $show->field('email', __('Email'));





        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
}
