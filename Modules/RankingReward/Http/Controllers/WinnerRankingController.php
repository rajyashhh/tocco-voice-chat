<?php

namespace Modules\RankingReward\Http\Controllers;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;
use Modules\Badge\Entities\Badge;
use App\Admin\Services\UserService;
use App\Admin\Controllers\MainController;
use Modules\RankingReward\Entities\WinnerRanking;

class WinnerRankingController extends  MainController

{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Winner Ranking';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans($this->title))
            ->body($this->grid()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */


    protected function grid()
    {
        $grid = new Grid(new WinnerRanking());

        $grid->model()->with([
            'user:id,uuid',
            'user.country',
            'user.senderLevel',
            'user.receiverLevel',
            'user.packs' => fn($q) =>
            $q->where('type', 25)->where('is_used', true)->with('ware:id,value'),
            'reward:id,target,target_type',
            'reward.ware:id,name,img2,show_img',
            'reward.vip:id,name,img',
            'reward.badge:id,name,image',
            'reward.badge.images',
            "reward.customAchievement",
            'reward.customAchievement.images',
        ]);

        /* ================= FILTERS ================= */

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $date = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                    $query->whereDate('created_at', '>=', $date);
                }, __('from_date'))->date();
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $date = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                    $query->whereDate('created_at', '<=', $date);
                }, __('to_date'))->date();
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->whereHas(
                        'user',
                        fn($q) =>
                        $q->where('id', $input)->orWhere('uuid', $input)
                    );
                }, __('User'))->placeholder(__('Search by ID, UUID'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('type', __('type'))->select([
                    'sender'   => __('wealth'),
                    'receiver' => __('charm'),
                    'game'     => __('game'),
                    'charge'   => __('charge'),
                ]);
            });
        });

        /* ================= COLUMNS ================= */

        $grid->column('id', __('Id'));

        $grid->column('winner_id', __('Winner'))->display(function () {
            return $this->user
                ? app(UserService::class)->adminUserCard($this->user)
                : __('No User');
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('type', __('type'))->display(fn($type) => [
            'sender'   => 'wealth',
            'receiver' => 'charm',
        ][$type] ?? $type);

        $grid->column('reward.target_type', __('reward type'));

        $grid->column('gift_id', __('gifts'))->display(function () {
            return match ($this->reward->target_type) {
                'ware'        => $this->reward->ware?->name,
                'vip'         => $this->reward->vip?->name,
                'badge'       => $this->reward->badge?->name,
                'coins'       => $this->reward->target,
                'achievement' =>  $this->reward->customAchievement?->name ?? '',
                default       => '',
            };
        });

        $grid->column('image', __('image'))->display(function () {
            $path = match ($this->reward->target_type) {
                'ware'        => $this->reward->ware->img2 ?? $this->reward->ware->show_img,
                'vip'         => $this->reward->vip->img ?? '',
                'badge'       => $this->reward->badge?->images?->firstWhere('language', app()->getLocale())?->image ?? '',
                'achievement' => $this->reward->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '',
                default       => 'coin.png',
            };

            return handleShowImageWithTypes(
                $this->id,
                getImagePath($path),
                50,
                50
            );
        });

        $grid->column('created_at', __('Created at'));

        /* ================= GRID OPTIONS ================= */

        $grid->disableCreateButton();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->disableExport();

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
        $show = new Show(WinnerRanking::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('winner_id', __('Winner id'));
        $show->field('reward_id', __('Reward id'));
        $show->field('type', __('Type'));
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
        $form = new Form(new WinnerRanking());

        $form->number('winner_id', __('Winner id'));
        $form->number('reward_id', __('Reward id'));
        $form->text('type', __('Type'));

        return $form;
    }
}
