<?php

namespace Modules\Country\Http\Controllers\SuperAdmin;

use App\Models\Ware;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Modules\Country\Actions\Admin\SuperAdminDedicateRewardAction;
use Modules\Country\Entities\SuperAdminReward;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;

class SuperAdminRewardController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public $permission_name = 'reward-center';

    public function index(Content $content)
    {
        session(['last_ware_type' => request()->get('type', 'vip')]);
        return parent::index($content
            ->title(trans('Rewards Center'))
            ->row(function (Row $row) {
                $row->column(12, $this->tabsComponent());
            })
            ->row(function (Row $row) {
                $row->column(12, $this->grid());
            }));
    }





    protected function grid()
    {
        $type = request()->get('type', 'vip');
        $grid = new Grid(new SuperAdminReward());
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement', 'customAchievement.images'])->where('type',  $type);
        $authId = Admin::user()->id;
        $grid->column('id', __('Id'));
        $authId = auth()->user()->type == 'country' ? auth()->user()->id : auth()->user()->parent_id;
        $grid->model()->where('super_admin_id', $authId);
        $grid->column('gift_id', __('gifts'))->display(function () {
            if ($this->type == "ware") {
                return @$this->ware->name ?? '';
            } elseif ($this->type == "vip") {
                return @$this->vip->name ?? '';
            } elseif ($this->type == "badge") {
                return @$this->badge->name ?? '';
            } elseif ($this->type == "coins") {
                return @$this->target;
            } elseif ($this->type == "achievement") {
                return $this->customAchievement?->name ?? '';
               
            }
        });
        if (!request()->filled('_export_')) {
            $grid->column('image', __('image'))->display(function ($path) {
                if ($this->type == 'ware') {
                    $ware = $this->ware;
                    $path = $ware->img2 ?? ($ware->show_img ?? "");
                } elseif ($this->type == 'vip') {
                    $vips = $this->vip;
                    $path = $vips->img ?? '';
                } elseif ($this->type == 'badge') {
                    // $vips = Badge::find($this->target);
                    $path = @$this->badge?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                } elseif ($this->type == 'achievement') {
                    $path = $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                } else {
                    $path = 'coin.png';
                }

                /** @var Gift $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }

        $grid->column('no_reward', __('No reward'))->display(function () {
            return $this->no_reward - $this->gave_reward_no;
        });

        if (Admin::user()->can('dedicate-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $grid->column('return', __('dedicate'))->display(function () {

                return (new SuperAdminDedicateRewardAction($this->id))->render();
            });
        }

        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableCreateButton();
        return $grid;
    }


    private function tabsComponent()
    {
        $content = new Row();

        // Define your type mapping
        $typeMap = SELECTED_USED_WARE;

        $types =  ['vip', 'ware',  'badge',
           'achievement',
            'coin'
        ];
        $currentType = request()->get('type', 'vip');

        $box = new Box(content: view('admin.grid.Form.rewardTabs', [
            'types' => $types,
            'currentType' => $currentType
        ]));

        $content->column(12, $box);

        return $content;
    }
}
