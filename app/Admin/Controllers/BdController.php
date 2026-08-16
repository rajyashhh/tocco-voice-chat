<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Admin\Actions\DeleteBdAction;
use App\Admin\Controllers\Concerns\ScopesCountryRecords;
use App\Models\Bd;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\Country;
use Modules\Country\Entities\SuperAdmin;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use App\Models\BdAgencyHostSallary;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Modules\Milestones\Entities\Milestone;

class BdController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use ScopesCountryRecords;

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'BD';
    public $permission_name = 'BD';

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
            content: view('admin.grid.bd.description'),
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
        $this->findInCountryScope(Bd::class, $id);

        return parent::show($id, $content
            ->title(trans('BD'))
            ->body($this->profile($id)));
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
        $this->findInCountryScope(Bd::class, $id);

        return parent::edit($id, $content
            ->title(trans('BD'))
            ->body($this->form()->edit($id)));
    }

    public function update($id)
    {
        $this->findInCountryScope(Bd::class, $id);

        return parent::update($id);
    }

    public function destroy($id)
    {
        $this->findInCountryScope(Bd::class, $id);

        return parent::destroy($id);
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('BD'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Bd());
        $countryID = Common::filterCountryIds();

        $superAdmin = [];

        if ($countryID) {

            $superAdmin = SuperAdmin::select(['id', 'country_id'])->whereIn('country_id', $countryID)->first();
        }

        $grid->model()
            ->when($countryID, function ($query) use ($superAdmin) {

                $query->where('parent_id', @$superAdmin->id);
            })

            ->with([
                'bdSalaries',
                'appUser.packs',
                'creator',
                'appUser.profile',
                'parent.appUser.packs',
                'createdBy.agencies',
                'createdBy',
                'country'
            ])
            ->withSum('bdSalaries', 'salary')
            ->withSum('bdSalaries', 'cut_amount')
            ->withCount('agencies as total_agencies')
            ->orderByDesc('id');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->expand();

            // Column 1: User & BD Info
            $filter->column(1 / 3, function ($filter) {
                $filter->like('appUser.uuid', __('App User UUID'));
                $filter->like('appUser.name', __('User Name'));
                $filter->like('username', __('BD Username'));
            });

            // Column 2: Country & Super Admin
            $filter->column(1 / 3, function ($filter) {
                $filter->equal('country_id', __('Country'))->select(Country::pluck('name', 'id')->toArray());
                $filter->equal('parent_id', __('Super Admin'))->select(SuperAdmin::pluck('username', 'id')->toArray());
                $filter->equal('transfer_salary', __('transfer_salary'))->select([0 => __('No'), 1 => __('Yes')]);
            });

            // Column 3: Date & ID
            $filter->column(1 / 3, function ($filter) {
                $filter->equal('id', __('Id'));
                $filter->between('created_at', __('Created at'))->datetime();
            });
        });
        $grid->column('id', __('Id'));
        $grid->column('username', __('Bd'))->display(function ($name) {
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
            $showUrl = url("admin/usersBd/{$this->id}");

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
        $grid->column('default', __('default_status'))->display(function () {
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

        $grid->column('parent.name', __('Super Admin'))->display(function () {
            $user = $this->parent;
            $name = $user->name ?? '';

            if (request()->filled('_export_')) {
                return $name;
            }
            if (!$user) return "<span style='color: red;'>غير مرتبط</span>";

            $uid = $user->id ?? 'غير معروف';
            $path = $user->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = url("admin/superadmin-users/{$user->id}");

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


        $grid->column('agencies_count', __('Agencies Count'))->display(function () {
            return $this->total_agencies;
        });


        $grid->column('total_salary', __('total proft'))->display(function () {
            return truncateAndTrim($this->bd_salaries_sum_salary ?? 0, 2);
        });

        $grid->column('current_balance', __('current_balance'))->display(function () {
            $total = floatval($this->bd_salaries_sum_salary ?? 0);
            $cut   = floatval($this->bd_salaries_sum_cut_amount ?? 0);
            return truncateAndTrim($total - $cut, 2);
        });

        $grid->column('total_cut', __('Cut amount'))->display(function () {
            return truncateAndTrim($this->bd_salaries_sum_cut_amount ?? 0, 2);
        });

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



        if (Admin::user()->can('stop-salary-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $col = $grid->column('transfer_salary', __("transfer_salary"))
                ->display(function () {
                    return $this->transfer_salary ? 1 : 0;
                });

            if (! request()->filled('_export_')) {
                $col->switch(Common::getSwitchStates());
            }
        }

        $grid->column('created_by', __('Creator'))->display(function ($creatorId) {
            $creator = $this->createdBy;
            return app(\App\Admin\Services\CreatorService::class)->showV2($creator);
        });
        $grid->column('created_at', __('Created at'))->display(function ($date) {
            $carbonDate = Carbon::parse($date);
            $locale = App::getLocale();
            $carbonDate->locale($locale);
            return $carbonDate->translatedFormat('d F Y H:i'); // مثال: 22 مايو 2025 14:30
        });

        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            $actions->disableDelete();
            $model = $actions->row;
            if (Admin::user()->can('delete-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new \App\Admin\Actions\DeleteBdAction());
            }

            // if (Admin::user()->can('charge-switch-' . $permission) || Admin::user()->can('*')) {
            //     $actions->add(new BdChargeSwitchAction());
            // }
            // $actions->add(new MakeBdDefultAction($model->id));
        });

        if (Admin::user()->can('browse-milestone') || Admin::user()->can('*')) {
            $grid->tools(function (Grid\Tools $tools) {
                $milestoneId = Milestone::where('slug', 'bd')->first();
                $url = $milestoneId?->id ? url('admin/milestone-rewards/' . $milestoneId->id) : '';
                $milestone = __('milestone');   // Translates 'milestone' via your language files

                $customButtonHTML = <<<HTML
                <div style="display: contents; align-items: center;">
                    <a href="{$url}" class="btn btn-sm btn-info" style="margin-right: 10px;">
                         {$milestone}
                    </a>
                </div>
            HTML;

                // Append the custom HTML button to the grid's toolbar
                $tools->append($customButtonHTML);
            });

            $grid->tools(function ($tools) {
                $logoutUrl = route('admin.bd.logout');
                $loginText = __('login');
                $areaManagerUrl = url('/bd/login');

                $customButtonHTML = <<<HTML
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

                $tools->append($customButtonHTML);
            });
        }

        $grid->disableRowSelector();

        $this->extendGrid($grid);
        return $grid;
    }




    /**
     * Make a form builder.
     *
     * @return Form
     */

    protected function form()
    {
        $form = new Form(new Bd());
        $this->disableFormTools($form);

        $form->text('username', __('username'))->creationRules(['required', "unique:admin_users,username,{{id}}"])->updateRules(['required', "unique:admin_users,username,{{id}}"]);;
        $form->password('password', __('Password'))->rules('required');
        $form->image('avatar', __('img'));

        $form->hidden('created_by')->default(auth()->id());

        $form->hidden('transfer_salary', __('transfer_salary'));

        //        $form->select('super_admin_id', 'Select Super Admin')
        //            ->options(function ($value) {
        //                $ops = [];
        //                foreach (SuperAdmin::where('id', $value)->get() as $admin) {
        //                    $ops[$admin->id] = $admin->username;
        //                }
        //                return $ops;
        //            })
        //            ->ajax('/api/search/users-superadmin', 'id', 'name')
        //            ->rules('required')
        //            ->when('!=', null, function ($form) {
        //
        //                $form->select('app_id', __('validation.select_user'))
        //                    ->options(function ($value) {
        //                        $ops2 = [];
        //                        foreach (\App\Models\User::where('id', $value)->get() as $user) {
        //                            $ops2[$user->id] = $user->uuid . '_' . $user->name;
        //                        }
        //                        return $ops2;
        //                    })
        //                    ->ajax('/api/search/users-by-country', 'id', 'name')
        //                    ->rules('required');
        //
        //                $form->switch('default', __('set_as_default'))
        //                    ->help(__('make_bd_default'));
        //
        //                if ($form->isEditing()) {
        //                    $form->select('app_id', __('validation.select_user'))
        //                        ->help('لا يمكن التعديل إلا إذا لم يكن هناك مستخدم مرتبط، أو كان المستخدم مرتبطًا لكن تم حذفه.');
        //                }
        //            });

        //        $form->select('parent_id', __('select super admin'))->options(function ($value) {
        //            $ops = [];
        //            foreach (SuperAdmin::where('id', $value)->get() as $admin) {
        //                $ops[$admin->id] = $admin->username;
        //            }
        //            return $ops;
        //        })->ajax('/api/search/users-superadmin2', 'id', 'name')->rules('required');

        if ($form->isEditing()) {
            $form->select('app_id', __('validation.select_user'))->options(function ($value) {
                if (!$value) return [];
                $user = User::find($value);
                return $user ? [$user->id => $user->uuid . '_' . $user->name] : [];
            })->ajax('/api/search/users-bd', 'id', 'name')->required()->help('لا يمكن التعديل إلا إذا لم يكن هناك مستخدم مرتبط، أو كان المستخدم مرتبطًا لكن تم حذفه.');
        } else {
            $form->select('app_id', __('validation.select_user'))->options(function ($value) {
                if (!$value) return [];
                $user = User::find($value);
                return $user ? [$user->id => $user->uuid . '_' . $user->name] : [];
            })->ajax('/api/search/users-bd', 'id', 'name')->required();

            //            $form->switch('default', __('set_as_default'))
            //                ->help(__('make_bd_default'));
        }

        $countryOps = [null => __('no country')];
        $scopeIds = $this->creatableCountryIds();
        $countries = Country::select('id', 'name', 'e_name')
            ->when($scopeIds, fn ($query) => $query->whereIn('id', $scopeIds))
            ->get();
        foreach ($countries as $country) {
            $countryOps[$country->id] = App::isLocale('en') ? ($country->e_name ?? $country->name) : $country->name;
        }
        $form->select('country_id', trans('country'))->options($countryOps)->required();

        $form->hidden('type', __('Type'))->value('bd');
        $form->hidden('transfer_salary', __('transfer_salary'));

        $form->saving(function (Form $form) {
            // Country-scope guard (create AND update/transfer): a scoped manager may
            // only write a BD in their own countries, regardless of the posted
            // country_id. Same authority check as SetCountry's session source.
            // Mandatory server-side line of defense — must run before any create/edit
            // branch or write. $form->country_id is the value the save will persist.
            if ($response = $this->rejectCountryOutOfScope($form->country_id ?? optional($form->model())->country_id)) {
                return $response;
            }

            $isEditing = $form->isEditing();
            if ($isEditing) {
                $originalAppId = $form->model()->getOriginal('app_id');
                $newAppId = $form->input('app_id');

                $superAdmin = SuperAdmin::where('country_id', request('country_id'))->first() ?? SuperAdmin::where('default', 1)->first();
                $form->model()->parent_id = $superAdmin->id;

                if ($originalAppId !=  $newAppId) {
                    $OldUserAppId = User::find($originalAppId);
                    if ($OldUserAppId) {
                        $OldUserAppId->is_bd = 0;
                        $OldUserAppId->save();
                    }

                    $newUserAppId = User::find($newAppId);
                    if (isset($newUserAppId)) {
                        $newUserAppId->is_bd = 1;
                        $newUserAppId->save();
                        $form->app_id = $newAppId;
                    }
                }
            } else {
                $selectedCountryId = $form->country_id;
                $superAdminId = SuperAdmin::where('country_id', $selectedCountryId)->first()?->id ?? SuperAdmin::where('default', 1)->where('country_id', 0)->first()?->id;

                if ($superAdminId) {
                    $form->model()->parent_id = $superAdminId;
                }

                $userAppId = $form->input('app_id');
                $userApp = User::find($userAppId);
                if (isset($userApp)) {
                    $userApp->is_bd = 1;
                    $userApp->save();
                }
            }

            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }

            $role = DB::table('admin_roles')->where('slug', 'bd')->first();
            $userId = $form->model()->id;

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
        });

        return $form;
    }



    public function profile($id)
    {
        $year = request('year') ?? now()->year;
        $month = request('month') ?? now()->month;
        $tab = request()->query('tab', 'agencies');

        $bd = Bd::select('id', 'name', 'app_id', 'avatar', 'username', 'default')->findOrFail($id);

        $id = $bd->id;
        $defaultImage = asset("images/businessman-icon.jpg");
        $imageUrl = getImagePath($bd->avatar);
        if (!isImageExists($imageUrl)) {
            $imageUrl = $defaultImage;
        }
        $bd->display_image = $imageUrl;

        $agencies = $bd->agencies()->paginate(10, ['*'], 'agencies_page');

        $transactions = $bd->transactions()
            ->select('id', 'agency_id', 'user_id', 'usd', 'amount', 'created_at', 'user_charger_type', 'user_type')
            ->with('receiveragency')
            ->latest()
            ->paginate(10, ['*'], 'transactions_page');

        $target_history = BdAgencyHostSallary::select(
            'id',
            'bd_id',
            'agency_id',
            'amount',
            'month',
            'year',
            'bd_user_id',
            'created_at'
        )
            ->where('bd_id', $bd->id)
            ->where('amount', '!=', 0)
            ->where('year', $year)
            ->latest()
            ->paginate(10, ['*'], 'target_history_page');

        return view('admin.bd.bd_profile', compact('bd', 'agencies', 'transactions', 'target_history'));
    }


    protected function detail($id)
    {
        $show = new Show(Bd::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('username', __('Username'));
        $show->field('avatar', __('Avatar'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('app_id', __('App id'));

        $this->extendShow($show);

        return $show;
    }

    public function sync($days = 0)
    {
        $days = request()->query('days', 0);
        $bds = DB::table('admin_users')
            ->where('type', 'bd')
            ->where('app_id', '!=', 0)
            ->get();

        $updated = 0;

        foreach ($bds as $bd) {
            $query = DB::table('agencies')
                ->where('bd_id', $bd->app_id);

            if ($days > 0) {
                $query->where('created_at', '<=', now()->subDays($days));
            }

            $affected = $query->update(['bd_id' => $bd->id]);
            $updated += $affected;
        }

        return response()->json([
            'status' => 'success',
            'message' => $updated
        ]);
    }


    protected function professionalBd()
    {
        $authSuperAdmin = auth()->user();
        $grid = new Grid(new Bd());

        $countriesIds = Common::areaCountries($authSuperAdmin->id);
        $grid->model()
            ->whereNotIn('country_id', $countriesIds)
            ->whereHas('appUser', function ($query) use ($countriesIds) {
                $query->whereIn('country_id', $countriesIds);
            })
            ->with(['bdSalaries', 'appUser.packs', 'appUser.profile', 'country'])
            ->withSum('bdSalaries', 'salary')
            ->withSum('bdSalaries', 'cut_amount')
            ->withCount('agencies as total_agencies')
            ->orderByDesc('id');

        $grid->filter(function ($filter) {
            $filter->like('appUser.uuid', __('App User UUID'));
            $filter->like('appUser.name', __('User Name'));
            $filter->equal('country_id', __('Country'))->select(Country::pluck('name', 'id')->toArray());
        });
        $grid->column('id', __('Id'));
        $grid->column('username', __('Bd'))->display(function ($name) {
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
            $showUrl = url("areaManager/user-Bds/{$this->id}");

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
            $showUrl = url("superadmin/users/{$user->id}");

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

        $grid->column('agencies_count', __('Agencies Count'))->display(function () {
            return $this->total_agencies;
        });


        $grid->column('total_salary', __('total proft'))->display(function () {
            return truncateAndTrim($this->bd_salaries_sum_salary ?? 0, 2);
        });

        $grid->column('current_balance', __('current_balance'))->display(function () {
            $total = floatval($this->bd_salaries_sum_salary ?? 0);
            $cut   = floatval($this->bd_salaries_sum_cut_amount ?? 0);
            return truncateAndTrim($total - $cut, 2);
        });

        $grid->column('total_cut', __('Cut amount'))->display(function () {
            return truncateAndTrim($this->bd_salaries_sum_cut_amount ?? 0, 2);
        });

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


        if (Admin::user()->can('stop-salary-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $col = $grid->column('transfer_salary', __("transfer_salary"))
                ->display(function () {
                    return $this->transfer_salary ? 1 : 0;
                });

            if (! request()->filled('_export_')) {
                $col->switch(Common::getSwitchStates());
            }
        }

        $grid->column('created_at', __('Created at'))->display(function ($date) {
            $carbonDate = Carbon::parse($date);
            $locale = App::getLocale();
            $carbonDate->locale($locale);
            return $carbonDate->translatedFormat('d F Y H:i');
        });

        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            $actions->disableDelete();
            if (Admin::user()->can('delete-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new DeleteBdAction());
            }
        });

        $grid->disableRowSelector();
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->disableExport();
        return Admin::content(function (Content $content) use ($grid) {
            $content->header(__('Professional BD'));
            $content->description(__('Professional BD List'));
            $content->body($grid);
        });
    }
}
