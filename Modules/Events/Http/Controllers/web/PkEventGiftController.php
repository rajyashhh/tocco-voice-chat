<?php

namespace Modules\Events\Http\Controllers\web;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Selectables\Badges;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Facades\Admin;
use App\Selectables\WaresByType;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Services\AppFeatureService;
use Modules\Events\Entities\PkReward;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class PkEventGiftController extends MainController
{

    use HasResourceActions;
    public $permission_name = 'pk-event-rewards';
    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("pk_event");
    }

    public function index(Content $content)
    {
        $url = url('/admin/pk-events'); // Define your button URL
        $back = __(' back');
        $buttonHTML = <<<HTML
    <a href="{$url}" class="btn btn-sm btn-success" style="margin-bottom: 20px;">
        <i class="fa fa-arrow-left"></i> {$back}
    </a>
    HTML;

        return parent::index($content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->breadcrumb(
                ['text' => trans('admin.eventGift')]
            )
            ->row($buttonHTML) // Add the button row
            ->row($this->grid1()) // First grid
            ->row($this->grid2()) // Second grid
            ->row($this->grid3())); // Third grid
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


    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'PkEvent';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid1()
    {
        $pkType = request('pk_type');
        $pkEventId = request('pk_event_id');
        $grid = new Grid(new PkReward());
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement', 'customAchievement.images'])->orderBy('level');
        $grid->column('created_at')->hide();
        $grid->model()->where("pk_event_id", $pkEventId)->where("pk_type", $pkType)->where("level", 1);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
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
                    $path = $this->customAchievement ? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '' : '';
                } else {
                    $path = 'coin.png';
                }

                /** @var Gift $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }
        $grid->column('expire', __('expire'));
        $grid->column('created_at', __('Created at'));

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) {
            $url = request()->route('pk_event_id') . "/1/create";

            $add = __('add');
            $gifts = __('winner first gifts');
            $customButtonHTML = <<<HTML

            <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                    <i class="fa fa-plus"></i> {$add}
                </a>
                <h3 style="margin-right: 10px;">$gifts</h3>

            HTML;
            $tools->append($customButtonHTML);
        });
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        return $grid;
    }
    protected function grid2()
    {
        $pkType = request('pk_type');
        $pkEventId = request('pk_event_id');
        $grid = new Grid(new PkReward());
        $grid->column('created_at')->hide();
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement','customAchievement.images'])->where("pk_event_id", $pkEventId)->where("pk_type", $pkType)->where("level", 2);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
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
                    $path = $this->customAchievement ? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '' : '';
                } else {
                    $path = 'coin.png';
                }

                /** @var Gift $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }
        $grid->column('expire', __('expire'));
        $grid->column('created_at', __('Created at'));

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) {
            $url = request()->route('pk_event_id') . "/2/create";
            $add = __('add');
            $gifts = __('winner second gifts');
            $customButtonHTML = <<<HTML
            <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                <i class="fa fa-plus"></i>{$add}
            </a>
            <h3 style="margin-right: 10px;">$gifts</h3>
            HTML;
            $tools->append($customButtonHTML);
        });
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        return $grid;
    }

    protected function grid3()
    {
        $pkType = request('pk_type');
        $pkEventId = request('pk_event_id');
        $grid = new Grid(new PkReward());
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement','customAchievement.images'])->orderBy('level');
        $grid->column('created_at')->hide();
        $grid->model()->where("pk_event_id", $pkEventId)->where("pk_type", $pkType)->where("level", 3);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
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
                    $ware =$this->ware;
                    $path = $ware->img2 ?? ($ware->show_img ?? "");
                } elseif ($this->type == 'vip') {
                    $vips = $this->vip;
                    $path = $vips->img ?? '';
                } elseif ($this->type == 'badge') {
                    // $vips = Badge::find($this->target);
                    $path = @$this->badge?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                } elseif ($this->type == 'achievement') {
                    //$path = $this->target;
                    $path = $this->customAchievement ? $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '' : '';
                } else {
                    $path = 'coin.png';
                }

                /** @var Gift $this */
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }
        $grid->column('expire', __('expire'));
        $grid->column('created_at', __('Created at'));

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) {
            $url = request()->route('pk_event_id') . "/3/create";
            $add = __('add');
            $gifts = __('winner third gifts');
            $customButtonHTML = <<<HTML
            <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                <i class="fa fa-plus"></i>{$add}
            </a>
            <h3 style="margin-right: 10px;"> $gifts</h3>
            HTML;
            $tools->append($customButtonHTML);
        });
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
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
        //        $show = new Show(PkEvent::findOrFail($id));

        //        $show->field('id', __('Id'));
        //        $show->field('admin_id', __('Admin id'));
        //        $show->field('start_date', __('Start date'));
        //        $show->field('end_date', __('End date'));
        //        $show->field('editor_id', __('Editor id'));
        //        $show->field('description_en', __('Description en'));
        //        $show->field('description_ar', __('Description ar'));
        //        $show->field('deleted_at', __('Deleted at'));
        //        $show->field('created_at', __('Created at'));
        //        $show->field('updated_at', __('Updated at'));

        //        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PkReward());
        $this->disableFormTools($form);

        $form->hidden('pk_event_id')->value(request('pk_event_id'));
        $form->hidden('pk_type')->value(request('pk_type'));

        $form->hidden('level')->value(request()->route('level'));
        $form->html('<div class="full-column-width">');

        $form->select('type', trans('type'))->options(["ware" => __('ware'), "badge" => __('badge'), "vip" => __('vip'), "coins" => __('coins'), "achievement" => __('achievement')])
            ->when("ware", function () use ($form) {
                $this->addWareField($form);
            })
            ->when("badge", function () use ($form) {
                $this->addBadgeField($form);
            })
            ->when("vip", function () use ($form) {
                $form->select('target2', trans('vips'))->options(function () {
                    $vips = OVip::query()->select('id', 'name')->get();
                    foreach ($vips as  $vip) {
                        $ops[$vip->id] = $vip->name;
                    }
                    return $ops;
                });
            })
            ->when("coins", function () use ($form) {
                $form->number("target3", __("coins"));
            })->when("achievement", function () use ($form) {
                // $form->image("target4", __('image'))->name(function ($file) {
                //     return now()->timestamp . '.' . $file->guessExtension();
                // })->disk('gcs');
                $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));
            });
        $form->number('expire', __('expire'));

        $form->html('</div>');

        Admin::style('

        .rtl .fields-group .form-group {
            display: block !important;
        }

        .form-horizontal .fields-group > .col-md-12 > .form-group .input-group {
            width: 50% !important;
        }
    ');


        $form->saved(function (Form $form) {

            $route = url('admin/pk-events-gift/' . request('pk_type') . '/' . request('pk_event_id'));
            return redirect($route);
        });
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
}
