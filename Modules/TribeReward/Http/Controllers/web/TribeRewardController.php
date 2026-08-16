<?php

namespace Modules\TribeReward\Http\Controllers\web;

use Carbon\Carbon;
use App\Models\Gift;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Selectables\Wares;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Layout\Content;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Modules\TribeReward\Entities\TribeReward;

class TribeRewardController extends MainController
{
    public $permission_name = 'tribe-rewards';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Tribe Rewards'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__('Tribe Reward'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        return parent::edit($id, $content
            ->title(__('Tribe Reward'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('Tribe Reward'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new TribeReward());

        $tribe_top_id = request('tribe_top_id');
        $grid->model()->with(['ware', 'vip', 'badge', 'customAchievement','customAchievement.images'])->where('tribe_top_id', $tribe_top_id);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('type', __('Type'));
        $grid->column('gift_id', __('gifts'))->display(function () {
            if ($this->target_type == "ware") {
                return @$this->ware->name;
            } elseif ($this->target_type == "vip") {
                return @$this->vip->name;
            } elseif ($this->target_type == "achievement") {
                return $this->customAchievement?->name ?? '';
            }
        });
        $grid->column('image', __('image'))->display(function ($path) {
            if ($this->target_type == 'ware') {
                $ware = $this->ware;
                $path = $ware->img2 ?? $ware?->show_img;
            } elseif ($this->target_type == 'vip') {
                $vips = $this->vip;
                $path = $vips?->img;
            } elseif ($this->target_type == 'achievement') {
                $path = $this->customAchievement ? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '' : '';
            } else {
                $path = 'coin.png';
            }
            /** @var Gift $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $grid->column('quantity', __('Quantity'));
        $grid->column('expire_days', __('expire'));
        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        if (method_exists($this, 'extendGrid')) {
            $this->extendGrid($grid);
        }

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(TribeReward::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('type', __('Type'));
        $show->field('target_type', __('Target Type'));
        $show->field('target', __('target'));
        $show->field('quantity', __('Quantity'));
        $show->field('expire_days', __('expire'));
        $show->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        $show->column('updated_at', __('Updated At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new TribeReward());

        $tribe_top_id = request('tribe_top_id');
        $form->hidden('tribe_top_id')->default($tribe_top_id);

        $form->select('type', __('Type'))->options([
            //            'agency_reward' => __('Agency Reward'),
            'share_rewards' => __('Share Rewards'),
        ])->default('share_rewards')->required()
            ->when('agency_reward', function (Form $form) {
                $form->belongsTo('target1', Wares::class, trans('wares'))->rules('required');
            })
            ->when('share_rewards', function (Form $form) {
                $form->select('target_type', trans('Target Type'))->options([
                    "ware" => __('ware'),
                    "vip" => __('vip'),
                    "achievement" => __('achievement')
                ])
                    ->when("ware", function (Form $form) {
                        $form->belongsTo('target1', Wares::class, trans('wares'))->rules('required');
                    })
                    ->when("vip", function (Form $form) {
                        $form->select('target2', trans('vips'))->options(function () {
                            $ops = [];
                            $vips = OVip::query()->select('id', 'name')->get();
                            foreach ($vips as  $vip) {
                                $ops[$vip->id] = $vip->name;
                            }
                            return $ops;
                        })->rules('required');
                    })
                    ->when("achievement", function (Form $form) {
                        $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));
                    });
            });

        $form->number('quantity', __('Quantity'))->required();
        $form->number('expire_days', __('expire'))->required();

        return $form;
    }

    public function store()
    {
        $form = $this->form();

        $form->saved(function (Form $form) {
            $tribe_top_id = $form->model()->tribe_top_id;
            admin_toastr(__('Created successfully'));
            return redirect()->to('admin/tribe_rewards/' . $tribe_top_id);
        });

        return $form->store();
    }

    public function update($id)
    {
        $id = request()->route('id');
        $form = $this->form()->edit($id);

        $form->saved(function (Form $form) {
            $tribe_top_id = $form->model()->tribe_top_id;
            admin_toastr(__('Updated successfully'));
            return redirect()->to('admin/tribe_rewards/' . $tribe_top_id);
        });

        return $form->update($id);
    }

    public function destroy($id)
    {
        $reward = TribeReward::findOrFail($id);
        $tribe_top_id = $reward->tribe_top_id;
        $reward->delete();

        admin_toastr(__('Deleted successfully'));

        return [
            'status' => true,
            'message' => __('Deleted successfully'),
            'redirect' => admin_url('tribe_rewards?tribe_top_id=' . $tribe_top_id),
        ];
    }
}
