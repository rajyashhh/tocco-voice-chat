<?php

namespace Modules\RankingReward\Http\Controllers;

use App\Admin\Controllers\MainController;
use App\Models\Gift;
use App\Models\Ware;
use App\Selectables\Badges;
use App\Selectables\WaresByType;
use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\Badge\Entities\Badge;
use Modules\RankingReward\Entities\RankingReward;
use Modules\Vip\Entities\OVip;

class RankingRewardController extends MainController
{
//    public $permission_name = 'ranking-rewards';
//
//    public function index(Content $content)
//    {
//        return parent::index($content
//            ->title(__('Ranking Rewards'))
//            ->body($this->grid()));
//    }
//
//    public function show($id, Content $content)
//    {
//        return parent::show($id, $content
//            ->title(__('Ranking Reward'))
//            ->body($this->detail($id)));
//    }
//
//    public function edit($id, Content $content)
//    {
//        $id = request()->route('id');
//        return parent::edit($id, $content
//            ->title(__('Ranking Reward'))
//            ->body($this->form()->edit($id)));
//    }
//
//    public function create(Content $content)
//    {
//        return parent::create($content
//            ->title(__('Ranking Reward'))
//            ->body($this->form()));
//    }
//
//    protected function grid()
//    {
//        $grid = new Grid(new RankingReward());
//
//        $rankingRangeId = request('ranking_range_id');
//        $grid->model()->where('ranking_range_id', $rankingRangeId);
//
//        $grid->column('id', __('ID'))->sortable();
//        $grid->column('target_type', __('Type'));
//        $grid->column('gift_id', __('gifts'))->display(function () {
//            if ($this->target_type == "ware") {
//                return @$this->ware->name;
//            } elseif ($this->target_type == "vip") {
//                return @$this->vip->name;
//            } elseif ($this->target_type == "achievement") {
//                $value = getDriverUrl() . '/' . @$this->target;
//                return "<img src='$value' width='80' height='80'>";
//            }
//        });
//        $grid->column('image', __('image'))->display(function ($path) {
//            if ($this->target_type == 'ware') {
//                $ware = Ware::find($this->target);
//                $path = $ware->img2 ?? $ware?->show_img;
//            } elseif ($this->target_type == 'vip') {
//                $vips = OVip::find($this->target);
//                $path = $vips?->img;
//            } elseif ($this->target_type == 'achievement') {
//                $path = $this?->target;
//            } else {
//                $path = 'coin.png';
//            }
//            /** @var Gift $this */
//            $url = getImagePath($path);
//            return handleShowImageWithTypes($this->id, $url, 50, 50);
//        });
//        $grid->column('expire_days', __('expire'));
//        $grid->column('created_at', __('Created At'))->display(function ($value) {
//            return Carbon::parse($value)->format('Y-m-d');
//        });
//        if (method_exists($this, 'extendGrid')) {
//            $this->extendGrid($grid);
//        }
//
//        return $grid;
//    }
//
//    protected function detail($id)
//    {
//        $show = new Show(RankingReward::findOrFail($id));
//
//        $show->field('id', __('ID'));
//        $show->field('target_type', __('Target Type'));
//        $show->field('target', __('target'));
//        $show->field('expire_days', __('expire'));
//        $show->column('created_at', __('Created At'))->display(function ($value) {
//            return Carbon::parse($value)->format('Y-m-d');
//        });
//        $show->column('updated_at', __('Updated At'))->display(function ($value) {
//            return Carbon::parse($value)->format('Y-m-d');
//        });
//
//        return $show;
//    }
//
//    protected function form()
//    {
//        $form = new Form(new RankingReward());
//        $this->disableFormTools($form);
//
//        $rankingRangeId = request('ranking_range_id');
//        $form->hidden('ranking_range_id')->value($rankingRangeId);
//
//        $existingRewards = RankingReward::where('ranking_range_id', $rankingRangeId)->get();
//        if ($existingRewards->count() > 0) {
//            $html = '
//                <div class="box box-success">
//                    <div class="box-header with-border">
//                        <h3 class="box-title"><i class="fa fa-gift"></i> ' . __('Added Rewards') . '</h3>
//                    </div>
//                    <div class="box-body">
//                    <div class="row" id="added-rewards-list">';
//
//            foreach ($existingRewards as $reward) {
//                $name = $reward->target;
//                $url = '';
//
//                if ($reward->target_type == 'ware') {
//                    $ware = Ware::find($reward->target);
//                    $name = $ware->name ?? $reward->target;
//                    $url = getImagePath($ware->img2 ?? $ware->show_img ?? '');
//                } elseif ($reward->target_type == 'badge') {
//                    $badge = Badge::find($reward->target);
//                    $name = $badge->name ?? $reward->target;
//                    $url = getImagePath($badge->img ?? '');
//                } elseif ($reward->target_type == 'vip') {
//                    $vip = OVip::find($reward->target);
//                    $name = $vip->name ?? $reward->target;
//                    $url = getImagePath($vip->img ?? '');
//                } elseif ($reward->target_type == 'coins') {
//                    $name = $reward->target . ' coins';
//                    $url = getImagePath('coin.png');
//                } elseif ($reward->target_type == 'achievement') {
//                    $name = 'Achievement';
//                    $url = getImagePath($reward->target);
//                }
//                $showImage = handleShowImageWithTypes($reward->id, $url, -1, 60, 4, 'cover');
//
//                $html .= '
//                    <div class="col-md-3 col-sm-4 col-xs-6" id="reward-item-' . $reward->id . '">
//                        <div class="card" style="border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin-bottom: 15px; text-align: center; position: relative;">
//                            <button type="button" class="btn btn-danger btn-xs delete-reward" data-id="' . $reward->id . '"
//                                style="position: absolute; top: 5px; right: 5px; border-radius: 50%; width: 24px; height: 24px; padding: 0; z-index: 10;">
//                                <i class="fa fa-times"></i>
//                            </button>
//
//                            <div style="
//                                width: 100%;
//                                height: 80px;
//                                display: flex;
//                                align-items: center;
//                                justify-content: center;
//                                margin-bottom: 8px;
//                            ">
//                                ' . $showImage . '
//                            </div>
//
//                            <div style="font-weight: bold; font-size: 12px; color: #333;">' . e($name) . '</div>
//                            <span class="label label-info" style="font-size: 10px;">' . $reward->target_type . '</span>
//                            ' . ($reward->expire_days ? '<div style="font-size: 10px; color: #888; margin-top: 5px;">' . $reward->expire_days . ' ' . __('days') . '</div>' : '') . '
//                        </div>
//                    </div>';
//            }
//
//            $html .= '
//            </div>
//        </div>
//    </div>';
//
//            $form->html($html);
//        }
//        $form->select('target_type', trans('type'))->options([
//            "ware" => __('ware'),
//            "badge" => __('badge'),
//            "vip" => __('vip'),
//            "coins" => __('coins'),
//            "achievement" => __('achievement')
//        ])
//            ->when("ware", function () use ($form) {
//                $this->addWareField($form);
//                $form->number('expire_days', __('expire'))->default(1);
//            })
//            ->when("badge", function () use ($form) {
//                $this->addBadgeField($form);
//                $form->number('expire_days', __('expire'))->default(1);
//            })
//            ->when("vip", function () use ($form) {
//                $form->select('target2', trans('vips'))->options(function () {
//                    $vips = OVip::query()->select('id', 'name')->get();
//                    foreach ($vips as $vip) {
//                        $ops[$vip->id] = $vip->name;
//                    }
//                    return $ops ?? [];
//                });
//                $form->number('expire_days', __('expire'))->default(1);
//            })
//            ->when("coins", function () use ($form) {
//                $form->number("target3", __("coins"));
//            })
//            ->when("achievement", function () use ($form) {
//                $form->image("target4", __('image'))->name(function ($file) {
//                    return now()->timestamp . '.' . $file->guessExtension();
//                })->disk('gcs');
//                $form->number('expire_days', __('expire'))->default(1);
//            })
//            ->rules('required');
//
//        $form->disableReset();
//        $form->tools(function (Form\Tools $tools) {
//            $tools->disableList();
//            $tools->disableDelete();
//            $tools->disableView();
//        });
//
//        $form->html('
//            <div class="form-group">
//                <button type="submit" name="action" value="add_more" class="btn btn-success">
//                    <i class="fa fa-plus"></i> ' . __('Add & Continue') . '
//                </button>
//                <button type="submit" name="action" value="done" class="btn btn-primary">
//                    <i class="fa fa-check"></i> ' . __('Done') . '
//                </button>
//            </div>
//        ');
//
//        $form->html('
//            <script>
//            $(document).on("click", ".delete-reward", function() {
//                var id = $(this).data("id");
//                var item = $("#reward-item-" + id);
//                var rankingRangeId = "' . $rankingRangeId . '";
//
//                item.css("opacity", "0.5");
//
//                $.ajax({
//                    url: "' . admin_url('ranking-rewards') . '/" + rankingRangeId + "/" + id,
//                    type: "POST",
//                    data: {
//                        _token: LA.token,
//                        _method: "DELETE"
//                    },
//                    success: function(response) {
//                        item.fadeOut(300, function() {
//                            $(this).remove();
//                        });
//                        toastr.success("Deleted!");
//                    }
//                });
//            });
//            </script>
//        ');
//        return $form;
//    }
//
//    public function store()
//    {
//        request()->validate([
//            'ranking_range_id' => 'required',
//            'target_type' => 'required',
//        ]);
//
//        $rankingRangeId = request('ranking_range_id');
//        $targetType = request('target_type');
//
//        $target = request('target1') ?? request('target2') ?? request('target3') ?? request('target5');
//
//        if ($targetType === 'achievement' && request()->hasFile('target4')) {
//            $file = request()->file('target4');
//            $target = $file->store('achievements', 'gcs');
//        }
//
//        $reward = new RankingReward();
//        $reward->ranking_range_id = $rankingRangeId;
//        $reward->target_type = $targetType;
//        $reward->target = $target;
//        $reward->expire_days = request('expire_days');
//        $reward->save();
//
//        if (request('ajax')) {
//            $name = $target;
//            $img = '';
//
//            if ($targetType == 'ware') {
//                $ware = Ware::find($target);
//                $name = $ware->name ?? $target;
//                $img = getImagePath($ware->img2 ?? $ware->show_img ?? '');
//            } elseif ($targetType == 'badge') {
//                $badge = Badge::find($target);
//                $name = $badge->name ?? $target;
//                $img = getImagePath($badge->img ?? '');
//            } elseif ($targetType == 'vip') {
//                $vip = OVip::find($target);
//                $name = $vip->name ?? $target;
//                $img = getImagePath($vip->img ?? '');
//            } elseif ($targetType == 'coins') {
//                $name = $target . ' ' . __('coins');
//                $img = getImagePath('coin.png');
//            } elseif ($targetType == 'achievement') {
//                $name = __('Achievement');
//                $img = getImagePath($target);
//            }
//
//            $html = '
//                <div class="col-md-3 col-sm-4 col-xs-6" id="reward-item-' . $reward->id . '">
//                    <div class="card" style="border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin-bottom: 15px; text-align: center; position: relative;">
//                        <button type="button" class="btn btn-danger btn-xs delete-reward" data-id="' . $reward->id . '"
//                            style="position: absolute; top: 5px; right: 5px; border-radius: 50%; width: 24px; height: 24px; padding: 0;">
//                            <i class="fa fa-times"></i>
//                        </button>
//                        <img src="' . $img . '" style="width: 60px; height: 60px; object-fit: contain; margin-bottom: 8px;">
//                        <div style="font-weight: bold; font-size: 12px; color: #333;">' . e($name) . '</div>
//                        <span class="label label-info" style="font-size: 10px;">' . $targetType . '</span>
//                        ' . ($reward->expire_days ? '<div style="font-size: 10px; color: #888; margin-top: 5px;">' . $reward->expire_days . ' ' . __('days') . '</div>' : '') . '
//                    </div>
//                </div>';
//
//            return response()->json([
//                'status' => true,
//                'html' => $html
//            ]);
//        }
//
//        admin_toastr(__('Reward added!'));
//
//        if (request('action') === 'done') {
//            return redirect()->to(admin_url('ranking-rewards/' . $rankingRangeId));
//        }
//
//        return redirect()->to(admin_url('ranking-rewards/' . $rankingRangeId . '/create?ranking_range_id=' . $rankingRangeId));
//    }
//
//    public function update($id)
//    {
//        $id = request()->route('id');
//        $form = $this->form()->edit($id);
//
//        $form->saved(function (Form $form) {
//            $rankingRangeId = $form->model()->ranking_range_id;
//            admin_toastr(__('Updated successfully'));
//            return redirect()->to('admin/ranking_range_id/' . $rankingRangeId);
//        });
//
//        return $form->update($id);
//    }

    public function destroy($id)
    {
        $id = request()->route('id');
        $reward = RankingReward::findOrFail($id);
        $ranking_range_id = $reward->ranking_range_id;
        $reward->delete();

        admin_toastr(__('Deleted successfully'));

        return [
            'status' => true,
            'message' => __('Deleted successfully'),
            'redirect' => admin_url('ranking-rewards/' . $ranking_range_id),
        ];
    }

//    protected function addWareField(Form $form)
//    {
//        $prefix = 'wares';
//        $form->belongsTo('target1', WaresByType::class, __('Ware'), function ($form) use ($prefix) {
//            $form->setElementName($prefix . 'target1')
//                ->select('id', __('wares'))
//                ->options(function ($id) {
//                    if (!$id) return [];
//                    $ware = Ware::find($id);
//                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
//                })
//                ->attribute([
//                    'data-image-select' => 1,
//                    'data-load-url' => admin_url('wares-by-id')
//                ]);
//
//            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');
//
//            $this->addWareJs();
//        });
//    }
//
//    protected function addBadgeField(Form $form)
//    {
//        $prefix = 'badges';
//        $form->belongsTo('target5', Badges::class, __('Badges'), function ($form) use ($prefix) {
//            $form->setElementName($prefix . 'target5')
//                ->select('id', __('badges'))
//                ->options(function ($id) {
//                    if (!$id) return [];
//                    $ware = Badge::find($id);
//                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
//                })
//                ->attribute([
//                    'data-image-select' => 1,
//                    'data-load-url' => admin_url('wares-by-id')
//                ]);
//
//            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');
//
//            $this->addWareJs();
//        });
//    }
}
