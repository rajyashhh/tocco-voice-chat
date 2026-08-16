<?php

namespace Modules\Region\Http\Controllers;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Enums\Charges\UserTypeEnum;
use App\Helpers\Common;
use App\Models\Agency;
use App\Models\Bd;
use App\Models\Charge;
use App\Models\Country;
use App\Models\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\MessageBag;
use Modules\Country\Actions\Admin\DeleteSuperAdminsAction;
use Modules\Country\Entities\SuperAdmin;
use Modules\Country\Entities\SuperAdminReward;

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
        $countries = Common::areaCountries();

        $grid->model()->with([
            'appUser.country',
            'appUser.senderLevel',
            'appUser.receiverLevel',
            'appUser.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'creator',
            'country'
        ])
            // ->where('parent_id', auth()->id())
            ->whereIn('country_id',  $countries)
            ->orderByDesc('id');

        $grid->filter(function ($filter) {
            $filter->like('appUser.uuid', __('App User UUID'));
            $filter->like('appUser.name', __('User Name'));
        });
        $grid->column('id', __('Id'));
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
            $showUrl = url("areaManager/superadmin-users/{$this->id}");

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


        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->appUser);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

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

        $grid->column('created_by', __('Creator'))->display(function ($creatorId) {
            //            return app(\App\Admin\Services\CreatorService::class)->show($creatorId);
            return app(\App\Admin\Services\CreatorService::class)->show($this->creator);
        });
        $grid->column('created_at', __('Created at'))->display(function ($date) {
            $carbonDate = Carbon::parse($date);
            $locale = App::getLocale();
            $carbonDate->locale($locale);
            return $carbonDate->translatedFormat('d F Y H:i'); // مثال: 22 مايو 2025 14:30
        });
        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            if (Admin::user()->can('delete-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new DeleteSuperAdminsAction());
            }
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->disableExport();
        $grid->disableRowSelector();
        $this->extendGrid($grid);

        //        $this->extendGrid($grid);
        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SuperAdmin());
        $this->disableFormTools($form);

        $form->hidden('created_by')->default(auth()->id());
        $form->text('name', __('name'));
        $form->text('username', __('username'))->creationRules(['required', "unique:admin_users,username,{{id}}"])->updateRules(['required', "unique:admin_users,username,{{id}}"]);;
        $form->password('password', __('Password'))->rules('required');
        $form->image('avatar', __('img'));

        //        $form->hidden('transfer_salary', __('transfer_salary'));

        $form->select('country_id', trans('country'))->options(function ($value) {
            $ops       = [null => __('no country')];
            $authId = auth()->user()->type == 'region' ? auth()->id() : auth()->user()->parent_id;

            $authAdmin = \Modules\Region\Entities\AreaManager::find($authId);

            if ($authAdmin && method_exists($authAdmin, 'countriesQuery')) {
                $countries = $authAdmin->countriesQuery()
                    ->doesntHave('superAdmin')
                    ->orWhere('id', $value)
                    ->get(['id', 'name', 'e_name']);
                foreach ($countries as $country) {
                    $ops[$country->id] = App::isLocale('en') ? $country->e_name : $country->name;
                }
                return $ops;
            }
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
        $this->addPhoneFields($form);

        $user = auth()->user();
        $form->hidden('type', __('Type'))->value('country');
        //        $form->hidden('transfer_salary', __('transfer_salary'));
        $authId = auth()->user()->type == 'country' ? $user->id : $user->parent_id;
        $form->hidden('parent_id', __('Super Admin'))->value($authId);

        $form->saving(function (Form $form) {
            $isEditing = $form->isEditing();
            $superAdmin = SuperAdmin::where('phone_code', request('phone_code'))->where('phone', request('phone'));
            if ($isEditing) $superAdmin->where('id', '!=', $form->model()->id);
            $exists = $superAdmin->exists();

            if ($exists) {
                $error = new MessageBag([
                    'title' => 'Error',
                    'message' => trans('you used this phone before'),
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
            $superAdmin = $form->model();
            $userId = $form->model()->id;
            $userAppId = $form->model()->app_id;

            $userApp = User::find($userAppId);
            if (isset($userApp)) {
                $userApp->is_super_admin = 1;
                $userApp->save();
            }

            $role = DB::table('admin_roles')->where('slug', 'country')->first();

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
                    \App\Models\Admin::forgetCachedPermissionsFor($userId);
                }
            }
            $isEditing = $form->isEditing();
            if (!$isEditing) {
                $authId = auth()->user()->type === 'region'
                    ? auth()->user()->id
                    : auth()->user()->parent_id;
                \Cache::forget('super_admins_by_manager_' . $authId);
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

    protected function addPhoneFields(Form $form)
    {

        $form->text('phone', __('whatsApp number'))
            ->rules('required')
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

        $superAdmin = SuperAdmin::select(['id', 'name', 'app_id', 'avatar', 'username', 'di', 'default', 'country_id'])
            ->with('country')
            ->whereIn('country_id', Common::areaCountries())
            ->findOrFail($id);

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
        $bds = Bd::where('parent_id', $superAdmin->id)->with('appUser')->paginate(10, ['*'], 'bd_page');

        switch ($tab) {
            case 'agencies':
                $agencies = $superAdmin->agencies()->paginate(10, ['*'], 'agencies_page');
                break;
            case 'rewards':


                $rewards = SuperAdminReward::where('super_admin_id', $superAdmin->id)->where('type', $type)->with('ware', 'vip', 'badge')->paginate(10, ['*'], 'reward_page');
                break;
        }
        $prefix = dashboardName();
        return view('superadmin::super_admin_profile', compact('superAdmin', 'defaultImage', 'prefix', 'bds', 'agencies', 'totalCharges', 'totalSpent', 'type', 'types', 'rewards'));
    }

    public function profilePreview()
    {
        if (!session('preview_superadmin') || !session('country_id')) {
            abort(404, __('not found'));
        }

        $tab = request()->query('tab', 'agencies');
        $countryID = session('country_id');

        $superAdmin = SuperAdmin::select(['id', 'name', 'app_id', 'avatar', 'username', 'default', 'country_id'])
            ->with('country')->where('country_id', $countryID)->firstOrFail();

        $defaultImage = asset("images/icon-agency.jpg");
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

        switch ($tab) {
            case 'agencies':
                $agencies = $superAdmin->agencies()->paginate(10, ['*'], 'agencies_page');
                break;
        }

        return view('superadmin.super_admin_profile', compact('superAdmin', 'agencies', 'totalCharges', 'totalSpent'));
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

    //    public function sync($days = 0)
    //    {
    //        $days = request()->query('days', 0);
    //        $bds = DB::table('admin_users')
    //            ->where('type', 'bd')
    //            ->where('app_id', '!=', 0)
    //            ->get();
    //
    //        $updated = 0;
    //
    //        foreach ($bds as $bd) {
    //            $query = DB::table('agencies')
    //                ->where('bd_id', $bd->app_id);
    //
    //            if ($days > 0) {
    //                $query->where('created_at', '<=', now()->subDays($days));
    //            }
    //
    //            $affected = $query->update(['bd_id' => $bd->id]);
    //            $updated += $affected;
    //        }
    //
    //        return response()->json([
    //            'status' => 'success',
    //            'message' => $updated
    //        ]);
    //    }

}
