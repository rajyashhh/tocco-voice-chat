<?php

namespace Modules\RoleRewards\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Admin\Services\UserService;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\AdminController;
use Modules\RoleRewards\Entities\UserHistoryReward;
use Modules\RoleRewards\Entities\VUserHistoryReward;


class UserHistoryRewardController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public $permission_name = 'user-reward';

    public function index(Content $content)
    {

        return parent::index(
            $content
                ->header(__('User history rewards'))
                ->description(__('User history rewards'))
                ->body($this->grid())
        );
    }

    protected function grid()
    {
        $grid = new Grid(new VUserHistoryReward());

        $grid->model()->with([
            'user' => function ($query) {
                $query->select(['id', 'name', 'uuid', 'sender_level', 'received_level', 'country_id', 'special_id'])
                    ->with([
                        'profile:id,user_id,avatar',
                        'country',
                        'senderLevel',
                        'receiverLevel',
                        'packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                    ]);
            }
        ])
            ->orderByDesc('id');

        $grid->column('id', __('ID'))->sortable();

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('receive_name', __('receive_type'));

        $grid->column('reward', __('Rewards'))->display(function () {
            if ($this->reward_value) {
                if ($this->rewardable_type === \Modules\Achievement\Entities\Achievement::class) {
                    $path = $this->reward_value ?? 'achievement.png';
                    $imgTag = handleShowImageWithTypes($this->id, getImagePath($path), 50, 50);
                    return $imgTag;
                }
                return $this->reward_value;
            }


            $path = $this->reward_img ?? 'coin.png';
            $imgTag = handleShowImageWithTypes($this->id, getImagePath($path), 50, 50);

            return "<div>{$imgTag}</div><div>{$this->reward_name}</div>";
        });


        $grid->column('created_at', __('Created At'))
            ->display(fn($date) => \Carbon\Carbon::parse($date)->format('Y-m-d H:i'));

        $grid->filter(function ($filter) {
            $filter->equal('receive_category', __('receive_type'))->select([
                'Role' => __('Role'),
                'Milestone' => __('Milestone'),
            ]);
        });

        $grid->disableCreateButton();
        $grid->disableActions();

        Admin::script("
            if (window.innerWidth >= 1024) {
                $('.table-responsive').removeClass('table-responsive');
            }
        ");

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
        $show = new Show(UserHistoryReward::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('receive_type', __('Receive type'));
        $show->field('rewardable_id', __('Rewardable id'));
        $show->field('rewardable_type', __('Rewardable type'));
        $show->field('extra', __('Extra'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('sub_type', __('Sub type'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new UserHistoryReward());

        $form->number('user_id', __('User id'));
        $form->text('receive_type', __('Receive type'));
        $form->number('rewardable_id', __('Rewardable id'));
        $form->text('rewardable_type', __('Rewardable type'));
        $form->text('extra', __('Extra'));
        $form->text('sub_type', __('Sub type'));

        return $form;
    }
}
