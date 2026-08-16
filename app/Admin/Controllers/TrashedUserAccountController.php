<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Admin\Actions\RestoreUserAccount;
use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Admin\Actions\SoftDeleteUserAccount;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;

class TrashedUserAccountController extends  MainController
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
    public $permission_name = 'deleted-accounts';
    use HasResourceActions;


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Deleted Accounts'))
            ->body($this->grid()));
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $countryID = Common::filterCountryIds();

        $grid->model()->with([
            'profile',
            'packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
        ])
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->onlyTrashed()->orderByDesc('deleted_at');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('uuid', __('uuid'));
            });
        });
        $grid->column('id', __('Id'));
        $grid->column('name', __('user'))->display(function () {
            $name = $this->name;
            $uuid = $this->uuid;
            $phone = $this->phone ?: '-'; // إذا لم يكن هناك رقم هاتف، عرض "-"
            $defaultImage = asset("images/businessman-icon.jpg");
            $avatarPath = @$this->profile->avatar;
            $avatar = getImagePath($avatarPath) ?? $defaultImage;

            if (!isImageExists($avatar)) {
                $avatar = $defaultImage;
            }

            $userUrl = admin_url('users/' . $this->id);

            return "<div style='display: flex; align-items: center; gap: 10px; background: var(--bg-color); padding: 10px; border-radius: 8px;'>
                        <img src='$avatar' alt='User Avatar' style='width: 40px; height: 40px; border-radius: 50%;'>
                        <div>
                            <a href='$userUrl' style='font-weight: bold; text-decoration: none;'>$name</a><br>
                            <span style='font-size: smaller;'>UUID: $uuid</span><br>
                            <span style='font-size: smaller;'>📞 $phone</span>
                        </div>
                    </div>";
        });

        // $grid->column('phone', __('Phone'));
        $grid->column('deleted_at', __('Deleted at'))->diffForHumans();
        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            $model = $actions->row;
            if ((Admin::user()->can('restore-user-account-switch-' . $permission) || Admin::user()->can('*'))) {
                $actions->add(new RestoreUserAccount($model->id));
            }
            if ((Admin::user()->can('delete-user-account-switch-' . $permission) || Admin::user()->can('*'))) {
                $actions->add(new SoftDeleteUserAccount($model->id));
            }
            $actions->disableEdit();
            $actions->disableView();
            $actions->disableDelete();
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


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());


        return $form;
    }
}
