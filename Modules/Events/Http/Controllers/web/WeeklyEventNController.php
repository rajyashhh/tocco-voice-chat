<?php

namespace Modules\Events\Http\Controllers\web;

use App\Models\Gift;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Selectables\Gifts;
use App\Helpers\UserCommon;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Services\AppFeatureService;
use Modules\Events\Entities\Reward;
use Modules\Events\Entities\WeeklyStar;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class WeeklyEventNController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'weekly-star';
    public $hiddenColumns = [];

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("weekly_star");
    }

    public function index(Content $content)
    {
        return parent::index(
            $content
                ->title(__('weekly-events-new'))
                ->row(function (Row $row) {
                    $row->column(12, function (Column $column) {
                        $column->row($this->grid2());
                        $column->row($this->grid());
                    });
                })
        );
    }
    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('weekly-events-new'))
            ->body($this->form()->edit($id)));
    }
    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('weekly-events-new'))
            ->body($this->form()));
    }

    protected function grid2()
    {
        $form = new Box();
        $form->view('admin.grid.users.WeeklyStarRoleView');

        return $form;
    }
    protected function grid()
    {
        $grid = new Grid(new WeeklyStar());
        $grid->model()->whereType("weekly_star")->orderByDesc("id");

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();


            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($this->input) {
                        $query->whereDate('start_date', convertArabicToEnglishNumbers($this->input));
                    }
                }, __('Start Date'), 'from_date')
                    ->date()
                    ->default(convertArabicToEnglishNumbers(request('from_date')));
            });

            // End Date
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($this->input) {

                        $query->whereDate('end_date', convertArabicToEnglishNumbers($this->input));
                    }
                }, __('End Date'), 'to_date')
                    ->date()
                    ->default(convertArabicToEnglishNumbers(request('to_date')));
            });
        });
        $grid->column('id', __('Id'));
        $grid->column('start_date_local', __('Start Date'));
        $grid->column('end_date_local', __('End Date'));
        $grid->column('created_at', __('Created at'));
        if (!request()->filled('_export_')) {
            if (Admin::user()->can('browse-' . 'weekly_star_rewards') || Admin::user()->can('*')) {
                $grid->column(__('procedures'))->display(function () {
                    // توليد الروابط
                    $url1 = url('admin/weekly-events-gift/' . $this->id);

                    // إنشاء أزرار HTML
                    $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" . __('winners gifts') . "</a>";
                    // دمج الأزرار في سلسلة واحدة وإرجاعها
                    return $button1;
                });
            }
        }
        $this->extendGrid($grid);
        return $grid;
    }

    public function store()
    {
        $data = request()->all();
        $data['start_date'] = UserCommon::convertArabicNumbers(request()['start_date']);
        request()->merge($data);
        return parent::store();
    }

    protected function form()
    {
        
        $form = new Form(new WeeklyStar);
        $this->disableFormTools($form);

        $form->display(__('admin.ID'));
        $form = new Form(new WeeklyStar());
        $form->hidden('type', 'Type')->default('weekly_star');
        $lastStartDate = \Modules\Events\Entities\WeeklyStar::where("type", 'weekly_star')->max('start_date');

        $minStartDate = $lastStartDate ? \Carbon\Carbon::parse($lastStartDate)->addWeek()->toDateString() : null;
        if ($minStartDate && $minStartDate < date("Y-m-d")) {
            $minStartDate = date("Y-m-d");
        }
        $form->date('start_date', __('Start Date'))->default($minStartDate ?? date("Y-m-d"))
            ->rules(function ($form) {

                $lastStartDate = \Modules\Events\Entities\WeeklyStar::where("type", 'weekly_star')->max('start_date');

                $minStartDate = $lastStartDate ? \Carbon\Carbon::parse($lastStartDate)->addWeek()->toDateString() : null;
                if ($minStartDate && $minStartDate < date("Y-m-d")) {
                    $minStartDate = \Carbon\Carbon::now()->subDay()->format('Y-m-d');
                }
                if ($minStartDate) {
                    if (!$id = $form->model()->id) {
                        return 'required|after:' . $minStartDate;
                    } else {
                        return 'required';
                    }
                } else {
                    return 'required|date';
                }
            });
        $form->belongsToMany('gifts', Gifts::class)
            ->rules('required|array|size:3', [
                'size' => __('choose only 3 gifts.'),
            ]);

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(WeeklyStar::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('start_date', __('Start date'));
        $show->field('type', __('Type'))->as(function ($type) {
            return  $type == 1 ? "gifts" : ($type == 0 ? "charges" : "PK");
        });


        $this->extendShow($show);

        return $show;
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content->title(__('weekly-events-new'))

            ->row("<h3>" . __('weekly Star') . "</h3>")->row(function ($row) use ($id) {
                $row->column(12, $this->weeklyStar($id));
            })
            ->row("<h3>" . __('gifts') . "</h3>")->row(function ($row) use ($id) {
                $row->column(12, $this->giftList($id));
            })
            ->row("<h3>" . __('Rewards') . "</h3>")->row(function ($row) use ($id) {
                $row->column(12, $this->rewardList($id));
            }));
    }


    protected function weeklyStar($id)
    {

        $grid = new Grid(new WeeklyStar());
        $grid->model()->where('id', $id);

        $grid->column('start_date', __('Start date'));
        $grid->column('end_date', __('End date'));
        $grid->column('type', __('Type'))->display(function ($type) {

            return  $type == 1 ? "gifts" : ($type == 0 ? "charges" : "PK");
        });

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->disableExport();

        return $grid;
    }
    protected function rewardList($id)
    {

        $grid = new Grid(new Reward);
        $grid->model()->where('weekly_star_id', $id);

        $grid->column('level', trans('winners'));
        $grid->column('type', trans('type'))->display(function ($type) {

            return   $type == "coins" ? "coins" : ($type == "ware" ? "ware" : ($type == "vip" ? "vip" : 'achievement'));
        });
        $grid->column('target', trans('gift'))->display(function ($target) {

            if ($this->type == "coins") {
                return $target;
            } elseif ($this->type == "ware") {
                $ware = $this->ware;
                return  $ware ? ($ware->name ?? '') : "";
            } elseif ($this->type == "vip") {
                $vip = $this->vip;
                return  $vip ? ($vip->name ?? '') : "";
            } elseif ($this->type == "badge") {
                $vip = $this->badge;
                return $vip ? (@$vip->name ?? '') : "";
            } else {
                return $this->customAchievement?->name ?? '';
            }
        });
        $grid->column('expire', trans('expire'));

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->disableExport();

        return $grid;
    }


    protected function giftList($id)
    {
        $weeklyEvent = WeeklyStar::find($id);
        $giftIds = $weeklyEvent->gifts->pluck('id')->toArray();
        $grid = new Grid(new Gift);
        $grid->model()->whereIn('id', $giftIds);
        $grid->name(__('name'));
        $grid->column('img', trans('image'))->image('', '30');

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->disableExport();

        return $grid;
    }
}
