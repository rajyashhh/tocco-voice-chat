<?php

namespace Modules\Country\Http\Controllers\Admin;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\Country\Actions\Admin\RestoreSuperAdminAction;
use Modules\Country\Entities\SuperAdmin;

class RestoreSuperAdminController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'SuperAdmin';
    public $permission_name = 'restore-super-admin';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('restore super admin'))
            ->body($this->grid()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SuperAdmin());
        $grid->model()->onlyTrashed()->with([
            'appUser',
            'appUser.profile',
            "appUser.country:id,name,e_name",
            'country',
            'appUser.senderLevel',
            'appUser.receiverLevel',
            'appUser.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
        ])->orderBy('deleted_at', 'desc');

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
       

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->appUser);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('country_name', __('Country'))->display(function () {
            $locale = app()->getLocale(); // get current locale
            return $locale === 'en' ? (@$this->country->e_name ?? @$this->country->name) : (@$this->country->name ?? @$this->country->e_name);
        });
        if (Admin::user()->can('restore-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            // Pre-fetch all country_ids that already have an active (non-trashed) super admin to avoid N+1 queries
            $occupiedCountryIds = SuperAdmin::pluck('country_id')->toArray();

            $grid->column('return', __('restore'))->display(function () use ($occupiedCountryIds) {
                return in_array($this->country_id, $occupiedCountryIds)
                    ? '<span style="color: red;">' . __('can not restore this super admin') . '</span>'
                    : (new RestoreSuperAdminAction($this->id))->render();
            });
        }

        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();

        return $grid;
    }
}
