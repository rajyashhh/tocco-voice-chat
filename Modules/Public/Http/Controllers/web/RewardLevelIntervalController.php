<?php

namespace Modules\Public\Http\Controllers\web;


use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Admin;
use App\Selectables\OVips;
use App\Selectables\Wares;
use Encore\Admin\Layout\Content;
use App\Selectables\CustomAchievements;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;
use Modules\Public\Entities\RewardLevelInterval;

class RewardLevelIntervalController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'RewardLevelInterval';
    public $permission_name = 'reward-level-interval';

    use HasResourceActions;

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Level Gifts'))
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
            ->title(trans('Level Gifts'))
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
        $id = request()->route('id');

        return parent::edit($id, $content
            ->title(trans('Level Gifts'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Level Gifts'))
            ->body($this->form()));
    }

    public function update($id)
    {
        $id = request()->route('id');
        return $this->form()->update($id);
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $level_interval = request('level_interval_id');
        $grid = new Grid(new RewardLevelInterval());

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('type', __('type'))->select([
                    "vip" => __('vip'),
                    'coins' => __('coins'),
                    'achievement' => __('achievement'),
                    "ware" => __("ware"),

                ]);
            });
        });
        $grid->model()->with(['ware', 'vip','customAchievement','customAchievement.images','badge','badge.images'])->where('level_interval_id', $level_interval);
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'));
        $grid->column('gift_id', __('Gifts'))->display(function () {
            if ($this->type == "ware") {
                return @$this->ware->name;
            } elseif ($this->type == "vip") {
                return @$this->vip->name;
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

        $grid->tools(function (Grid\Tools $tools) {
            $url = '/admin/level-intervals';
            $button = '<a href="' . $url . '" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("back") . '</a>';
            $tools->append($button);
        });
        $this->extendGrid($grid);
        Admin::script("
        if (window.innerWidth >= 1024) {
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
        $show = new Show(RewardLevelInterval::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'));
        $show->field('target', __('Target'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new RewardLevelInterval());
        $form->html('<div class="full-column-width">');
        $form->hidden('level_interval_id')->value(request('level_interval_id'));

        $form->select('type', __('Gift type'))
            ->options([
                "ware"        => __('ware'),
                "vip"         => __('vip'),
                "coins"       => __('coins'),
                "achievement" => __('achievement'),
            ])
            ->when('ware', function () use ($form) {
                $form->belongsTo('target1', Wares::class, trans('wares'));
                $form->number('expire', __('expire'));
            })
            ->when('vip', function () use ($form) {
                $form->belongsTo('target2', OVips::class, trans('vips'));
                $form->number('expire', __('expire'));
            })
            ->when('coins', function () use ($form) {
                $form->number('target3', __('coins'));
            })
            ->when('achievement', function () use ($form) {
                $form->belongsTo('target4', CustomAchievements::class, trans('Custom achievement'));

                $form->number('expire', __('expire'));
            })
            ->rules('required');
        $form->html('</div>');

        Admin::style('

        .rtl .fields-group .form-group {
            display: block !important;
        }

        .form-horizontal .fields-group > .col-md-12 > .form-group .input-group {
            width: 50% !important;
        }
    ');

        $form->saving(function (Form $form) {
            $type = $form->type;
            $errors = [];

            switch ($type) {
                case 'ware':
                    if (!$form->target1) $errors[] = __('wares') . ' ' . __('is required');
                    if (empty($form->expire) || !is_numeric($form->expire)) $errors[] = __('expire') . ' ' . __('is required and must be numeric');
                    break;
                case 'vip':
                    if (!$form->target2) $errors[] = __('vips') . ' ' . __('is required');
                    if (empty($form->expire) || !is_numeric($form->expire)) $errors[] = __('expire') . ' ' . __('is required and must be numeric');
                    break;
                case 'coins':
                    if (empty($form->target3) || !is_numeric($form->target3)) $errors[] = __('coins') . ' ' . __('is required and must be numeric');
                    $form->expire = null;
                    break;
                case 'achievement':
                    if (!$form->target4) $errors[] = __('custom_achievement') . ' ' . __('is required');
                    if (empty($form->expire) || !is_numeric($form->expire)) $errors[] = __('expire') . ' ' . __('is required and must be numeric');
                    break;
            }

            if (count($errors)) {
                admin_error(__('Validation error'), implode('<br>', $errors));
                return back();
            }
        });
        $form->tools(function (Form\Tools $tools) {
            $url = '/admin/reward_level_interval/' . request('level_interval_id');
            $button = '<a href="' . $url . '" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("back") . '</a>';
            $tools->append($button);
            if (request()->is('*edit*')) {
                $tools->disableDelete();
            }
        });


        return $form;
    }
}
