<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use App\Admin\Actions\DeleteBdAction;
use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\Bd;
use App\Models\BdAgencyHostSallary;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Carbon;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;

class BdController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'BD';
    public $permission_name = 'Bds';

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
        return parent::edit($id, $content
            ->title(trans('BD'))
            ->body($this->form()->edit($id)));
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
        $authSuperAdmin = auth()->user();
        $authId = auth()->user()->type == 'country' ? $authSuperAdmin->id : $authSuperAdmin->parent_id;

        $grid = new Grid(new Bd());
        $grid->model()->where('parent_id', $authId)
            ->where('country_id', $authSuperAdmin->country_id)

            ->where('admin_users.type', 'bd')
            ->with(['bdSalaries', 'appUser.packs', 'appUser.profile', 'creator', 'country', 'createdBy'])
            ->withSum('bdSalaries', 'salary')
            ->withSum('bdSalaries', 'cut_amount')
            ->withCount('agencies as total_agencies')
            ->orderByDesc('admin_users.id');

        $grid->filter(function ($filter) {
            $filter->like('appUser.uuid', __('App User UUID'));
            $filter->like('appUser.name', __('User Name'));
        });
        $grid->column('id', __('Id'))->sortable();
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
            $showUrl = url("superadmin/profile/{$this->id}");

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
        })->sortable();


        $grid->column('default', trans('default_status'))
            ->switch(Common::getSwitchStates())
            ->display(function ($enable) {
                return $enable;
            })->sortable();

        $grid->column('app_id', __('user'))->display(function ($name) {
            $user = $this->appUser;
            if (request()->filled('_export_')) {
                return $user->name;
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
                         <span style='text-decoration: underline; cursor: pointer;'>$user->name</span>
                        </a>
                        <span style='font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        })->sortable();

        $grid->column('createdBy.name', __('created by'))->display(function () {
            $user = $this->createdBy;
            $name = $user->name ?? '';

            if (request()->filled('_export_')) {
                return $name;
            }
            if (!$user) return "<span style='color: red;'>غير مرتبط</span>";

            $id = $user->id ?? 'غير معروف';
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
                        <span style='font-size: smaller;'>ID: $id</span>
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
            return app(\App\Admin\Services\CreatorService::class)->show($creatorId);
        });
        $grid->column('created_at', __('Created at'))->display(function ($date) {
            $carbonDate = Carbon::parse($date);
            $locale = App::getLocale();
            $carbonDate->locale($locale);
            return $carbonDate->translatedFormat('d F Y H:i'); // مثال: 22 مايو 2025 14:30
        })->sortable();

        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            $actions->disableDelete();
            if (Admin::user()->can('delete-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new DeleteBdAction());
            }
        });

        $grid->disableRowSelector();

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

        $form->text('username', __('username'))->creationRules(['required', "unique:admin_users,username,{{id}}"])->updateRules(['required', "unique:admin_users,username,{{id}}"]);;
        $form->password('password', __('Password'))->rules('required');
        $form->image('avatar', __('img'));

        $form->hidden('created_by')->default(auth()->id());

        $form->hidden('transfer_salary', __('transfer_salary'));
        $form->hidden('default', __('default'))->default(0);

        if ($form->isEditing()) {
            $form->select('app_id', __('validation.select_user'))->options(function ($value) {
                $ops2 = [];
                foreach (User::Where('id', $value)->get() as $user) {
                    $ops2[$user->id] = $user->uuid . '_' . $user->name;
                }
                return $ops2;
            })->ajax('/api/search/users-bd', 'id', 'name')->required()->help('لا يمكن التعديل إلا إذا لم يكن هناك مستخدم مرتبط، أو كان المستخدم مرتبطًا لكن تم حذفه.');
        } else {
            $form->select('app_id', __('validation.select_user'))->options(function ($value) {
                $ops2 = [];
                foreach (User::Where('id', $value)->get() as $user) {
                    $ops2[$user->id] = $user->uuid . '_' . $user->name;
                }
                return $ops2;
            })->ajax('/api/search/users-bd', 'id', 'name')->required();

            //            $form->switch('default', __('set_as_default'))
            //                ->help(__('make_bd_default'));
        }

        $user = auth()->user();
        $form->hidden('type', __('Type'))->value('bd');
        $form->hidden('transfer_salary', __('transfer_salary'));

        $authId = auth()->user()->type == 'country' ? $user->id : $user->parent_id;
        $form->hidden('parent_id', __('Super Admin'))->value($authId);

        $form->hidden('country_id', __('country'))->value($user->country_id);

        $form->saving(function (Form $form) {
            $isEditing = $form->isEditing();
            if ($isEditing) {
                $originalAppId = $form->model()->getOriginal('app_id');
                $newAppId = $form->input('app_id');
                if ($originalAppId != $newAppId && $newAppId != null) {

                    $OldUserAppId = User::find($originalAppId);
                    if ($OldUserAppId) {
                        $OldUserAppId->is_bd = 0;
                        $OldUserAppId->save();
                    }
                    $newUserAppId = User::find($newAppId);
                    $newUserAppId->is_bd = 1;
                    $newUserAppId->save();
                    $form->app_id = $newAppId;
                }
            }

            if ($form->password && $form->model()->password != $form->password) {
                $form->password   = Hash::make($form->password);
            }
        });

        $form->saved(function (Form $form) {
            $userId = $form->model()->id;
            $userAppId = $form->model()->app_id;

            $userApp = User::find($userAppId);
            if (isset($userApp)) {
                $userApp->is_bd = 1;
                $userApp->save();
            }

            $role = DB::table('admin_roles')->where('slug', 'bd')->first();

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
        });

        return $form;
    }

    public function profile($id)
    {
        $year = request('year') ?? now()->year;
        $month = request('month') ?? now()->month;
        $tab = request()->query('tab', 'agencies');

        $bd = Bd::select(['id', 'name', 'app_id', 'avatar', 'username', 'default'])->findOrFail($id);

        $defaultImage = asset("images/icon-agency.jpg");
        $imageUrl = getImagePath($bd->avatar);
        if (!isImageExists($imageUrl)) {
            $imageUrl = $defaultImage;
        }
        $bd->display_image = $imageUrl;

        $agencies = $transactions = $target_history = null;

        switch ($tab) {
            case 'agencies':
                $agencies = $bd->agencies()->paginate(10, ['*'], 'agencies_page');
                break;

            case 'transactions':
                $transactions = $bd->transactions()
                    ->select('id', 'agency_id', 'user_id', 'usd', 'amount', 'created_at', 'user_charger_type', 'user_type')
                    ->with('receiveragency')
                    ->latest()
                    ->paginate(10, ['*'], 'transactions_page');
                break;

            case 'target_history':
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
                    ->where('bd_id', $bd->id)
                    ->where('amount', '!=', 0)
                    ->where('year', $year)
                    ->latest()
                    ->paginate(10, ['*'], 'target_history_page');
                break;
        }

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
}


