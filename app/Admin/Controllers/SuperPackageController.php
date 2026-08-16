<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Selectables\OVips;
use App\Selectables\Badges;
use App\Models\PackageReward;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Widgets\Table;
use App\Selectables\WaresByType;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Models\SuperPackageReward;
use Illuminate\Support\MessageBag;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Modules\Achievement\Entities\CustomAchievement;
use Illuminate\Http\Exceptions\HttpResponseException;




class SuperPackageController extends MainController
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
    protected $title = 'SuperPackageReward';
    public $permission_name = 'super-package-reward';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Super Package reward'))
            ->body($this->grid()));
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Super Package reward'))
            ->body($this->detail($id)));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('Super Package reward'))
            ->body($this->form($id)->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Super Package reward'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SuperPackageReward());
        $grid->model()->select('id', 'title')->with([
            'packageRewards.ware:id,name,img2',
            'packageRewards.vip:id,name,img',
            'packageRewards.badge:id,name,image',
            'packageRewards:id,super_package_id,type,target,expire,quantity',
        ]);

        $grid->column('id', __('Id'));
        $grid->column('title', __('title'));


        $grid->column('members', __('rewards'))->expand(function ($model) {

            $members = $model->packageRewards->map(function ($reward) {

                $gift = '';
                $path = '';

                switch ($reward->type) {
                    case 'ware':
                        $gift = optional($reward->ware)->name;
                        $path = optional($reward->ware)->img2
                            ?? optional($reward->ware)->img2;
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

                $url =  getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithSvga(
                    $reward->id,
                    $url,
                    50,
                    50
                );

                return [
                    'id'       => $reward->id,
                    'type'     => $reward->type,
                    'gift'     => $gift,
                    'image'    => $image,
                    'quantity' => $reward->expire,
                    'expire'   => $reward->quantity,
                ];
            });

            return new Table(
                ['ID', __('type'), __('gift'), __('image'), __('quantity'), __('expire')],
                $members->toArray()
            );
        });



        Admin::script("
        $('.rtlSvga').each(function() {
            var id = $(this).attr('id');
            var url = $(this).data('url');
            var player = new SVGA.Player('#' + id);
            var parser = new SVGA.Parser();
            parser.load(url, function(videoItem) {
                player.setVideoItem(videoItem);
                player.startAnimation();
            });
        });
    ");


        if (Admin::user()->can('dedicate-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $grid->column('return', __('dedicate'))->display(function () {

                return (new \App\Admin\Actions\DedicateSuperPackageRewardAction($this->id))->render();
            });
        }
        Admin::script("
        if (window.innerWidth >= 1024) {
            $('.table-responsive').removeClass('table-responsive');
            }
        ");

        Admin::script('
            $(document).on("click", ".grid-expand", function(){
                setTimeout(function(){
                    $(".grid-box .table .table").css("direction", "ltr");
                    $(".grid-box .table .table td, .grid-box .table .table th").css("text-align", "center");
                    $(".grid-box .table .table .rtlSvga").css("direction", "ltr");
                }, 300);
            });
            // Also apply immediately for already expanded
            $(".grid-box .table .table").css("direction", "ltr");
            $(".grid-box .table .table td, .grid-box .table .table th").css("text-align", "center");
            $(".grid-box .table .table .rtlSvga").css("direction", "ltr");
        ');
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $this->extendGrid($grid);
        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = null)
    {
        $form = new Form(new SuperPackageReward());
        $form->tools(function (Form\Tools $tools) {
            $url = '/admin/super-package-rewards';
            $button = '<a href="' . $url . '" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("back") . '</a>';
            $tools->append($button);
            if (request()->is('*edit*')) {
                $tools->disableDelete();
            }
        });

        $form->hidden('action')->default('submit');

        $form->ignore(['type', 'target1', 'target2', 'target3', 'target4', 'target5', 'expire', 'quantity', 'action']);


        $form->text('title', __('title'))->required();

        $rankingRangeId = null;
        if ($form->isEditing()) {
            $rankingRangeId = $id;
            //  dd( $rankingRangeId );
            $existingRewards = PackageReward::where('super_package_id', $rankingRangeId)->get();

            if ($existingRewards->count() > 0) {
                $wareIds = $existingRewards->where('type', 'ware')->pluck('target')->unique();
                $badgeIds = $existingRewards->where('type', 'badge')->pluck('target')->unique();
                $vipIds = $existingRewards->where('type', 'vip')->pluck('target')->unique();
                $achievementIds = $existingRewards->where('type', 'achievement')->pluck('target')->unique();

                $waresMap = Ware::whereIn('id', $wareIds)->get()->keyBy('id');
                $badgesMap = Badge::whereIn('id', $badgeIds)->get()->keyBy('id');
                $vipsMap = OVip::whereIn('id', $vipIds)->get()->keyBy('id');
                $achievementsMap = CustomAchievement::with('images')->whereIn('id', $achievementIds)->get()->keyBy('id');

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

                    if ($reward->type == 'ware') {
                        $ware = $waresMap->get($reward->target);
                        $name = $ware->name ?? $reward->target;
                        $url = getImagePath($ware->img2 ?? $ware->show_img ?? '');
                    } elseif ($reward->type == 'badge') {
                        $badge = $badgesMap->get($reward->target);
                        $name = $badge->name ?? $reward->target;
                        $url = getImagePath($badge->img ?? '');
                    } elseif ($reward->type == 'vip') {
                        $vip = $vipsMap->get($reward->target);
                        $name = $vip->name ?? $reward->target;
                        $url = getImagePath($vip->img ?? '');
                    } elseif ($reward->type == 'coin') {
                        $name = $reward->target . 'coin';
                        $url = getImagePath('coin.png');
                    } elseif ($reward->type == 'achievement') {
                        $achievement = $achievementsMap->get($reward->target);
                        $name = $achievement->name ?? 'Achievement';
                        $url = getImagePath($achievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '');
                    }

                    $defaultImage = asset('images/reward.jpg');

                    $url = $url ?? $defaultImage;

                    if (! isImageExists($url)) {
                        $url = $defaultImage;
                    }

                    $showImage = handleShowImageWithTypes($reward->id, $url, -1, 60, 4, 'cover');

                    $html .= '
                    <div class="col-md-3 col-sm-4 col-xs-6" id="reward-item-' . $reward->id . '">
                        <div class="card" style="border: 1px solid #ddd; border-radius: 8px; padding: 10px; margin-bottom: 15px; text-align: center; position: relative;">
                            <button type="button" class="btn btn-danger btn-xs delete-reward" data-id="' . $reward->id . '"
                                style="position: absolute; top: 5px; right: 5px;">
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

        $form->html('<div class="form-divider full-column-width"> <span>' . __('Rewards') . '</span> </div>');
        $form->html('<div class="full-column-width">');
        $form->select('type', trans('type'))->options([
            "ware" => __('ware'),
            "badge" => __('badge'),
            "vip" => __('vip'),
            "coin" => __('coins'),
            "achievement" => __('achievement')
        ])
            ->when("ware", function () use ($form) {
                $this->addWareField($form);
                $form->number('expire', __('expire'))->default(1);
                $form->number('quantity', __('number'))->min(0);
            })
            ->when("badge", function () use ($form) {
                $this->addBadgeField($form);
                $form->number('expire', __('expire'))->default(1);
                $form->number('quantity', __('number'))->min(0);
            })
            ->when("vip", function () use ($form) {
                $form->select('target2', trans('vips'))->options(function () {
                    $vips = OVip::query()->select('id', 'name')->get();
                    foreach ($vips as $vip) {
                        $ops[$vip->id] = $vip->name;
                    }
                    return $ops ?? [];
                });
                $form->number('expire', __('expire'))->default(1);
                $form->number('quantity', __('number'))->min(0);
            })
            ->when("coin", function () use ($form) {
                $form->number("target3", __("coins"));
            })
            ->when("achievement", function () use ($form) {
                $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));

                $form->number('expire', __('expire'))->default(1);
            });
        $form->html('</div>');

        Admin::style('

        .rtl .fields-group .form-group {
            display: block !important;
        }

        .form-horizontal .fields-group > .col-md-12 > .form-group .input-group {
            width: 50% !important;
        }
    ');

        $form->html('
            <script>
            $(document).on("click", ".delete-reward", function() {
                var id = $(this).data("id");
                var item = $("#reward-item-" + id);
                var rankingRangeId = "' . $rankingRangeId . '";

                item.css("opacity", "0.5");

                $.ajax({
                    url: "' . admin_url('super-package-rewards') . '/" + rankingRangeId + "/" + id,
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
            <div class="box-footer" style="text-align: center; width: 100%;">
                <button type="button" id="btn-add-continue" class="btn btn-success">
                    <i class="fa fa-plus"></i> ' . __('Add & Continue') . '
                </button>
            </div>
        ');

        Admin::script('
            var btnWrapper = $("#btn-add-continue").closest(".form-group");
            btnWrapper.css({"width":"100%", "text-align":"center"});
            btnWrapper.find(".col-sm-8, .col-sm-12, .col-md-8, .col-md-12").css({"width":"100%", "max-width":"100%", "text-align":"center"});
            btnWrapper.closest(".col-md-12").css({"width":"100%", "max-width":"100%"});
        ');

        $form->saving(function (Form $form) {
            $action = request('action');

            if ($action === 'add_continue') {
                $targetType = request('type');

                if (!$targetType) {
                    $error = new MessageBag([
                        'type' => [__('Please select reward type')],
                    ]);
                    return back()->withErrors($error)->withInput();
                }

                $target = null;
                $fieldName = 'type';

                if ($targetType === 'ware') {
                    $target = request('target1');
                    $fieldName = 'target1';
                } elseif ($targetType === 'badge') {
                    $target = request('target5');
                    $fieldName = 'target5';
                } elseif ($targetType === 'vip') {
                    $target = request('target2');
                    $fieldName = 'target2';
                } elseif ($targetType === 'coin') {
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


            $currentId = $form->model()->id;
        });

        $form->saved(function (Form $form) {
            static $rewardSaved = false;

            if ($rewardSaved) {
                return;
            }

            $rankingRange = $form->model();
            $targetType = request('type');
            $action = request('action');

            if ($targetType) {
                $target = request('target1') ?? request('target2') ?? request('target3') ?? request('target5') ?? request('target4');

                if ($target) {
                    $reward = new PackageReward();
                    $reward->super_package_id = $rankingRange->id;
                    $reward->type = $targetType;
                    $reward->target = $target;
                    $reward->expire = request('expire') ?? 0;
                    $reward->quantity = request('quantity') ?? 0;
                    $reward->save();

                    $rewardSaved = true;
                }

                if ($action === 'add_continue') {
                    admin_toastr(__('Reward added!'));

                    throw new HttpResponseException(
                        redirect(admin_url('super-package-rewards/' . $rankingRange->id . '/edit'))
                    );
                }
            }
        });

        return $form;
    }

    protected function addWareField(Form $form)
    {
        $prefix = 'wares';
        $form->belongsTo('target1', WaresByType::class, __('ware'), function ($form) use ($prefix) {
            $form->setElementName($prefix . 'target1')
                ->select('id', __('wares'))
                ->options(function ($id) {
                    if (!$id) return [];
                    $ware = Ware::find($id);
                    return $ware ? [$ware->id => "{$ware->name}_{$ware->id}"] : [];
                })
                ->attribute([
                    'data-image-select' => 1,
                    'data-load-url' => admin_url('wares-by-id'),
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
