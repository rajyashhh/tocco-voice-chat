<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ScopesCountryRecords;
use App\Helpers\Common;
use App\Models\Country;
use App\Models\ShippingSuperAdmin;
use App\Models\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Country\Entities\SuperAdmin;
use Encore\Admin\Auth\Permission;

/**
 * Admin-side management of the Shipping Super Admin layer — the coin-only actor
 * that sits between the Country Manager and the Shipping Agencies. This is the
 * BD Super Admin's shipping counterpart: BD is dollar-denominated (bd_salaries),
 * this layer is coin-only (admin_users.di). It reads/creates the actors; every
 * money move stays inside the hardened shipping wallet service (untouched here).
 *
 * Mirrors BdController: grid on the type-scoped model, a create/edit form that
 * links an app user, pins the parent Country Manager by country, and binds the
 * shipping_super_admin role. No balance is ever written from this screen — the
 * di column is read-only.
 */
class ShippingSuperAdminController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use ScopesCountryRecords;

    protected $title = 'Shipping Super Admin';

    public $permission_name = 'shipping-super-admin';

    public function show($id, Content $content)
    {
        $this->findInCountryScope(ShippingSuperAdmin::class, $id);

        return parent::show($id, $content
            ->title(trans('Shipping Super Admin'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        $this->findInCountryScope(ShippingSuperAdmin::class, $id);

        return parent::edit($id, $content
            ->title(trans('Shipping Super Admin'))
            ->body($this->form()->edit($id)));
    }

    public function update($id)
    {
        $this->findInCountryScope(ShippingSuperAdmin::class, $id);

        return parent::update($id);
    }

    public function destroy($id)
    {
        $this->findInCountryScope(ShippingSuperAdmin::class, $id);

        return parent::destroy($id);
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Shipping Super Admin'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ShippingSuperAdmin());

        $countryID = Common::filterCountryIds();

        $grid->model()
            ->when($countryID, fn ($query) => $query->whereIn('country_id', $countryID))
            ->with(['appUser.profile', 'parent', 'createdBy', 'country'])
            ->orderByDesc('id');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->expand();

            $filter->column(1 / 2, function ($filter) {
                $filter->like('appUser.uuid', __('App User UUID'));
                $filter->like('appUser.name', __('User Name'));
                $filter->like('username', __('username'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('country_id', __('Country'))->select(Country::pluck('name', 'id')->toArray());
                $filter->equal('parent_id', __('Country Manager'))->select(SuperAdmin::pluck('username', 'id')->toArray());
                $filter->equal('id', __('Id'));
            });
        });

        $grid->column('id', __('Id'));

        $grid->column('username', __('Shipping Super Admin'))->display(function ($name) {
            if (request()->filled('_export_')) {
                return $name;
            }

            $id = $this->id ?? '-';
            $name = e($this->username ?? 'غير معروف');
            $defaultImage = asset('images/businessman-icon.jpg');
            $url = getImagePath($this->avatar) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = url('admin/shipping-super-admins/' . $this->id);

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
            if (!$user) {
                return "<span style='color: red;'>غير مرتبط</span>";
            }

            $uid = e($user->uuid ?? 'غير معروف');
            $name = e($name ?? '');
            $defaultImage = asset('images/businessman-icon.jpg');
            $url = getImagePath($user->profile?->avatar) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = url('admin/users/' . $user->id);

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

        $grid->column('parent.name', __('Country Manager'))->display(function () {
            $manager = $this->parent;
            if (request()->filled('_export_')) {
                return $manager->name ?? '';
            }
            if (!$manager) {
                return "<span style='color: red;'>غير مرتبط</span>";
            }

            return e($manager->username ?: $manager->name) . " <span style='font-size: smaller;'>(ID: {$manager->id})</span>";
        });

        // Coin balance (di) — the whole shipping layer is coin-only. Read-only.
        $grid->column('di', __('Coin Balance'))->display(function ($di) {
            return number_format((int) ($di ?? 0)) . ' 🪙';
        });

        $grid->column('country.name', __('country'))->display(function () {
            $country = $this->country;
            if (!$country) {
                return '-';
            }

            $name = app()->getLocale() === 'ar'
                ? ($country->name ?: $country->e_name)
                : ($country->e_name ?: $country->name);

            $flag = $country->flag ? getImagePath($country->flag) : null;

            return <<<HTML
                <div style="display:flex; align-items:center; gap:8px;">
                    <img src="$flag" alt="flag" width="20" height="20" style="border-radius:4px;">
                    <span>$name</span>
                </div>
            HTML;
        });

        $grid->column('created_by', __('Creator'))->display(function () {
            return app(\App\Admin\Services\CreatorService::class)->showV2($this->createdBy);
        });

        $grid->column('created_at', __('Created at'))->display(function ($date) {
            $carbonDate = Carbon::parse($date);
            $carbonDate->locale(App::getLocale());
            return $carbonDate->translatedFormat('d F Y H:i');
        });

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
        $form = new Form(new ShippingSuperAdmin());
        $this->disableFormTools($form);

        $form->text('username', __('username'))
            ->creationRules(['required', 'unique:admin_users,username,{{id}}'])
            ->updateRules(['required', 'unique:admin_users,username,{{id}}']);
        $form->password('password', __('Password'))->creationRules(['required']);
        $form->image('avatar', __('img'));

        $form->select('app_id', __('validation.select_user'))->options(function ($value) {
            if (!$value) {
                return [];
            }
            $user = User::find($value);
            return $user ? [$user->id => $user->uuid . '_' . $user->name] : [];
        })->ajax('/api/search/users-bd', 'id', 'name')->required();

        $countryOps = [];
        $scopeIds = $this->creatableCountryIds();
        $countryQuery = Country::select('id', 'name', 'e_name')
            ->when($scopeIds, fn ($query) => $query->whereIn('id', $scopeIds));
        foreach ($countryQuery->get() as $country) {
            $countryOps[$country->id] = App::isLocale('en') ? ($country->e_name ?? $country->name) : $country->name;
        }
        $form->select('country_id', trans('country'))->options($countryOps)->required();

        $form->hidden('created_by')->default(auth()->id());
        $form->hidden('type')->default(ShippingSuperAdmin::TYPE);

        $form->saving(function (Form $form) {
            // Country-scope guard: a scoped manager may only create/edit records in
            // their own countries, regardless of what the form posts. Same authority
            // check SetCountry uses at the session source. Mandatory server-side line
            // of defense (the restricted select is only cosmetic).
            $countryId = $form->country_id ?? $form->model()->country_id;
            if ($response = $this->rejectCountryOutOfScope($countryId)) {
                return $response;
            }

            // Pin the parent Country Manager by the selected country, falling back
            // to the platform default. Mirrors BdController::form()'s parent wiring.
            $parentId = SuperAdmin::where('country_id', $countryId)->first()?->id
                ?? SuperAdmin::where('default', 1)->where('country_id', 0)->first()?->id;

            if ($parentId) {
                $form->model()->parent_id = $parentId;
            }

            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
        });

        $form->saved(function (Form $form) {
            $role = DB::table('admin_roles')->where('slug', 'shipping_super_admin')->first();
            $userId = $form->model()->id;

            if ($role && $userId) {
                DB::table('admin_role_users')->updateOrInsert(
                    ['user_id' => $userId, 'role_id' => $role->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                \App\Models\Admin::forgetCachedPermissionsFor($userId);
            }
        });

        return $form;
    }

    protected function detail($id)
    {
        $show = new \Encore\Admin\Show(ShippingSuperAdmin::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('username', __('username'));
        $show->field('avatar', __('img'));
        $show->field('di', __('Coin Balance'))->as(fn ($di) => number_format((int) ($di ?? 0)) . ' 🪙');
        $show->field('country.name', __('country'));
        $show->field('parent.username', __('Country Manager'));
        $show->field('created_at', __('Created at'));

        $this->extendShow($show);

        return $show;
    }
}
