<?php

namespace Modules\HostLevel\Http\Controllers\web;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Selectables\OVips;
use App\Selectables\Wares;
use App\Selectables\Badges;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Modules\HostLevel\Entities\HostLevelReward;

class HostLevelRewardController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Host level reward';
    public $permission_name = 'host-level-reward';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans($this->title))
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
            ->title(trans($this->title))
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
            ->title(trans($this->title))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans($this->title))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $host_level_id = request('host_level_id');
        $grid = new Grid(new HostLevelReward());
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement', 'customAchievement.images'])->where("host_level_id", $host_level_id);
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

        $grid->tools(function (Grid\Tools $tools) {
            $url = url('admin/host-levels');
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

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        $this->extendGrid($grid);

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
        $show = new Show(HostLevelReward::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('host_level_id', __('host_level_id'));
        $show->field('type', __('Type'));
        $show->field('target', __('Target'));
        $show->field('expire', __('Expire'));
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
        $form = new Form(new HostLevelReward());

        $form->hidden('host_level_id')->value(request('host_level_id'));

        $form->select('type', __('Type'))->options([
            "coins"        => __('Coins'),
            "ware"         => __('Wares'),
            "vip"          => __('vip'),
            "achievement"  => __('Achievement'),
            "badge"        => __('Badge'),
        ])
            ->when("ware", function (Form $form) {
                $form->belongsTo('target1', Wares::class, trans('Wares'))->rules('required');
                $form->number('expire', __('Expire'))->default(1)->help(__('admin.lifetime_help'));
            })
            ->when("vip", function (Form $form) {
                $form->belongsTo('target2', OVips::class, trans('vip'));
                $form->number('expire', __('Expire'))->default(1)->help(__('admin.lifetime_help'));
            })
            ->when("achievement", function (Form $form) {

                $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));


                $form->number('expire', __('Expire'))->default(1)->help(__('admin.lifetime_help'));
            })
            ->when("badge", function () use ($form) {
                $this->addBadgeField($form);
                $form->number('expire', __('Expire'))->default(1)->help(__('admin.lifetime_help'));
            })
            ->when("coins", function (Form $form) {
                $form->number("target3", __('Coins'))->rules('required|integer|min:1');
            });
        $this->addSavedRedirect($form);

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

    protected function addSavedRedirect(Form $form)
    {
        $form->saved(function (Form $form) {
            $route = url('admin/host-level-reward/' . request('host_level_id'));
            return redirect($route);
        });
    }
}
