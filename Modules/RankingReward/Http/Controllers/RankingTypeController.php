<?php

namespace Modules\RankingReward\Http\Controllers;

use Carbon\Carbon;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Selectables\Badges;
use Encore\Admin\Widgets\Box;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use App\Selectables\WaresByType;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use Illuminate\Support\MessageBag;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Modules\RankingReward\Entities\RankingType;
use Modules\RankingReward\Entities\RankingRange;
use Modules\RankingReward\Entities\RankingReward;
use Modules\Reals\Http\Services\InterventionImage;
use Modules\Achievement\Entities\CustomAchievement;
use Illuminate\Http\Exceptions\HttpResponseException;

class RankingTypeController extends MainController
{
    public $permission_name = 'ranking-types';

    public function index(Content $content)
    {
        $type = request('type', 'wealth');
        $schedule = request('schedule', 'daily');

        return $content
            ->title(__('Ranking Types'))
            ->row(function ($row) use ($type, $schedule) {
                $row->column(12, $this->grid2($type, $schedule));

                $row->column(12, $this->grid($type, $schedule));
            });
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__('Ranking Range'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__('Edit Ranking Range'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('Create Ranking Range'))
            ->body($this->form()));
    }

    protected function grid2($type, $schedule)
    {
        return new Box('', view('admin.grid.users.ranking_tabs', [
            'type' => $type,
            'schedule' => $schedule
        ])->render());
    }

    protected function grid($type, $schedule)
    {
        $grid = new Grid(new RankingRange());

        $grid->model()->whereHas('rankingType', function ($query) use ($type, $schedule) {
            $query->where('type', $type)->where('schedule', $schedule);
        })->with([
            'rewards',
            'rewards.ware',
            'rewards.vip',
            'rewards.badge',
        ]);

        $grid->column('id', 'ID')->sortable();
        $grid->column('min', __('Min Rank'));
        $grid->column('max', __('Max Rank'));

        $grid->column('range', __('Range'))->display(function () {
            if ($this->max === null) {
                return "<span class='label label-info'>{$this->min}</span>";
            }
            return "<span class='label label-info'>{$this->min} - {$this->max}</span>";
        });



        $grid->column('members', __('Rewards'))->display(function () {
            $text = __('View Rewards'); // Translation key
            return "<button class='btn btn-sm btn-primary show-rewards-modal' data-id='{$this->id}'>$text</button>";
        });

        $modalTitle = __('Rewards'); // PHP variable with translation

        Admin::script("
    $(document).on('click', '.show-rewards-modal', function() {
        var id = $(this).data('id');
        var modalTitle = '" . e($modalTitle) . "'; // escape for JS

        // Show modal
        if (!$('#rewardsModal').length) {
            $('body').append(`
                <div class='modal fade' id='rewardsModal' tabindex='-1'>
                    <div class='modal-dialog modal-lg'>
                        <div class='modal-content'>
                            <div class='modal-header'>
                                <button type='button' class='close' data-dismiss='modal'>&times;</button>
                                <h5 class='modal-title'>${modalTitle}</h5>
                            </div>
                            <div class='modal-body'>Loading...</div>
                        </div>
                    </div>
                </div>
            `);
        } else {
            $('#rewardsModal .modal-title').text(modalTitle);
        }

        $('#rewardsModal .modal-body').html('Loading...');
        $('#rewardsModal').modal('show');

        // Load rewards via AJAX
        $.get('/admin/rewards/' + id, function(html) {
            $('#rewardsModal .modal-body').html(html);
        }).fail(function() {
            $('#rewardsModal .modal-body').html('<p class=\"text-danger\">Failed to load rewards.</p>');
        });
    });
");




        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        //        if (Admin::user()->can('browse-ranking-rewards') || Admin::user()->can('*')) {
        //            if (!request()->filled('_export_')) {
        //                $grid->column(__('Procedures'))->display(function () {
        //                    $url = url('admin/ranking-rewards/' . $this->id);
        //                    $text = __('Ranking Rewards');
        //                    return "<a href='{$url}' class='btn btn-sm btn-info'>{$text}</a>";
        //                });
        //            }
        //        }

        $grid->disableCreateButton();

        $grid->tools(function ($tools) use ($type, $schedule) {
            $tools->append(
                "<a href='" . admin_url("ranking-types/create?type={$type}&schedule={$schedule}") . "' class='btn btn-sm btn-success'>
                    <i class='fa fa-plus'></i>&nbsp;&nbsp;" . __('New') . "
                </a>"
            );
        });

        if (method_exists($this, 'extendGrid')) {
            $this->extendGrid($grid);
        }

        Admin::style('
            .rtl audio,
            .rtl canvas,
            .rtl progress,
            .rtl video {
              transform: matrix(0.294118, 0, 0, 0.294118, 60, -60) !important;
            }

            .svga-player.rtlSvga {
                transform: scaleX(1) scale(1) !important;
            }
        ');
        return $grid;
    }




    public function getRewards($id)
    {
        // Load the RankingRange and its rewards
        $model = RankingRange::with(['rewards.ware', 'rewards.vip', 'rewards.badge','rewards.customAchievement','rewards.customAchievement.images'])->findOrFail($id);
        $members = $model->rewards->map(function ($reward) {

            $gift = '';
            $path = '';

            switch ($reward->target_type) {
                case 'ware':
                    $gift = optional($reward->ware)->name;
                    $path = optional($reward->ware)->img2;
                    break;

                case 'vip':
                    $gift = optional($reward->vip)->name;
                    $path = optional($reward->vip)->img;
                    break;

                case 'badge':
                    $gift = optional($reward->badge)->name;
                    $path = optional($reward->badge)->image;
                    break;

                case 'coin':
                    $gift = $reward->target;
                    $path = 'coin.png';
                    break;

                case 'achievement':
                    $gift = optional($reward->customAchievement)->name;
                    $path = optional($reward->customAchievement)->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                    break;
            }

            $defaultImage = asset('images/reward.jpg');
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) $url = $defaultImage;

            $image = handleShowImageWithSvga($reward->id, $url, 50, 50);

            // Build HTML columns
            $giftColumn = $reward->target_type === 'achievement' ? $gift : e($gift);

            return [
                'ID'     => $reward->id,
                'Type'   => $reward->target_type,
                'Gift'   => $giftColumn,
                'Image'  => $image,
                'Expire' => $reward->expire_days,
            ];
        });

        // Return a Laravel-Admin Table (HTML)
        $table = new Table([__('ID'), __('type'), __('gift'), __('image'), __('expire')], $members->toArray());

        // Render HTML for modal
        return $table->render();
    }



    protected function detail($id)
    {
        $show = new Show(RankingRange::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('min', __('Min Rank'));
        $show->field('max', __('Max Rank'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new RankingRange());

        $form->hidden('ranking_type_id');
        $form->hidden('action')->default('submit');

        $form->ignore(['target_type', 'target1', 'target2', 'target3', 'target4', 'target5', 'expire_days', 'action']);

        if (!$form->isEditing()) {
            $type = request('type', 'wealth');
            $schedule = request('schedule', 'daily');

            $rankingType = RankingType::firstOrCreate([
                'type' => $type,
                'schedule' => $schedule
            ]);

            $form->hidden('ranking_type_id')->value($rankingType->id);

            $existingRanges = RankingRange::where('ranking_type_id', $rankingType->id)
                ->orderBy('min')
                ->get()
                ->map(function ($r) {
                    if ($r->max === null) {
                        return "Rank {$r->min}";
                    }
                    return "{$r->min} - {$r->max}";
                })
                ->implode(', ');

            if ($existingRanges) {
                $form->html("<div class='alert alert-info'> " . __('Existing ranges:') . " <strong>{$existingRanges}</strong></div>");
            }
        }

        $form->number('min', __('Min Rank'))->min(1)->required();
        $form->number('max', __('Max Rank'))->min(1)->help(__('Leave empty for single rank'));

        $form->divider(__('Rewards'));

        $rankingRangeId = null;
        if ($form->isEditing()) {
            $rankingRangeId = request()->route('ranking_type');
            $existingRewards = RankingReward::where('ranking_range_id', $rankingRangeId)->get();

            if ($existingRewards->count() > 0) {
                $html = '
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-gift"></i> ' . __('Added Rewards') . '</h3>
                    </div>
                    <div class="box-body">
                    <div class="row" id="added-rewards-list">';

                foreach ($existingRewards as $reward) {
                    $name = $reward->target;
                    $url = '';

                    if ($reward->target_type == 'ware') {
                        $ware = Ware::find($reward->target);
                        $name = $ware->name ?? $reward->target;
                        $url = getImagePath($ware->img2 ?? $ware->show_img ?? '');
                    } elseif ($reward->target_type == 'badge') {
                        $badge = Badge::find($reward->target);
                        $name = $badge->name ?? $reward->target;
                        $url = getImagePath($badge->img ?? '');
                    } elseif ($reward->target_type == 'vip') {
                        $vip = OVip::find($reward->target);
                        $name = $vip->name ?? $reward->target;
                        $url = getImagePath($vip->img ?? '');
                    } elseif ($reward->target_type == 'coins') {
                        $name = $reward->target . ' coins';
                        $url = getImagePath('coin.png');
                    } elseif ($reward->target_type == 'achievement') {
                        $achievement = CustomAchievement::find($reward->target);
                        $name = $achievement->name ?? $reward->target;
                        $url = getImagePath($achievement->images?->firstWhere('language', app()->getLocale())?->image ?? '');
                    }
                    $showImage = handleShowImageWithTypes($reward->id, $url, -1, 60, 4, 'cover');

                    $html .= '
                    <div class="col-md-3 col-sm-4 col-xs-6" id="reward-item-' . $reward->id . '">
                        <div class="card" style="border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin-bottom: 15px; text-align: center; position: relative;">
                            <button type="button" class="btn btn-danger btn-xs delete-reward" data-id="' . $reward->id . '"
                                style="position: absolute; top: 5px; right: 5px; border-radius: 50%; width: 24px; height: 24px; padding: 0; z-index: 10;">
                                <i class="fa fa-times"></i>
                            </button>

                            <div style="
                                width: 100%;
                                height: 80px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin-bottom: 8px;
                            ">
                                ' . $showImage . '
                            </div>

                            <div style="font-weight: bold; font-size: 12px; color: #333;">' . e($name) . '</div>
                            <span class="label label-info" style="font-size: 10px;">' . $reward->target_type . '</span>
                            ' . ($reward->expire_days ? '<div style="font-size: 10px; color: #888; margin-top: 5px;">' . $reward->expire_days . ' ' . __('days') . '</div>' : '') . '
                        </div>
                    </div>';
                }

                $html .= '
                    </div>
                </div>
            </div>';

                $form->html($html);
            }
        }

        $form->select('target_type', trans('type'))->options([
            "ware" => __('ware'),
            "badge" => __('badge'),
            "vip" => __('vip'),
            "coins" => __('coins'),
            "achievement" => __('achievement')
        ])
            ->when("ware", function () use ($form) {
                $this->addWareField($form);
                $form->number('expire_days', __('expire'))->default(1);
            })
            ->when("badge", function () use ($form) {
                $this->addBadgeField($form);
                $form->number('expire_days', __('expire'))->default(1);
            })
            ->when("vip", function () use ($form) {
                $form->select('target2', trans('vips'))->options(function () {
                    $vips = OVip::query()->select('id', 'name')->get();
                    foreach ($vips as $vip) {
                        $ops[$vip->id] = $vip->name;
                    }
                    return $ops ?? [];
                });
                $form->number('expire_days', __('expire'))->default(1);
            })
            ->when("coins", function () use ($form) {
                $form->number("target3", __("coins"));
            })
            ->when("achievement", function () use ($form) {
                $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));

                $form->number('expire_days', __('expire'))->default(1);
            });

        $form->html('
            <script>
            $(document).on("click", ".delete-reward", function() {
                var id = $(this).data("id");
                var item = $("#reward-item-" + id);
                var rankingRangeId = "' . $rankingRangeId . '";

                item.css("opacity", "0.5");

                $.ajax({
                    url: "' . admin_url('ranking-rewards') . '/" + rankingRangeId + "/" + id,
                    type: "POST",
                    data: {
                        _token: LA.token,
                        _method: "DELETE"
                    },
                    success: function(response) {
                        item.fadeOut(300, function() {
                            $(this).remove();
                        });
                        toastr.success("Deleted!");
                    },
                    error: function() {
                        item.css("opacity", "1");
                        toastr.error("Error deleting!");
                    }
                });
            });

            $(document).off("click", "#btn-add-continue").on("click", "#btn-add-continue", function(e) {
                e.preventDefault();
                e.stopPropagation();

                var btn = $(this);

                if (btn.data("submitting")) {
                    return false;
                }
                btn.data("submitting", true);

                var form = btn.closest("form");

                btn.html("<i class=\"fa fa-spinner fa-spin\"></i> ' . __('Loading...') . '");
                btn.prop("disabled", true);

                $("input[name=action]").val("add_continue");

                form.find(".btn-primary[type=submit]").addClass("disabled").attr("disabled", true);

                form.submit();

                return false;
            });
            </script>
        ');

        $form->html('
            <div class="box-footer">
                <button type="button" id="btn-add-continue" class="btn btn-success">
                    <i class="fa fa-plus"></i> ' . __('Add & Continue') . '
                </button>
            </div>
        ');

        $form->saving(function (Form $form) {
            $action = request('action');

            if ($action === 'add_continue') {
                $targetType = request('target_type');

                if (!$targetType) {
                    $error = new MessageBag([
                        'target_type' => [__('Please select reward type')],
                    ]);
                    return back()->withErrors($error)->withInput();
                }

                $target = null;
                $fieldName = 'target_type';

                if ($targetType === 'ware') {
                    $target = request('target1');
                    $fieldName = 'target1';
                } elseif ($targetType === 'badge') {
                    $target = request('target5');
                    $fieldName = 'target5';
                } elseif ($targetType === 'vip') {
                    $target = request('target2');
                    $fieldName = 'target2';
                } elseif ($targetType === 'coins') {
                    $target = request('target3');
                    $fieldName = 'target3';
                } elseif ($targetType === 'achievement') {
                    $target = request('target4');
                    $fieldName = 'target4';
                }

                if (empty($target)) {
                    $error = new MessageBag([
                        $fieldName => [__('Please select or enter reward value')],
                    ]);
                    return back()->withErrors($error)->withInput();
                }
            }

            $rankingTypeId = $form->ranking_type_id;
            $min = (int) $form->min;
            $max = $form->max ? (int) $form->max : null;
            $currentId = $form->model()->id;

            $effectiveMax = $max ?? $min;

            if ($max !== null && $min > $max) {
                $error = new MessageBag([
                    'min' => [__('Min rank must be less than or equal to max rank')],
                ]);
                return back()->withErrors($error)->withInput();
            }

            $existingRanges = RankingRange::where('ranking_type_id', $rankingTypeId)
                ->when($currentId, function ($query) use ($currentId) {
                    $query->where('id', '!=', $currentId);
                })
                ->get();

            foreach ($existingRanges as $range) {
                $existingMin = $range->min;
                $existingMax = $range->max ?? $range->min;

                if ($this->rangesOverlap($min, $effectiveMax, $existingMin, $existingMax)) {
                    $display = $range->max === null ? "Rank {$range->min}" : "{$range->min} - {$range->max}";
                    $error = new MessageBag([
                        'min' => [__('Range overlaps with existing:') . $display],
                    ]);
                    return back()->withErrors($error)->withInput();
                }
            }
        });

        $form->saved(function (Form $form) {
            static $rewardSaved = false;

            if ($rewardSaved) {
                return;
            }

            $rankingRange = $form->model();
            $targetType = request('target_type');
            $action = request('action');

            if ($targetType) {
                $target = request('target1') ?? request('target2') ?? request('target3') ?? request('target5') ?? request('target4');



                if ($target) {
                    $reward = new RankingReward();
                    $reward->ranking_range_id = $rankingRange->id;
                    $reward->target_type = $targetType;
                    $reward->target = $target;
                    $reward->expire_days = request('expire_days');
                    $reward->save();

                    $rewardSaved = true;
                }

                $rewards = RankingReward::where('ranking_range_id', $rankingRange->id)->get();
                $intervalImage = (new InterventionImage());


                $images = [];

                foreach ($rewards as $reward) {

                    $path = null;

                    switch ($reward->target_type) {

                        case 'ware':
                            $path = $reward->ware->img2
                                ? getImagePath($reward->ware->img2)
                                : asset('images/ware-image.jpg');
                            break;

                        case 'vip':
                            $path =
                                // $reward->vip->image2
                                //     ? getImagePath($reward->vip->image2)
                                //     :
                                asset('images/ware-image.jpg');
                            break;

                        case 'badge':
                            $path = $reward->badge->show_image
                                ? getImagePath($reward->badge->show_image)
                                : asset('images/ware-image.jpg');
                            break;

                        case 'coins':
                            $path =
                                // getImagePath('coin.png');
                                asset('images/coin.jpg');
                            break;

                        case 'achievement':
                            $path = getImagePath($reward->images?->firstWhere('language', app()->getLocale())?->image ?? '');
                            break;
                    }

                    if ($path) {
                        $images[] = $path; // <-- Store **path string**, not readImage() output
                    }
                }

                //  dd( $images,$rewards,$target);
                if (!empty($images)) {
                    $intervalImageUrl =  $intervalImage->combineImages($images);
                    if ($intervalImageUrl) {
                        $rankingRange->generate_image = $intervalImageUrl;
                        $rankingRange->save();
                    }
                }


                if ($action === 'add_continue') {
                    admin_toastr(__('Reward added!'));

                    throw new HttpResponseException(
                        redirect(admin_url('ranking-types/' . $rankingRange->id . '/edit'))
                    );
                }
            }
        });

        return $form;
    }

    protected function rangesOverlap($min1, $max1, $min2, $max2): bool
    {
        if ($max1 < $min2) {
            return false;
        }

        if ($min1 > $max2) {
            return false;
        }

        return true;
    }

    protected function addWareField(Form $form)
    {
        $prefix = 'wares';
        $form->belongsTo('target1', WaresByType::class, __('Ware'), function ($form) use ($prefix) {
            $form->setElementName($prefix . 'target1')
                ->select('id', __('wares'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $ware = Ware::find($id);
                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id')
                ]);

            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');

            $this->addWareJs();
        });
    }

    protected function addBadgeField(Form $form)
    {
        $prefix = 'badges';
        $form->belongsTo('target5', Badges::class, __('Badges'), function ($form) use ($prefix) {
            $form->setElementName($prefix . 'target5')
                ->select('id', __('badges'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $ware = Badge::find($id);
                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id')
                ]);

            $form->html('<div id="ware-image-preview" style="margin-top:10px;"></div>');

            $this->addWareJs();
        });
    }
}
