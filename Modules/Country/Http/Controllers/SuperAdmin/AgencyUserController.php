<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use App\Admin\Actions\CanPlaySwitchAction;
use App\Models\User;
use App\Models\Agency;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use App\Admin\Controllers\MainController;
use App\Admin\Selectable\ImageColors;
use App\Facades\UserHandling;
use App\Models\Country;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\SwitchAccount\Entities\UserAccount;
use Modules\Achievement\Http\Services\UserAchievementService;
use Session;

class AgencyUserController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;
    public $permission_name = 'host';

    public function __construct()
    {
        $this->title = 'Hosts';
    }


    public function index(Content $content)
    {
        $content = $content->title(__($this->title));

        $content = $content->row(function ($row) {
            $row->column(12, $this->grid());
        })->row(view('admin.same_device_users_modal'));

        return  parent::index($content);
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__($this->title))
            ->body($this->detail($id)));
    }

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

    public function update($id)
    {
        return $this->form()->update($id);
    }

    public function indexProfessionals(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-professional-users');
        }
        $content = $content->title(__($this->title));

        $content = $content->row(function ($row) {
            $row->column(12, $this->gridProfessional());
        })->row(view('admin.same_device_users_modal'));

        return parent::index($content);
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $haveCoins = (request()->have_coins == 1);
        $grid->model()->ofAgency()
            ->with(['profile', 'packs', 'agency'])
            ->where('is_host', 1)
            ->where('country_id', auth()->user()->country_id)
            ->withCount('sameDeviceUsers');
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
        $grid->column('id', __('Id'))->sortable();
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

                return "
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                            $image
                            <div>
                                <strong>$name</strong><br>
                                <span style='font-size: smaller;'>UID: $uid</span><br>
                                <img src='$receiverImg' style='width: 20px; height: 20px; border-radius: 50%;'>
                                <span style='font-size: smaller;'>Receiver Level</span><br>
                                <img src='$senderImg' style='width: 20px; height: 20px; border-radius: 50%;'>
                                <span style='font-size: smaller;'>Sender Level</span>
                            </div>
                        </div>
                        ";
            })->sortable();

        $grid->column('agency_id', __('Agency'))
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

                return "
                    <a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            {$image}
                            <div style='display: flex; flex-direction: column;'>
                                    <span style='text-decoration: underline; cursor: pointer;'>{$name}</span>
                                <span style='font-size: smaller;'>ID: {$this->agency_id}</span>
                            </div>
                        </div>
                    </a>
                ";
            })->sortable();

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

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });

        return $grid;
    }

    protected function gridProfessional()
    {
        $grid = new Grid(new User());
        $haveCoins = (request()->have_coins == 1);
        $grid->model()
            ->ofAgency()
            ->with(['profile', 'packs', 'agency.country', 'country'])
            ->where('is_host', 1)
            ->withCount('sameDeviceUsers')
            ->where(function ($query) {
                $currentCountry = auth()->user()->country_id;
                $query->where(function ($q) use ($currentCountry) {
                    $q->where('country_id', $currentCountry)
                        ->whereHas('agency', function ($a) use ($currentCountry) {
                            $a->where('country_id', '!=', $currentCountry);
                        });
                })->orWhere(function ($q) use ($currentCountry) {
                    $q->where('country_id', '!=', $currentCountry)
                        ->whereHas('agency', function ($a) use ($currentCountry) {
                            $a->where('country_id', $currentCountry);
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
        $grid->column('id', __('Id'))->sortable();
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
            })->sortable();

        $grid->column('agency_id', __('Agency'))
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
            })->sortable();

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
        $grid->disableExport();

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new User());
        $this->disableFormTools($form);
        $form->disableEditingCheck();

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
        $form->hidden('country_id')->default(auth()->user()->country_id);

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
                        break;
                }
            }
        });


        return $form;
    }

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
}
