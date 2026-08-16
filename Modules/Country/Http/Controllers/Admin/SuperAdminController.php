<?php

namespace Modules\Country\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Models\Bd;
use App\Models\User;
use App\Models\Agency;
use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\Country;
use App\Models\Permission;
use Illuminate\Support\Str;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Illuminate\Validation\Rule;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use App\Enums\Charges\UserTypeEnum;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Admin\Controllers\MainController;
use Modules\Milestones\Entities\Milestone;
use Modules\Country\Entities\SuperAdmin;
use Modules\Country\Entities\SuperAdminReward;
use App\Admin\Actions\FrozenWalletSuperAdminAction;
use Modules\Country\Actions\Admin\DeleteSuperAdminsAction;

class SuperAdminController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Super Admin';
    public $permission_name = 'superadmin';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__($this->title))
            ->row(function (Row $row) {
                $row->column(12, $this->grid2());
            })
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    protected function grid2()
    {
        return (new Box(
            title: __('admin.description'),
            content: view('admin.grid.superadmin.description'),
        ));
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
            ->title(trans('Super Admin'))
            ->body($this->profile($id)));
    }

    public function showPreview(Content $content)
    {
        return $content
            ->title(trans('Super Admin'))
            ->body($this->profilePreview());
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
            ->title(trans('Super Admin'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Super Admin'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SuperAdmin());

        $countryID = Common::filterCountryIds();
        $areaManagerPreview = session('preview_area_manager');

        $grid->model()->orderByDesc('id');

        if ($countryID && $areaManagerPreview) {
            $grid->model()->whereIn('country_id', $countryID);
        }

        $grid->model()->with([
            'appUser.packs',
            'country',
            'appUser',
            'appUser.profile',
            'createdBy.agency',
            'createdBy'
        ]);

        $milestoneCacheKey = 'milestone_super_admin';
        $milestoneId = Cache::remember($milestoneCacheKey, 3600, function () {
            return Milestone::where('slug', 'country')->first();
        });

        $grid->filter(function ($filter) {
            $filter->like('appUser.uuid', __('App User UUID'));
            $filter->like('appUser.name', __('User Name'));
        });

        $grid->column('id', __('Id'));
        $this->superAdminData($grid);

        $grid->column('default', __('default_superadmin_status'))->display(function () {
            if (request()->filled('_export_')) {
                return $this->default;
            }

            if ($this->default == 1) {
                return <<<HTML
                    <span style="display: flex; align-items: center;">
                        <span style="
                            font-size: smaller;
                            background: red;
                            display: inline-block;
                            border-radius: 50%;
                            width: 10px;
                            height: 10px;
                            margin-left: 5px;
                        " title=""></span>
                    </span>
                HTML;
            } else {
                return '<span style="color: #999;"></span>';
            }
        });

        $this->appUserData($grid);

        $this->countryData($grid);

        $grid->column('created_by', __('Creator'))->display(function ($creatorId) {
            $creator = $this->createdBy;

            if (!$creator) {
                return __('Unknown');
            }

            $url = url("admin/admin-users/{$creator->id}");
            $avatar = getImagePath($creator->avatar) ?? asset("images/businessman-icon.jpg");

            if (!isImageExists($avatar)) {
                $avatar = asset("images/businessman-icon.jpg");
            }

            $image = handleShowImageWithTypes($creator->id, $avatar, 30, 30);
            $name = $creator->username ?? __('Unknown');

            return "
                <div style='display:flex; align-items:center; gap:8px;'>
                    {$image}
                    <a href='{$url}' style='text-decoration:none;'>
                        <span style='text-decoration:underline; cursor:pointer;'>{$name}</span>
                    </a>
                </div>
            ";
        });

        // $grid->column('created_at', __('Created at'))->display(function ($date) {
        //     static $formatter = null;

        //     if ($formatter === null) {
        //         $formatter = new IntlDateFormatter(
        //             App::getLocale() === 'ar' ? 'ar_SA' : 'en_US',
        //             IntlDateFormatter::LONG,
        //             IntlDateFormatter::SHORT
        //         );
        //     }

        //     return $formatter->format(strtotime($date));
        // });

        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            $actions->disableDelete();
            if (Admin::user()->can('delete-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new DeleteSuperAdminsAction());
            }

            if (Admin::user()->can('charge-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new FrozenWalletSuperAdminAction());
            }
        });

        if (Admin::user()->can('choose-switch-' . $permission) || Admin::user()->can('*')) {
            $grid->tools(function (Grid\Tools $tools) use ($milestoneId) {
                $url = url('admin/milestone-rewards/' . @$milestoneId->id);
                $milestone = __('Acquisitions');

                $customButtonHTML = <<<HTML
                <div style="display: contents; align-items: center;">
                    <a href="{$url}" class="btn btn-sm btn-info" style="margin-right: 10px;">
                        {$milestone}
                    </a>
                </div>
            HTML;
                $tools->append($customButtonHTML);

                $logoutUrl = route('admin.super.logout');
                $loginText = __('login');
                $areaManagerUrl = url('/superadmin/login');

                $customButtonHTML2 = <<<HTML
                <div style="display: contents; align-items: center;">
                    <a href="{$logoutUrl}" class="btn btn-sm btn-danger" style="margin-right: 10px;">
                        <i class="fa fa-sign-in"></i> {$loginText}
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" onclick="copyAreaManagerUrl()">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
                    <script>
                    function copyAreaManagerUrl() {
                        const url = '{$areaManagerUrl}';
                        navigator.clipboard.writeText(url).then(() => {
                            toastr.success('تم نسخ الرابط بنجاح');
                        }).catch(() => {
                            alert('تعذر نسخ الرابط');
                        });
                    }
                </script>
                HTML;

                $tools->append($customButtonHTML2);
            });
        }

        $grid->disableRowSelector();
        $grid->disableExport();
        $this->extendGrid($grid);

        return $grid;
    }


    private function superAdminData($grid)
    {
        $grid->column('username', __('Super Admin'))->display(function ($name) {
            if (request()->filled('_export_')) {
                return $name;
            }

            $id = $this->id ?? '-';
            $name = $this->username ?? 'غير معروف';
            $path = $this->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = url("admin/superadmin-users/{$this->id}");

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='font-size: smaller;'>ID: $id</span>
                    </div>
                </div>
            ";
        });
    }


    private function appUserData($grid)
    {
        $grid->column('appUser.name', __('user'))->display(function ($name) {
            $user = $this->appUser;
            if (request()->filled('_export_')) {
                return $name;
            }
            if (!$user) return "<span style='color: red;'>غير مرتبط</span>";

            $uid = $user->uuid ?? 'غير معروف';
            $path = $user->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = url("admin/users/{$user->id}");

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        });
    }
    private function countryData($grid)
    {
        $grid->column('country.name', __('country'))->display(function () {

            $country = $this->country;

            if (!$country) {
                return '-';
            }

            // Select correct name based on locale
            $name = app()->getLocale() === 'ar'
                ? ($country->name ?: $country->e_name)
                : ($country->e_name ?: $country->name);

            // Get flag image URL
            $flag = $country->flag ? getImagePath($country->flag) : null;


            return <<<HTML
                    <div style="display:flex; align-items:center; gap:8px;">
                        <img src="$flag" alt="flag" width="20" height="20" style="border-radius:4px;">
                        <span>$name</span>
                    </div>
                HTML;
        });
    }
    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $userTable = config('admin.database.users_table');
        $connection = config('admin.database.connection');
        $form = new Form(new SuperAdmin());
        $this->disableFormTools($form);

        $form->text('name', __('name'));
        $form->hidden('created_by')->default(auth()->id());

        $form->text('username', trans('admin.username'))
            ->rules(function ($form) use ($connection, $userTable) {
                $table = "{$connection}.{$userTable}";

                $rules = ['required'];

                $uniqueRule = Rule::unique($table, 'username');

                if (! $form->isCreating()) {
                    $id = $form->model()?->id ?? null;
                    $uniqueRule->ignore($id);
                }

                $rules[] = $uniqueRule;

                return $rules;
            });
        $form->password('password', __('Password'))->rules('required');
        $form->image('avatar', __('img'));

        //        $form->hidden('transfer_salary', __('transfer_salary'));

        $form->select('country_id', trans('country'))->options(function ($value) {
            $ops       = [null => __('no country')];
            $countries = Country::doesntHave('superAdmin')->orWhere('id', $value)->get();
            foreach ($countries as $country) {
                $ops[$country->id] = App::isLocale('en') ?  ($country->e_name ?? $country->name) : $country->name;
            }
            return $ops;
        })->required();



        if ($form->isEditing()) {
            $form->select('app_id', __('validation.select_user'))->options(function ($value) {
                $ops2 = [];
                foreach (User::Where('id', $value)->get() as $user) {
                    $ops2[$user->id] = $user->uuid . '_' . $user->name;
                }
                return $ops2;
            })->ajax('/api/search/users-superadmin', 'id', 'name')->help('لا يمكن التعديل إلا إذا لم يكن هناك مستخدم مرتبط، أو كان المستخدم مرتبطًا لكن تم حذفه.')->rules('required');
        } else {
            $form->select('app_id', __('validation.select_user'))->options(function ($value) {
                $ops2 = [];
                foreach (User::Where('id', $value)->get() as $user) {
                    $ops2[$user->id] = $user->uuid . '_' . $user->name;
                }
                return $ops2;
            })->ajax('/api/search/users-superadmin', 'id', 'name')->rules('required');

            //            $form->switch('default', __('set_superadmin_as_default'))
            //                ->help(__('make_super_admin_default'));
        }
        $this->addPhoneFields($form, 'sometimes');

        $form->hidden('type', __('Type'))->value('country');
        //        $form->hidden('transfer_salary', __('transfer_salary'));

        $form->saving(function (Form $form) {
            $isEditing = $form->isEditing();
            $country_id = $form->input('country_id');
            $superAdmin = SuperAdmin::where('phone_code', request('phone_code'))->where('phone', request('phone'));
            if ($isEditing) $superAdmin->where('id', '!=', $form->model()->id);
            $exists = $superAdmin->exists();

            if ($exists) {
                $error = new \Illuminate\Support\MessageBag([
                    'title' => 'Error',
                    'message' => trans('you used this phone before'),
                ]);
                return back()->with(compact('error'))->withInput();
            }

            $userName = SuperAdmin::where('username', request('username'))->where('type', request('type'));
            if ($isEditing) $userName->where('id', '!=', $form->model()->id);
            $exists = $userName->exists();
            if ($exists) {
                $error = new \Illuminate\Support\MessageBag([
                    'title' => 'Error',
                    'message' => trans('you used this user name before'),
                ]);
                return back()->with(compact('error'))->withInput();
            }



            if ($isEditing) {
                $originalAppId = $form->model()->getOriginal('app_id');
                $newAppId = $form->input('app_id');
                if ($originalAppId !=  $newAppId) {
                    $OldUserAppId = User::find($originalAppId);
                    if ($OldUserAppId) {
                        $OldUserAppId->is_super_admin = 0;
                        $OldUserAppId->save();
                    }

                    $newUserAppId = User::find($newAppId);
                    $newUserAppId->is_super_admin = 1;
                    $newUserAppId->save();

                    $form->app_id = $newAppId;
                }
            }

            if ($form->password && $form->model()->password != $form->password) {
                $form->password   = Hash::make($form->password);
            }
        });

        $form->saved(function (Form $form) {
            /** @var \App\Models\AdminUser $superAdmin */
            $superAdmin = $form->model();
            $userId = $form->model()->id;
            $userAppId = $form->model()->app_id;


            $country = Country::find($superAdmin->country_id);

            if ($country && $country->area_manager_id) {
                if ($superAdmin->parent_id != $country->area_manager_id) {
                    $superAdmin->update([
                        'parent_id' => $country->area_manager_id,
                    ]);
                }
            }


            $userApp = User::find($userAppId);
            if (isset($userApp)) {
                $userApp->is_super_admin = 1;
                $userApp->save();
            }

            $role = DB::table('admin_roles')->where('slug', 'country')->first();
            $permissions = Permission::whereHas('permissionTypes', function ($q) {
                $q->where('type', 'country');
            })->get();

            if ($role && $userId) {
                $exists = DB::table('admin_role_users')
                    ->where('user_id', $userId)
                    ->where('role_id', $role->id)
                    ->exists();

                if (!$exists) {
                    DB::table('admin_role_users')->insert([
                        'user_id' => $userId,
                        'role_id' => $role->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            if ($permissions && $userId) {
                foreach ($permissions as $permission) {
                    $exists = DB::table('admin_user_permissions')
                        ->where('user_id', $userId)
                        ->where('permission_id', $permission->id)
                        ->exists();
                    if (!$exists) {
                        DB::table('admin_user_permissions')->insert([
                            'user_id' => $userId,
                            'permission_id' => $permission->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            if ($userId) {
                \App\Models\Admin::forgetCachedPermissionsFor($userId);
            }
            $isEditing = $form->isEditing();
            if (!$isEditing) {
                $countryName = Country::whereId($superAdmin->country_id)->first()->e_name;

                $newBdId = DB::table('admin_users')->insertGetId([
                    'parent_id' => $superAdmin->id,
                    'username' => 'bd' . $countryName . 'default',
                    'name' => 'bd' . $countryName . 'default',
                    'password' => Hash::make('bd' . $countryName . 'default'),
                    'default' => 1,
                    'country_id' => $superAdmin->country_id,
                    'type' => 'bd',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $defaultBd =  Bd::where('country_id', $superAdmin->country_id)->where('default', 1)->first();
                if ($defaultBd) {
                    $defaultBd->password   = Hash::make(Str::random(10));
                    $defaultBd->save();
                }
                Bd::where('country_id', $superAdmin->country_id)->update(['parent_id' => $superAdmin->id]);

                Agency::where('country_id', $superAdmin->country_id)->where(function ($q) {
                    $q->whereDoesntHave('bd')
                        ->orWhereHas('bd', function ($q) {
                            $q->where([
                                'default' => 1,
                                'country_id' => 0
                            ]);
                        });
                })->update(['bd_id' => $newBdId]);
            }
        });

        return $form;
    }

    protected function addPhoneFields(Form $form, $rules = 'required')
    {

        $form->text('phone', __('whatsApp number'))
            ->rules($rules)
            ->attribute('id', 'phone-input')
            ->attribute('maxlength', 12)
            ->default(function ($form) {
                if ($form->model()->phone && $form->model()->phone_code) {
                    return $form->model()->phone;
                }
                return null;
            });

        $form->hidden('phone_code')->default(function ($form) {
            return $form->model()->phone_code ?? '';
        });


        Admin::script($this->phoneJs());
    }


    protected function phoneJs()
    {
        return <<<JS
            function initPhoneInputById(inputId, hiddenId) {
                const input = document.querySelector(inputId);
                const hidden = document.querySelector(hiddenId);
                if (!input || input.classList.contains('iti-initialized')) return;

                const iti = window.intlTelInput(input, {separateDialCode: true, preferredCountries: ["eg"], utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"});
                input.classList.add('iti-initialized');

                if (input.value && hidden && hidden.value) iti.setNumber(hidden.value + input.value);

                input.addEventListener("countrychange", function () { if(hidden) hidden.value = "+" + iti.getSelectedCountryData().dialCode; });
                const form = input.closest('form');
                if(form && !form.classList.contains('phone-init')){
                    form.addEventListener('submit', function(){
                        // if(hidden) hidden.value = "+" + iti.getSelectedCountryData().dialCode;
                        // input.value = iti.getNumber(intlTelInputUtils.numberFormat.E164);
                                hidden.value = "+" + iti.getSelectedCountryData().dialCode;

                    });
                    form.classList.add('phone-init');
        }
    }

    function initAllPhones() { initPhoneInputById("#phone-input", "input[name='phone_code']"); }
    initAllPhones();
    $(document).on('pjax:complete', function () { setTimeout(initAllPhones, 100); });
    JS;
    }

    public function profile($id)
    {
        $tab = request()->query('tab', 'agencies');

        $authAdmin = auth()->user();
        $restrictCountry = !($authAdmin->isAdministrator() || $authAdmin->can('*'));

        $superAdmin = SuperAdmin::select(['id', 'name', 'app_id', 'avatar', 'username', 'di', 'default', 'country_id'])
            ->with('country')
            ->when($restrictCountry, fn($q) => $q->where('country_id', $authAdmin->country_id))
            ->find($id);
        if (!$superAdmin) {
            $superAdmin = SuperAdmin::withTrashed()
                ->select(['id', 'name', 'app_id', 'avatar', 'username', 'di', 'default', 'country_id'])
                ->with('country')
                ->when($restrictCountry, fn($q) => $q->where('country_id', $authAdmin->country_id))
                ->findOrFail($id);
        }

        $defaultImage = asset("images/businessman-icon.jpg");
        $imageUrl = getImagePath($superAdmin->avatar);
        if (!isImageExists($imageUrl)) {
            $imageUrl = $defaultImage;
        }
        $superAdmin->display_image = $imageUrl;

        $agencies = $transactions = $target_history = null;
        $rewards = null;
        $totals = Charge::selectRaw("
            SUM(CASE WHEN user_type = ? AND user_id = ? THEN amount ELSE 0 END) as total_charges,
            SUM(CASE WHEN charger_type = ? AND charger_id = ? THEN amount ELSE 0 END) as total_spent
        ", [
            UserTypeEnum::SUPER_ADMIN,
            $superAdmin->id,
            UserTypeEnum::SUPER_ADMIN,
            $superAdmin->id
        ])
            ->first();

        $totalCharges = $totals->total_charges;
        $totalSpent   = $totals->total_spent;
        $types = ['vip', 'badge', 'ware'];
        $type = request()->get('type', 'vip');
        $bds = Bd::where('parent_id', $id)->with('appUser')->paginate(10, ['*'], 'bd_page');
        $prefix = dashboardName();

        $subSuperAdmins = $superAdmin->subSuperAdmins()->with('appUser')->paginate(10, ['*'], 'sub_super_admin_page');

        // Load ALL tab data at once for client-side tab switching (no page reload)
        $agencies = $superAdmin->agencies()->paginate(10, ['*'], 'agencies_page');
        $rewards = SuperAdminReward::where('super_admin_id', $superAdmin->id)->where('type', $type)->with('ware', 'vip', 'badge')->paginate(10, ['*'], 'reward_page');

        return view('SuperAdmin::super_admin_profile', compact('superAdmin', 'defaultImage', 'agencies', 'totalCharges', 'totalSpent', 'prefix', 'type', 'types', 'rewards', 'bds', 'subSuperAdmins'));
    }

    public function profilePreview()
    {
        if (!session('preview_superadmin') || !session('country_id')) {
            abort(404, __('not found'));
        }

        $tab = request()->query('tab', 'agencies');
        $countryID = Common::filterCountryIds();


        $superAdmin = SuperAdmin::select(['id', 'name', 'app_id', 'avatar', 'username', 'default', 'country_id'])
            ->with('country')->whereIn('country_id', $countryID)->firstOrFail();

        $defaultImage = asset("images/businessman-icon.jpg");
        $imageUrl = getImagePath($superAdmin->avatar);
        if (!isImageExists($imageUrl)) {
            $imageUrl = $defaultImage;
        }
        $superAdmin->display_image = $imageUrl;

        $agencies = $transactions = $target_history = null;

        $totals = Charge::selectRaw("
            SUM(CASE WHEN user_type = ? AND user_id = ? THEN amount ELSE 0 END) as total_charges,
            SUM(CASE WHEN charger_type = ? AND charger_id = ? THEN amount ELSE 0 END) as total_spent
        ", [
            UserTypeEnum::SUPER_ADMIN,
            $superAdmin->id,
            UserTypeEnum::SUPER_ADMIN,
            $superAdmin->id
        ])
            ->first();

        $totalCharges = $totals->total_charges;
        $totalSpent   = $totals->total_spent;
        $bds = Bd::where('parent_id', $superAdmin->id)->with('appUser')->paginate(10, ['*'], 'bd_page');
        $subSuperAdmins = $superAdmin->subSuperAdmins()->with('appUser')->paginate(10, ['*'], 'sub_super_admin_page');

        $types = ['vip', 'badge', 'ware'];
        $type = request()->get('type', 'vip');

        // Load ALL tab data at once for client-side tab switching (no page reload)
        $agencies = $superAdmin->agencies()->paginate(10, ['*'], 'agencies_page');
        $rewards = SuperAdminReward::where('super_admin_id', $superAdmin->id)->where('type', $type)->with('ware', 'vip', 'badge')->paginate(10, ['*'], 'reward_page');

        $prefix = dashboardName();

        return view('SuperAdmin::super_admin_profile', compact('superAdmin', 'defaultImage', 'agencies', 'totalCharges', 'totalSpent', 'prefix', 'type', 'types', 'rewards', 'bds', 'subSuperAdmins'));
    }

    protected function detail($id)
    {
        $show = new Show(SuperAdmin::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('username', __('Username'));
        $show->field('avatar', __('Avatar'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('app_id', __('App id'));

        $this->extendShow($show);

        return $show;
    }




    public function searchBySuperAdmin(Request $request)
    {
        $key = $request->q;
        $page = $request->get('page', 1);
        $perPage = 10;
        return SuperAdmin::selectRaw('concat(username, " - ", id) as name, id')
            ->where(function ($query) use ($key) {
                $query->where('username', 'like', '%' . $key . '%')
                    ->orWhere('id', 'like', '%' . $key . '%');
            })
            ->paginate($perPage, ['*'], 'page', $page);
    }




    public function deleteSubSuperAdmin($id)
    {
        $authAdmin = auth()->user();
        $restrict = !($authAdmin->isAdministrator() || $authAdmin->can('*'));

        $oldUser = DB::table('admin_users')
            ->where('id', $id)
            ->where('type', UserTypeEnum::SUB_ADMIN)
            ->when($restrict, fn($q) => $q->where('parent_id', $authAdmin->id))
            ->first();
        if (!$oldUser) {
            return response()->json([
                'status' => false,
                'message' => trans('message.notFoundGift'),
            ], 404);
        }
        $OldUserAppId = User::find($oldUser->app_id);
        if ($OldUserAppId) {
            $OldUserAppId->is_sub_super_admin = 0;
            $OldUserAppId->save();
        }

        // Delete the SubAdmin record
        DB::table('admin_users')->where('id', $oldUser->id)->delete();

        return response()->json([
            'status' => true,
            'message' => __('done')
        ]);
    }
}



