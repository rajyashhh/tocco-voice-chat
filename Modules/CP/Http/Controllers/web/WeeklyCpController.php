<?php

namespace Modules\CP\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Selectables\Gifts;
use App\Helpers\UserCommon;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Content;
use App\Services\AppFeatureService;
use Modules\CP\Entities\CpRelation;
use Modules\Events\Entities\WeeklyStar;
use App\Admin\Controllers\MainController;
use Modules\Achievement\Entities\Achievement;
use Modules\Achievement\Enums\AchievementType;
use Encore\Admin\Controllers\HasResourceActions;

class WeeklyCpController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'weekly-cp';
    // public function __construct()
    // {
    //     (new AppFeatureService)->validateStatusEnable("weekly_cp");
    // }
    public function index(Content $content)
    {
        return $content
            ->title(trans('weekly-cp'))
            ->row(function (Row $row) {
                $row->column(12, $this->grid2());
            })
            ->row(function (Row $row) {
                $row->column(12, $this->grid());
            });
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
            ->title(trans('weekly-cp'))
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
            ->title(trans('weekly-cp'))
            ->body($this->form()->edit($id)));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('weekly-cp'))
            ->body($this->form()));
    }
    protected function grid2()
    {
        $form = new Box();
        $form->view('admin.grid.users.weeklyCpRole');

        return $form;
    }

    protected function grid()
    {
        $grid = new Grid(new WeeklyStar());
        $grid->model()->whereType("weekly_cp");
         $grid->disableRowSelector();
        $grid->column('id', __('Id'));
        $grid->column('start_date_local', __('Start Date'));
        $grid->column('end_date_local', __('End Date'));
        $grid->column('created_at', __('Created at'));
        $grid->column('Actions')->display(function () {
            // توليد الروابط
            $url1 = url('admin/weekly-cp-gift/' . $this->id);

            // إنشاء أزرار HTML
            $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>هداية الفائز </a>";

            // دمج الأزرار في سلسلة واحدة وإرجاعها
            return $button1;
        });


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
        $form->display(__('admin.ID'));
        $form = new Form(new WeeklyStar());
        $this->disableFormTools($form);

        $form->hidden('type', 'Type')->default('weekly_cp');
        $lastStartDate = \Modules\Events\Entities\WeeklyStar::where("type", 'weekly_cp')->max('start_date');

        $minStartDate = $lastStartDate ? \Carbon\Carbon::parse($lastStartDate)->addDay(8)->toDateString() : null;
        $form->date('start_date', __('Start Date'))->default($minStartDate ?? date("Y-m-d"))
            ->rules(function ($form) {

                $lastStartDate = \Modules\Events\Entities\WeeklyStar::where("type", 'weekly_cp')->max('start_date');

                $minStartDate = $lastStartDate ? \Carbon\Carbon::parse($lastStartDate)->addWeek()->toDateString() : null;
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
}
