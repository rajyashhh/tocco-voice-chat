<?php

namespace Modules\Events\Http\Controllers\web;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Admin;
use App\Selectables\Wares;
use App\Selectables\Badges;

use Modules\Vip\Entities\OVip;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Services\AppFeatureService;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Encore\Admin\Auth\Permission;
use Modules\Events\Entities\RewardTarget;
use Modules\Events\Entities\ChargeTargetEvent;
use Encore\Admin\Controllers\HasResourceActions;

class RewardTargetController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'gift-target-event';
    public function __construct()
    {
        try {
            (new AppFeatureService)->validateStatusEnable("target_events");
        } catch (\Throwable $e) {
            \Log::warning('AppFeatureService validateStatusEnable failed: ' . $e->getMessage());
            abort(404);
        }
    }
    public function index(Content $content)
    {
        return parent::index($content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->body($this->grid()));
    }
    public function create(Content $content)
    {
        return parent::create($content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form()));
    }

    public function update($id)
    {
        $id = request()->route('id');
        return $this->form()->update($id);
    }

    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        return parent::edit($id, $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id)));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->header(trans('admin.detail'))
            ->description(trans('admin.description'))
            ->body($this->detail($id)));
    }
    protected function grid()
    {

        $charge_event_id = request('charge_event_id');
        $target = ChargeTargetEvent::query()->find($charge_event_id);
        $grid = new Grid(new RewardTarget());
        $grid->column('created_at')->hide();
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement', 'customAchievement.images'])->where("charge_event_id", $charge_event_id);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));

        if (!request()->filled('_export_')) {
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
                    $path = $this->customAchievement ? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '' : '';
                } else {
                    $path = 'coin.png';
                }

                /** @var Gift $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }
        $grid->column('expire', __('expire'))->display(function ($expire) {
            if ($this->type == 'coins') {
                return  '-';
            }
            return $expire;
        });
        $grid->column('created_at', __('Created at'));

        $grid->tools(function (Grid\Tools $tools) use ($target) {
            $url = url('admin/target-events');
            $back = __(' back');
            $gifts = __('gifts');
            $customButtonHTML = <<<HTML
                     <div style="display: contents; align-items: center;">
                        <a href="{$url}" class="btn btn-sm btn-info" style="margin-right: 10px;">
                            <i class="fa fa-arrow-left"></i> {$back}
                        </a>
                        <label style="margin: 0;" { $gifts} : {$target?->value} </label>
                    </div>
                HTML;
            $tools->append($customButtonHTML);
        });

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new RewardTarget());
        $this->disableFormTools($form);

        $form->hidden('charge_event_id')->value(request('charge_event_id'));
        $form->html('<div class="full-column-width">');

        $form->select('type', trans('type'))->options(["ware" => __('ware'), "badge" => __('badge'), "vip" => __('vip'), "coins" => __('coins'), "achievement" => __('achievement')])
            ->when("ware", function () use ($form) {
                $form->belongsTo('target1', Wares::class, trans('wares'))->rules('required');
            })
            ->when("vip", function () use ($form) {
                $form->select('target2', trans('vips'))->options(function () {
                    $ops = [];
                    $vips = OVip::query()->select('id', 'name')->get();
                    foreach ($vips as $vip) {
                        $ops[$vip->id] = $vip->name;
                    }
                    return $ops;
                })->rules('required');
            })->when("badge", function () use ($form) {
                $this->addBadgeField($form);
            })
            ->when("coins", function () use ($form) {
                $form->number("target3", __("coins"))->rules('required');
            })->when("achievement", function () use ($form) {
                $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));
            });
        $form->number('expire', __('expire'))->default(1);
        $form->html('</div>');

        Admin::style('

        .rtl .fields-group .form-group {
            display: block !important;
        }

        .form-horizontal .fields-group > .col-md-12 > .form-group .input-group {
            width: 50% !important;
        }
    ');
        return $form;
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



    public function destroyBulk($id, $targets)
    {
        Permission::check('delete-' . $this->permission_name);

        $targetIds = explode(',', $targets);

        RewardTarget::where('charge_event_id', $id)
            ->whereIn('id', $targetIds)
            ->delete();

        return response()->json([
            'status'  => true,
            'message' => __('deleted_success'),
        ]);
    }
}
