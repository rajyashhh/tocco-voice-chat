<?php

namespace App\Admin\Controllers;

use App\Models\UserWallet;
use Carbon\Carbon;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class UserWalletController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public function title()
    {
        return __('UserWallet');
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */

     


     protected function grid()
    {
        $grid = new Grid(new UserWallet());
        $grid->model()->with(['user:id,name,uuid', 'user.profile:id,user_id,avatar']);

        $grid->column('id', __('Id'));
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user_id', __('user_id'));
            });
        });
        $grid->column('user.name', __('User'))->display(function () {
            $name = $this->user?->name ?? '';
            $uid = $this->user?->uuid ?? '';
            $path = $this->user?->profile?->avatar ?? null;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;
        
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
        
            $image = handleShowImageWithTypes($this->user->id ?? 0, $url, 40, 40);
            $showUrl = $this->user ? url("admin/users/{$this->user->id}") : "#";
        
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        });
               
        $grid->column('value', __('Value'));
        $grid->column('cut_amount', __('Cut amount'));
        $grid->column('pending_value', __('Pending value'));
        $grid->column('created_at', __('Created at'))->display(function ($created_at) {
            return Carbon::parse($created_at)->format('Y-m-d H:i');
        }); 
        
        $grid->column('View transactions')->display(function () {
            return "<a href='" . admin_url("wallet-transactions?user_id={$this->user_id}") . "'>عرض</a>";
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
        $show = new Show(UserWallet::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('value', __('Value'));
        $show->field('cut_amount', __('Cut amount'));
        $show->field('pending_value', __('Pending value'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UserWallet());

        $form->number('user_id', __('User id'));
        $form->decimal('value', __('Value'))->default(0.00);
        $form->decimal('cut_amount', __('Cut amount'))->default(0.00);
        $form->decimal('pending_value', __('Pending value'))->default(0.00);

        return $form;
    }
}
