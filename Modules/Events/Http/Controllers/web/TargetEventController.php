<?php

namespace Modules\Events\Http\Controllers\web;


use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use App\Services\AppFeatureService;
use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Content;
use Modules\Events\Entities\ChargeTargetEvent;

class TargetEventController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public $permission_name = 'target-event';
    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("target_events");
    }

    public function index(Content $content)
    {
        return $content
            ->title(__('Charge events'))
            ->row(function (Row $row) {

                $row->column(12, function (Column $column) {
                    $column->row($this->grid());
                });
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
            ->title(trans('Charging Events'))
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
            ->title(trans('Charging Events'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Charging Events'))
            ->body($this->form()));
    }
    protected function grid()
    {
        //        dd(ChargeTargetEvent::with('rewards.ware')->first());
        $grid = new Grid(new ChargeTargetEvent());

        $grid->column('id', __('Id'));
        $grid->column('value', __('value'))->display(function ($value) {
            $image = asset('images/coin.png'); // تأكد من أن الصورة موجودة
            if (request()->filled('_export_')) {
                return $value;
            }
            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$value}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });

        if (!request()->filled('_export_')) {
     
            if (Admin::user()->can('browse-' . 'gift-target-event') || Admin::user()->can('*')) {
                $grid->column(__('procedures'))->display(function () {

                    if (request()->filled('_export_')) {
                        return '';
                    }
                    $url1 = url('admin/target-events-gift/' . $this->id);
                    $gifts = __('gifts');
                    $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" .   $gifts . "</a>";

                    return $button1;
                });
            }
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
        $show = new Show(ChargeTargetEvent::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('tile', __('Tile'));
        $show->field('value', __('value'));
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
        $form = new Form(new ChargeTargetEvent());
        $this->disableFormTools($form);

        $form->number('value', __('value'));
        $form->saved(function (Form $form) {
            return redirect()->to('admin/target-events-gift/' . $form->model()->id);
        });
        return $form;
    }
}
