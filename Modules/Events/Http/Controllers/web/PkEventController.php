<?php

namespace Modules\Events\Http\Controllers\web;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\UserCommon;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Modules\Vip\Entities\OVip;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Modules\Badge\Entities\Badge;
use App\Services\AppFeatureService;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkReward;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class PkEventController extends MainController
{

    use HasResourceActions;



    public $permission_name = 'pk-event';
    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("pk_event");
    }

    public function index(Content $content)
    {
        return $content
            ->title(__('pk-events'))
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $column->row($this->grid2());
                    $column->row($this->grid());
                });
            });
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
        return $content
            ->title(trans('pk-events'))
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->title(trans('pk-events'))
            ->body($this->form());
    }

    protected function grid2()
    {
        $form = new Box();
        $form->view('admin.grid.users.PkEventRoleView');

        return $form;
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PkEvent());
        $grid->model()->orderByDesc("id");
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
        if (!request()->filled('_export_') && (Admin::user()->can('browse-' . 'pk-event-rewards') || Admin::user()->can('*'))) {
            $grid->column('الاجرائات')->display(function () {
                // توليد الروابط
                $url1 = url('admin/pk-events-gift/pk-star/' . $this->id);
                $url2 = url('admin/pk-events-gift/pk-king/' . $this->id);
                $url3 = url('admin/pk-events-gift/pk-room/' . $this->id);

                $pk_star = 'النجم PK  هداية ';
                $pk_king = 'الملك PK  هداية ';
                $pk_owner = 'الغرفة pk هداية ';
                if (app()->getLocale() == 'en') {
                    $pk_star = 'star PK gift';
                    $pk_king = 'king PK gift';
                    $pk_owner = 'room PK gift';
                }
                // إنشاء أزرار HTML
                $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" . $pk_star . "  </a>";
                $button2 = "<a href='{$url2}' class='btn btn-sm btn-danger'>" . $pk_king . " </a>";
                $button3 = "<a href='{$url3}' class='btn btn-sm btn-primary'>" . $pk_owner . " </a>";

                // دمج الأزرار في سلسلة واحدة وإرجاعها
                return $button1 . ' ' . $button2 . ' ' . $button3;
            });
        }
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
        $show = new Show(PkEvent::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('start_date', __('Start Date'));
        $show->field('end_date', __('End Date'));
        $this->extendShow($show);
        return $show;
    }


    public function store()
    {
        $data = request()->all();
        
        // Convert Arabic numbers in start_date
        if (isset($data['start_date'])) {
            $data['start_date'] = UserCommon::convertArabicNumbers($data['start_date']);
        }
        
        request()->merge($data);
        
        return parent::store();
    }

    public function update($id)
    {
        $data = request()->all();
        
        // Convert Arabic numbers in start_date
        if (isset($data['start_date'])) {
            $data['start_date'] = UserCommon::convertArabicNumbers($data['start_date']);
        }
        
        request()->merge($data);
        
        return parent::update($id);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PkEvent());
        $this->disableFormTools($form);

        $form->display('id', __('admin.ID'));
        
        $lastStartDate = \Modules\Events\Entities\PkEvent::max('start_date');
        $minStartDate = $lastStartDate ? \Carbon\Carbon::parse($lastStartDate)->addDay(8)->toDateString() : null;
        if ($minStartDate && $minStartDate < date("Y-m-d")) {
            $minStartDate = date("Y-m-d");
        }
        // Use start_date directly instead of start_date_local
        $form->date('start_date', __('Start Date'))->default($minStartDate ?? date("Y-m-d"))
            ->rules(function ($form) {
                $lastStartDate = \Modules\Events\Entities\PkEvent::max('start_date');
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

        // Saving callback to convert Arabic numbers
        $form->saving(function (Form $form) {
            $startDate = request()->input('start_date');
            if ($startDate) {
                $form->start_date = UserCommon::convertArabicNumbers($startDate);
            }
        });

        return $form;
    }


    public function show($id, Content $content)
    {
        return $content
            ->title(trans('pk-events'))
            ->row("<h3>" . __('PK Event') . "</h3>")->row(function ($row) use ($id) {
                $row->column(12, $this->PkEvent($id));
            })
            ->row("<h3>" . __('Rewards') . "</h3>")->row(function ($row) use ($id) {
                $row->column(12, $this->rewardList($id));
            });
    }


    protected function PkEvent($id)
    {

        $grid = new Grid(new PkEvent());
        $grid->model()->where('id', $id);

        $grid->column('start_date_local', __('Start Date'));
        $grid->column('end_date_local', __('End Date'));
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->disableExport();

        return $grid;
    }
    protected function rewardList($id)
    {

        $grid = new Grid(new PkReward);
        $grid->model()->where('pk_event_id', $id);

        $grid->column('level', trans('level'));
        $grid->column('type', trans('type'))->display(function ($type) {

            return   $type == "coins" ? "coins" : ($type == "ware" ? "ware" : ($type == "vip" ? "vip" : 'achievement'));
        });
        $grid->column('target', trans('target'))->display(function ($target) {

            if ($this->type == "coins") {
                return $target;
            } elseif ($this->type == "ware") {
                $ware = $this->ware;
                return $ware ? ($ware->name ?? "") : "";
            } elseif ($this->type == "vip") {
                $vip = $this->vip;
                return $vip ? ($vip->name ?? "") : "";
            } elseif ($this->type == "badge") {
                $vip = $this->badge;
                return $vip ? (@$vip->name ?? '') : "";
            } else {
                return $this->customAchievement?->name ?? '';
            }
        });

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableFilter();
        $grid->disableRowSelector();
        $grid->disableExport();

        return $grid;
    }
}
