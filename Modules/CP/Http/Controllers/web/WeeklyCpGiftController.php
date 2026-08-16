<?php

namespace Modules\CP\Http\Controllers\web;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Selectables\Badges;
use Modules\Vip\Entities\OVip;
use App\Selectables\WaresByType;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Services\AppFeatureService;
use Modules\CP\Entities\WeeklyCpGift;
use App\Selectables\CustomAchievements;
use Modules\Events\Entities\WeeklyStar;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class WeeklyCpGiftController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'weekly_cp_gift';
    public function __construct()
    {
        $weekly_cp_id = request('weekly_cp_id');
        $data = WeeklyStar::find($weekly_cp_id);
        // if (@$data->type == "weekly_cp") {

        //     (new AppFeatureService)->validateStatusEnable("weekly_cp");
        // }
    }
    public function index(Content $content)
    {
        $url = url('/admin/weekly-cp'); // Define your button URL

        $buttonHTML = <<<HTML
    <a href="{$url}" class="btn btn-sm btn-success" style="margin-bottom: 20px;">
        <i class="fa fa-arrow-left"></i> رجوع
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
        return $content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form());
    }

    public function update($id)
    {
        $id = request()->route('id');
        return $this->form()->update($id);
    }

    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        return $content
            ->header(trans('admin.edit'))
            ->description(trans('admin.description'))
            ->body($this->form()->edit($id));
    }

    public function show($id, Content $content)
    {
        return $content
            ->header(trans('admin.detail'))
            ->description(trans('admin.description'))
            ->body($this->detail($id));
    }
    protected function grid1()
    {
        $type = 1;
        $weekly_cp_id = request('weekly_cp_id');
        $grid = new Grid(new WeeklyCpGift());
        $grid->disableRowSelector();
        $grid->column('created_at')->hide();
        $grid->model()->where("weekly_cp_id", $weekly_cp_id)->where("level", $type);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
        $grid->column('gift_id', __('gifts'))->display(function () {
            if (@$this->type == "ware") {
                return @$this->ware->name;
            } elseif (@$this->type == "vip") {
                return @$this->vip->name;
            } elseif (@$this->type == "coins") {
                return @$this->target;
            } elseif ($this->type == "badge") {
                return @$this->badge->name ?? '';
            } elseif (@$this->type == "achievement") {
                return $this->customAchievement?->name ?? '';
            }
        });
        $grid->column('created_at', __('Created at'));

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) {
            $url = request()->route('weekly_cp_id') . "/1/create";
            $customButtonHTML = <<<HTML

                <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                    <i class="fa fa-plus"></i> ضيف
                </a>
                <h3 style="margin-right: 10px;">جوائز للفائز الأول</h3>
            HTML;
            $tools->append($customButtonHTML);
        });
        $grid->disableExport();
        return $grid;
    }

    protected function grid2()
    {
        $type = 2;
        $weekly_cp_id = request('weekly_cp_id');
        $grid = new Grid(new WeeklyCpGift());
        $grid->disableRowSelector();
        $grid->column('created_at')->hide();
        $grid->model()->where("weekly_cp_id", $weekly_cp_id)->where("level", $type);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
        $grid->column('gift_id', __('gifts'))->display(function () {
            if ($this->type == "ware") {
                return @$this->ware->name;
            } elseif ($this->type == "vip") {
                return @$this->vip->name;
            } elseif ($this->type == "badge") {
                return @$this->badge->name ?? '';
            } elseif ($this->type == "coins") {
                return @$this->target;
            } elseif ($this->type == "achievement") {
                return $this->customAchievement?->name ?? '';
            }
        });
        $grid->column('created_at', __('Created at'));

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) {
            $url = request()->route('weekly_cp_id') . "/2/create";
            $customButtonHTML = <<<HTML
            <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                <i class="fa fa-plus"></i>ضيف
            </a>
            <h3 style="margin-right: 10px;">جوائز للفائز الثاني</h3>
            HTML;
            $tools->append($customButtonHTML);
        });
        $grid->disableExport();

        return $grid;
    }

    protected function grid3()
    {
        $type = 3;
        $weekly_cp_id = request('weekly_cp_id');
        $grid = new Grid(new WeeklyCpGift());
        $grid->disableRowSelector();
        $grid->column('created_at')->hide();
        $grid->model()->where("weekly_cp_id", $weekly_cp_id)->where("level", $type);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
        $grid->column('gift_id', __('gifts'))->display(function () {
            if ($this->type == "ware") {
                return @$this->ware->name;
            } elseif ($this->type == "vip") {
                return @$this->vip->name;
            } elseif ($this->type == "badge") {
                return @$this->badge->name ?? '';
            } elseif ($this->type == "coins") {
                return @$this->target;
            } elseif ($this->type == "achievement") {
                return $this->customAchievement?->name ?? '';
            }
        });
        $grid->column('created_at', __('Created at'));

        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) {
            $url = request()->route('weekly_cp_id') . "/3/create";
            $customButtonHTML = <<<HTML
            <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                <i class="fa fa-plus"></i>ضيف
            </a>
            <h3 style="margin-right: 10px;"> جوائز للفائز الثالث </h3>
            HTML;
            $tools->append($customButtonHTML);
        });
        $grid->disableExport();
        return $grid;
    }

    protected function form()
    {
        $form = new Form(new WeeklyCpGift());
        $this->disableFormTools($form);
        $this->addHiddenFields($form);
        $this->addTypeField($form);
        $this->addExpireField($form);
        $this->addGenderField($form);
        $this->addSavedRedirect($form);

        return $form;
    }

    /**
     * Hidden fields
     */
    protected function addHiddenFields(Form $form)
    {
        $form->hidden('weekly_cp_id')->value(request('weekly_cp_id'));
        $form->hidden('level')->value(request('level'));
    }

    /**
     * Type field and dependent fields
     */
    protected function addTypeField(Form $form)
    {
        $form->select('type', trans('type'))->options([
            "ware" => __('ware'),
            "vip" => __('vip'),
            "badge" => __('badge'),
            "coins" => __('coins'),
            "achievement" => __('achievement')
        ])->when('ware', function () use ($form) {
            $this->addWareField($form);
        })->when("badge", function () use ($form) {
            $this->addBadgeField($form);
        })->when('vip', function () use ($form) {
            $this->addVipField($form);
        })->when('coins', function () use ($form) {
            $this->addCoinsField($form);
        })->when('achievement', function () use ($form) {
            $this->addAchievementField($form);
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

    /**
     * Ware field
     */
    protected function addWareField(Form $form)
    {
        $prefix = 'wares';
        $form->belongsTo('target', WaresByType::class, __('Ware'), function ($form) use ($prefix) {
            $form->setElementName($prefix . 'target')
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
        $form->hidden('sub_type');
    }

    /**
     * VIP field
     */
    protected function addVipField(Form $form)
    {
        $form->select('target2', trans('vips'))->options(function () {
            $ops = [];
            $vips = OVip::query()->select('id', 'name')->get();
            foreach ($vips as $vip) {
                $ops[$vip->id] = $vip->name;
            }
            return $ops;
        });
    }

    /**
     * Coins field
     */
    protected function addCoinsField(Form $form)
    {
        $form->number("target3", __("coins"));
    }

    /**
     * Achievement field
     */
    protected function addAchievementField(Form $form)
    {
        // $form->image("target4", __('image'))->name(function ($file) {
        //     return now()->timestamp . '.' . $file->guessExtension();
        // })->disk('gcs');

        $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));
    }

    /**
     * Expire field
     */
    protected function addExpireField(Form $form)
    {
        $form->number('expire', __('expire'))->required();
    }

    /**
     * Gender field
     */
    protected function addGenderField(Form $form)
    {
        $form->select('gender', __('gender'))->options([
            'all' => __('all'),
            'male' => __('Male'),
            'female' => __('Female')
        ])->required();
    }

    /**
     * Redirect after saved
     */
    protected function addSavedRedirect(Form $form)
    {
        $form->saved(function (Form $form) {
            $route = url('admin/weekly-cp-gift/' . request('weekly_cp_id'));
            return redirect($route);
        });
    }
}
