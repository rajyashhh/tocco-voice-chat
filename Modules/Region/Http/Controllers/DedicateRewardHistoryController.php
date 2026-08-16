<?php

namespace Modules\Region\Http\Controllers;


use Encore\Admin\Grid;
use App\Helpers\Common;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Admin\Services\UserService;
use App\Models\DedicateAdminReward;
use Illuminate\Support\Facades\Auth;
use Encore\Admin\Controllers\AdminController;
use Modules\Region\Entities\SubAreaManager;
use Modules\Country\Entities\SuperAdminReward;

class DedicateRewardHistoryController extends AdminController
{

    public function index(Content $content)
    {

        return $content
            ->header(trans('Dedicated Rewards History'))
            ->row(function ($row) {
                $row->column(12, $this->gridTabs()); // <-- Tab buttons
            })
            ->row(function ($row) {
                $row->column(12, $this->grid());
            });
    }

    protected function gridTabs()
    {
        $scope = request('type', 'user');
        $rewardType = request('reward_type', 'vip');

        $html = '
    <style>
        .tab-buttons {
            margin-bottom: 15px;
        }
        .tab-buttons .tab-button {
            color: black !important;
            margin-right: 10px;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f7f7f7;
        }
        .tab-buttons .tab-button.active {
            background-color: #007bff;
            color: white !important;
            border-color: #007bff;
        }
    </style>
    <div class="tab-buttons">
        <a href="?type=user&reward_type=' . $rewardType . '"  class="tab-button btn-dash ' . ($scope === 'user' ? 'active' : '') . '">' . __('user') . '</a>
        <a href="?type=super_admin&reward_type=' . $rewardType . '"  class="tab-button btn-agency ' . ($scope === 'super_admin' ? 'active' : '') . '">' . __('Country Manager') . '</a>
    </div>';

        return new \Encore\Admin\Widgets\Box(__(), $html);
    }


    protected function grid()
    {
        $authIds = [];
        if (auth()->user()->type == 'region') {
            $subAreaManager = SubAreaManager::where('parent_id', auth()->id())->pluck('id')->toArray();
            $authIds = array_merge([$authId = auth()->id()], $subAreaManager);
        } else {
            $authIds = [auth()->id()];
        }
        $type = request('type');
        $rewardType = request('reward_type') ?? 'vip';

        if ($type == 'user') {
            $grid = new Grid(new DedicateAdminReward());
            $this->DedicateReward($grid, $authIds, $rewardType);
        } elseif ($type == 'super_admin') {
            $grid = new Grid(new SuperAdminReward());
            $this->superRewards($grid, $authIds, $rewardType);
        } else {
            // Optional: handle invalid type
            $grid = new Grid(new DedicateAdminReward());
        }


        $grid->tools(function (Grid\Tools $tools) {
            $url = url('areaManager/rewards');
            $back = __('back');

            $customButtonHTML = <<<HTML
                     <div style="display: contents; align-items: center;">
                        <a href="{$url}" class="btn btn-sm btn-info" style="margin-right: 10px;">
                            <i class="fa fa-arrow-left"></i> {$back}
                        </a>
                    </div>
                HTML;
            $tools->append($customButtonHTML);
        });



        $grid->disableRowSelector();
        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();


        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");

        return $grid;
    }

    protected function DedicateReward($grid, $authIds, $rewardType)
    {
        $grid->model()->with([
            'user',
            'user.senderLevel',
            'user.receiverLevel',
            'user.profile',
            'user.country',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'reward'
        ])->whereHas('reward', function ($q) use ($rewardType) {
            $q->where('type', $rewardType);
        })->whereIn('admin_id', $authIds);
        $grid->column('name', __('user'))
            ->display(function ($name) {

                $user = $this->user;
                if (!$user) {
                    return __('No User');
                }
                return app(UserService::class)->adminUserCard($user);
            });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('gift_id', __('gifts'))->display(function () {
            if ($this->reward->type == "ware") {
                return @$this->reward->ware->name ?? '';
            } elseif ($this->reward->type == "vip") {
                return @$this->reward->vip->name ?? '';
            } elseif ($this->reward->type == "badge") {
                return @$this->reward->badge->name ?? '';
            } elseif ($this->reward->type == "coins") {
                return @$this->reward->target;
            } elseif ($this->reward->type == "achievement") {
                return $this->reward->customAchievement?->name ?? '';
            }
        });

        $grid->column('image', __('image'))->display(function ($path) {
            if ($this->reward->type == 'ware') {
                $ware = $this->reward->ware;
                $path = $ware->img2 ?? ($ware->show_img ?? "");
            } elseif ($this->reward->type == 'vip') {
                $vips = $this->reward->vip;
                $path = $vips->img ?? '';
            } elseif ($this->reward->type == 'badge') {
                $path = @$this->reward->badge?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
            } elseif ($this->reward->type == 'achievement') {
                $path = $this->reward->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
            } else {
                $path = 'coin.png';
            }

            /** @var Gift $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });

        $grid->column('reward.expire', __('expire'));
        $grid->column('created_at', __('Created at'));
    }


    protected function superRewards($grid, $authIds, $rewardType)
    {

        $grid->model()->with([
            'ware',
            'vip',
            'badge',
            'badge.images',
            'customAchievement',
            'customAchievement.images',
            'subAreaManagerDedicate',
            'areaManagerDedicate',
            'superAdmin'
        ])->where('type', $rewardType)->whereIn('created_by', $authIds);

        $grid->column('super_admin_id', __('Country Manager'))->display(function () {

            $info =  $this->superAdmin;

            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($info->avatar) ?? $defaultImage;
            if (!isImageExists($url)) $url = $defaultImage;

            $image = handleShowImageWithTypes($info->id, $url, 40, 40);
            $showUrl = url("areaManager/superadmin-users/{$info->id}");
            return "
                        <a href='{$showUrl}' style='text-decoration: none; color: inherit;'>
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                {$image}
                                <div>
                                    <span style='text-decoration: underline; cursor: pointer;'>{$info->username}</span><br>
                                    <span style='color: #aaa; font-size: smaller;'>UUID: {$info->id}</span>
                                </div>
                            </div>
                        </a>
                    ";


            return "<span class='text-danger'>" . __('لا يوجد مستلم') . "</span>";
        });

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
        $grid->column('expire', __('expire'));
        $grid->column('created_at', __('Created at'));
        if (auth()->user()->type == 'region') {
            $grid->column('charger_id', __('created by'))->display(function () {

                $info =  $this->areaManagerDedicate ?? $this->subAreaManagerDedicate;

                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($info->avatar) ?? $defaultImage;
                if (!isImageExists($url)) $url = $defaultImage;

                $image = handleShowImageWithTypes($info->id, $url, 40, 40);
                $showUrl = url("areaManager/area-manager-users/profile/{$info->id}");
                return "
                        <a href='{$showUrl}' style='text-decoration: none; color: inherit;'>
                            <div style='display: flex; align-items: center; gap: 10px;'>
                                {$image}
                                <div>
                                    <span style='text-decoration: underline; cursor: pointer;'>{$info->username}</span><br>
                                    <span style='color: #aaa; font-size: smaller;'>UUID: {$info->id}</span>
                                </div>
                            </div>
                        </a>
                    ";


                return "<span class='text-danger'>" . __('لا يوجد مستلم') . "</span>";
            });
        }
    }
}
