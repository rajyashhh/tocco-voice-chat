<?php

namespace Modules\Events\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Services\AppFeatureService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Modules\Events\Entities\RewardWinnerPk;
use Modules\Events\Entities\UserChargeEvent;
use Modules\Events\Entities\WinnerReward;

class EventReportController extends MainController
{
    public $permission_name = 'event_report';
    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("event_report");
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('reports'))
            ->description(__(request('desc') ?: 'users'))
            ->row(function ($row) {
                $row->column(2, view('admin.grid.common.event-reports'));
                $row->column(10, $this->grid());
            }));
    }

    protected function grid()
    {
        $name = request('name') ?: 'weekly_star';
        if (request("name") == "pk_event") {
            $name = "pk_event";
        }
        if (request("name") == "event_period") {
            $name = "event_period";
        }
        $grid = $name;
        $grid = $this->{$grid}();
        $grid->disableexport();
        $grid->disableActions();
        $grid->disableCreateButton();
        return $grid;
    }

    protected function weekly_star()
    {
        $grid = new Grid(new WinnerReward());
        $grid->model()->with([
            'winner',
            'winner.profile',
            'winner.country',
            'winner.senderLevel',
            'winner.receiverLevel',
            'reward',
            'reward.vip:id,name,img',
            'reward.ware:id,name,img2,show_img',
            'winner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
        ])->where('type', 'weekly_star')->orWhere('type', null);
        $grid->column('id', __('ID'));

        $grid->column('nameWinner', __('winner'))->display(function () {
            return app(UserService::class)->adminUserCard($this->winner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('reward.level', __('level'));
        $grid->column('reward.type', __('type'));
        $grid->column(__('Gifts'))->display(function () {
            if ($this->reward != null) {
                $target = '';
                if ($this->reward->type == 'coins') {
                    $target = $this->reward->target;
                } elseif ($this->reward->type == 'vip') {
                    $target = $this->reward->vip?->name;
                } elseif ($this->reward->type == 'ware') {
                    $target = $this->reward->ware?->name;
                }
                return $target;
            }
        });
        $grid->column('image', __('image'))->display(function ($path) {
            if ($this->reward->type == 'ware') {
                // $ware = Ware::find($this->reward->target);
                $path = $this->reward->ware?->img2 ?? $this->reward->ware?->show_img;
            } elseif ($this->reward->type == 'vip') {
                //   $vips = OVip::find($this->reward->target);
                $path = $this->reward->vip?->img;
            } elseif ($this->reward->type == 'achievement') {
                $path = $this->reward?->target;
            } else {
                $path = 'coin.png';
            }

            /** @var Gift $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        if (Admin::user()->can('return-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $grid->column(__('return'))->display(function () {
                $options = ['user' => __('user')];
                return (new \Modules\Events\Http\Actions\EventReportAction($this->id, 'weekly'))->render();
            });
        }
        return $grid;
    }

    protected function pk_event()
    {
        $grid = new Grid(new RewardWinnerPk());
        $grid->model()->with([
            'winner',
            'winner.profile',
            'winner.country',
            'winner.senderLevel',
            'winner.receiverLevel',
            'reward',
            'reward.vip:id,name,img',
            'reward.ware:id,name,img2,show_img',
            'winner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
        ]);
        $grid->column('id', __('ID'));
        $grid->column('nameWinner', __('winner'))->display(function () {
            return app(UserService::class)->adminUserCard($this->winner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        $grid->column('reward.level', __('level'));
        $grid->column('reward.type', __('type'));
        $grid->column(__('gifts'))->display(function () {
            if ($this->reward != null) {
                $target = '';
                if ($this->reward->type == 'coins') {
                    $target = $this->reward?->target;
                } elseif ($this->reward->type == 'vip') {
                    $target = $this->reward->vip?->name;
                } elseif ($this->reward->type == 'ware') {
                    $target = $this->reward->ware?->name;
                }
                return $target;
            }
        });
        $grid->column('image', __('image'))->display(function ($path) {
            if ($this->reward->type == 'ware') {
                //  $ware = Ware::find($this->reward->target);
                $path = $this->reward->ware?->img2 ?? $this->reward->ware?->show_img;
            } elseif ($this->reward->type == 'vip') {
                //   $vips = OVip::find($this->reward->target);
                $path = $this->reward->vip?->img;
            } elseif ($this->reward->type == 'achievement') {
                $path = $this->reward?->target;
            } else {
                $path = 'coin.png';
            }

            /** @var Gift $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });

        if (Admin::user()->can('return-switch-' . $this->permission_name) || Admin::user()->can('*')) {

            $grid->column(__('return'))->display(function () {
                $options = ['user' => __('user')];
                return (new \Modules\Events\Http\Actions\EventReportAction($this->id, 'pk'))->render();
            });
        }
        return $grid;
    }

    protected function event_period()
    {
        $grid = new Grid(new WinnerReward());
        $grid->model()->with([
            'winner',
            'winner.profile',
            'winner.country',
            'winner.senderLevel',
            'winner.receiverLevel',
            'reward',
            'reward.vip:id,name,img',
            'reward.ware:id,name,img2,show_img',
            'winner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
        ])->where('type', 'event_period');
        $grid->column('id', __('ID'));
        $grid->column('nameWinner', __('winner'))->display(function () {
            return app(UserService::class)->adminUserCard($this->winner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        $grid->column('reward.level', __('level'));
        $grid->column('reward.type', __('type'));
        $grid->column(__('gifts'))->display(function () {
            if ($this->reward != null) {
                $target = '';
                if ($this->reward->type == 'coins') {
                    $target = $this->reward->target;
                } elseif ($this->reward->type == 'vip') {
                    $target = $this->reward->vip->name;
                } elseif ($this->reward->type == 'ware') {
                    $target = $this->reward->ware->name;
                }
                return $target;
            }
        });
        $grid->column('image', __('image'))->display(function ($path) {
            if ($this->reward->type == 'ware') {
                // $ware = Ware::find($this->reward->target);
                $path = $this->reward->ware->img2 ?? $this->reward->ware->show_img;
            } elseif ($this->reward->type == 'vip') {
                //  $vips = OVip::find($this->reward->target);
                $path = $this->reward->vip->img;
            } elseif ($this->reward->type == 'achievement') {
                $path = $this->reward->target;
            } else {
                $path = 'coin.png';
            }

            /** @var Gift $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });

        if (Admin::user()->can('return-switch-' . $this->permission_name) || Admin::user()->can('*')) {

            $grid->column(__('return'))->display(function () {
                $options = ['user' => __('user')];
                return (new \Modules\Events\Http\Actions\EventReportAction($this->id, 'weekly'))->render();
            });
        }
        return $grid;
    }


    protected function charges_reports()
    {
        $grid = new Grid(new UserChargeEvent());

        $grid->model()
            ->whereHas('rewardCharge')
            ->with(['user', 'rewardCharge']);

        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->disableRowSelector();

        $grid->filter(function ($filter) {
            $filter->equal('charge_event_id', __('Target ID'));
        });

        $grid->column('id', __('ID'));

        $grid->column('user.name', __('Name'))->display(function ($name) {
            $uid = @$this->user->uuid;
            $path = @$this?->user->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = ($this->user) ? url("admin/users/{$this->user->id}") : 0;

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


        $grid->column('ChargeEvents.tile', __('title'));
        $grid->column('ChargeEvents.value', __('value'));
        $grid->column('rewards', __('Gifts'))->display(function () {
            if (!$this->rewardCharges || count($this->rewardCharges) == 0) {
                return '-';
            }

            $html = '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
            foreach ($this->rewardCharges as $reward) {
                if ($reward->type == "ware") {
                    $name = @$reward->ware->name;
                    $img = getImagePath($reward->ware?->img2 ?? $reward->ware?->show_img);
                } elseif ($reward->type == "vip") {
                    $name = @$reward->vip?->name;
                    $img = getImagePath($reward->vip?->img);
                } elseif ($reward->type == "coins") {
                    $name = @$reward?->target;
                    $img = asset('coin.png');
                } elseif ($reward->type == "achievement") {
                    $name = @$reward->customAchievement?->name ?? '';
                    $img = getImagePath($reward->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? "");
                } else {
                    $name = "-";
                    $img = asset('coin.png');
                }

                $html .= "
                        <div style='text-align: center; width: 80px;'>
                            <img src='{$img}' width='50' height='50' style='border-radius: 8px;'><br>
                            <small>{$name}</small>
                        </div>
                    ";
            }
            $html .= '</div>';

            return $html;
        });
        $grid->column('created_at', __('Created at'))->display(function ($date) {
            return date('Y-m', strtotime($date)); // فقط السنة والشهر
        });

        return $grid;
    }
}
