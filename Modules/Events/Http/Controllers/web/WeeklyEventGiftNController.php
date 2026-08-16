<?php

namespace Modules\Events\Http\Controllers\web;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Admin;
use App\Selectables\Badges;
use Modules\Vip\Entities\OVip;
use App\Selectables\WaresByType;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use Modules\Badge\Entities\Badge;
use App\Services\AppFeatureService;
use Modules\Events\Entities\Reward;
use App\Selectables\CustomAchievements;
use Modules\Events\Entities\WeeklyStar;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class WeeklyEventGiftNController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'weekly_star_rewards';
    public function __construct()
    {
        $weekly_event_id = request('weekly_event_id');
        $data = WeeklyStar::find($weekly_event_id);
        if (@$data->type == "event_period") {
            (new AppFeatureService)->validateStatusEnable("period_event");
        } else {
            (new AppFeatureService)->validateStatusEnable("weekly_star");
        }
    }
    public function index(Content $content)
    {
        if (!\Encore\Admin\Facades\Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        $url = url('/admin/weekly-events-new'); // Define your button URL
        $translation = __(' back');

        $buttonHTML = <<<HTML
        <a href="{$url}" class="btn btn-sm btn-success" style="margin-bottom: 20px;">
            <i class="fa fa-arrow-left"></i> {$translation}
        </a>
        HTML;
        return $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->breadcrumb(
                ['text' => trans('admin.eventGift')]
            )
            ->row($buttonHTML)
            ->row($this->grid1()) // First grid
            ->row($this->grid2()) // Second grid
            ->row($this->grid3()); // Third grid


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

    // public function show($id, Content $content)
    // {
    //     return parent::show($id,$content
    //         ->header(trans('admin.detail'))
    //         ->description(trans('admin.description'))
    //         ->body($this->detail($id)));
    // }

    public function show($id, Content $content)
    {
        $type = $id; // Using the second parameter as type

        $content = $content
            ->header(trans('admin.index'))
            ->description(trans('admin.description'))
            ->breadcrumb(['text' => trans('admin.eventGift')]);

        // Create a row to hold our grid
        $row = new \Encore\Admin\Layout\Row();

        switch ($type) {
            case 1:
                $row->column(12, $this->grid1());
                break;
            case 2:
                $row->column(12, $this->grid2());
                break;
            case 3:
                $row->column(12, $this->grid3());
                break;
            default:
                // Show all grids stacked vertically
                $row->column(12, $this->grid1());
                $row->column(12, $this->grid2());
                $row->column(12, $this->grid3());
        }

        return $content->body($row);
    }

    protected function grid1()
    {
        $type = 1;
        $weekly_event_id = request('weekly_event_id');
        $grid = new Grid(new Reward());
        $grid->column('created_at')->hide();
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement', 'customAchievement.images'])->where("weekly_star_id", $weekly_event_id)->where("level", $type);
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
            $url = request()->route('weekly_event_id') . "/1/create";
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
        $this->extendGrid($grid);
        return $grid;
    }

    protected function grid2()
    {
        $type = 2;
        $weekly_event_id = request('weekly_event_id');
        $grid = new Grid(new Reward());
        $grid->column('created_at')->hide();
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement', 'customAchievement.images'])->where("weekly_star_id", $weekly_event_id)->where("level", $type);
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
            $url = request()->route('weekly_event_id') . "/2/create";
            $add = __('add');
            $gifts = __('winner second gifts');
            $customButtonHTML = <<<HTML
            <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                <i class="fa fa-plus"></i>{$add}
            </a>
            <h3 style="margin-right: 10px;">{$gifts}</h3>
            HTML;
            $tools->append($customButtonHTML);
        });
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        $this->extendGrid($grid);
        return $grid;
    }

    protected function grid3()
    {
        $type = 3;
        $weekly_event_id = request('weekly_event_id');
        $grid = new Grid(new Reward());
        $grid->column('created_at')->hide();
        $grid->model()->with(['ware', 'vip', 'badge', 'badge.images', 'customAchievement', 'customAchievement.images'])->where("weekly_star_id", $weekly_event_id)->where("level", $type);
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
                    $ware = Ware::find($this->target);
                    $path = $ware->img2 ?? ($ware->show_img ?? "");
                } elseif ($this->type == 'vip') {
                    $vips = OVip::find($this->target);
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
            $url = request()->route('weekly_event_id') . "/3/create";
            $add = __('add');
            $gifts = __('winner third gifts');
            $customButtonHTML = <<<HTML
            <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                <i class="fa fa-plus"></i>{$add}
            </a>
            <h3 style="margin-right: 10px;"> {$gifts}</h3>
            HTML;
            $tools->append($customButtonHTML);
        });
        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        $this->extendGrid($grid);
        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Reward());
        $this->disableFormTools($form);

        $form->hidden('weekly_star_id')->value(request('weekly_event_id'));
        $form->hidden('level')->value(request('level'));
        $form->html('<div class="full-column-width">');
        $form->select('type', trans('type'))->options(["ware" => __('ware'), "badge" => __('badge'), "vip" => __('vip'), "coins" => __('coins'), "achievement" => __('achievement')])
            ->when("ware", function () use ($form) {
                $this->addWareField($form);
                $form->number('expire', __('expire'))->default(1);
            })
            ->when("badge", function () use ($form) {
                $this->addBadgeField($form);
                $form->number('expire', __('expire'))->default(1);
            })
            ->when("vip", function () use ($form) {
                $form->select('target2', trans('vips'))->options(function () {
                    $vips = OVip::query()->select('id', 'name')->get();
                    foreach ($vips as  $vip) {
                        $ops[$vip->id] = $vip->name;
                    }
                    return $ops;
                });
                $form->number('expire', __('expire'))->default(1);
            })
            ->when("coins", function () use ($form) {
                $form->number("target3", __("coins"));
            })->when("achievement", function () use ($form) {
                // $form->image("target4", __('image'))->name(function ($file) {
                //     return now()->timestamp . '.' . $file->guessExtension();
                // })->disk('gcs');

                $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));

                $form->number('expire', __('expire'))->default(1);
            })->rules('required');

        $form->html('</div>');

        Admin::style('

        .rtl .fields-group .form-group {
            display: block !important;
        }

        .form-horizontal .fields-group > .col-md-12 > .form-group .input-group {
            width: 50% !important;
        }
    ');
        //     $form->html('
        //     <style>
        //     .file-input .input-group.file-caption-main {
        //         display: flex !important;
        //     }
        //          .file-input .input-group.file-caption-main .btn-file {
        //     padding: 5px 15px !important;
        //     border-radius: 6px !important;
        //     font-size: 12px !important;
        //     margin-left: -3333% !important;
        // }
        //     </style>
        // ');


        $form->saved(function (Form $form) {
            $route = url('admin/weekly-events-gift/' . request('weekly_event_id'));
            return redirect($route);
        });
        return $form;
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

    protected function detail($id)
    {
        $show = new Show(Reward::findOrFail($id));

        //        $show->id('ID');
        //        $show->name('name');
        //        $show->img('img');
        //        $show->exp('exp');
        //        $show->type('type');
        //        $show->created_at(trans('admin.created_at'));
        //        $show->updated_at(trans('admin.updated_at'));
        $this->extendShow($show);
        return $show;
    }
}
