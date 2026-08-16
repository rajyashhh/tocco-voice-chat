<?php

namespace Modules\CP\Http\Controllers\web;


use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Admin\Controllers\MainController;
use Modules\CP\Entities\CpLevel;
use Encore\Admin\Layout\Content;

class LevelController extends MainController
{
    protected $title = 'CpLevel';
    public $permission_name = 'cp-level';
    // public function __construct()
    // {
    //     (new AppFeatureService)->validateStatusEnable("target_events");
    // }

    public function index(Content $content)
    {
        $url = url('/admin/cp-relations'); // Define your button URL

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
            ->row($this->grid());
    }

    public function show($id, Content $content)
    {
        $url = url('/admin/cp-relations'); // Define your button URL

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
            ->row($this->grid());
        // $relation_id = request()->route('relation_id');
        // return parent::show($id, $content
        //     ->title(trans('cp-relations'))
        //     ->body($this->detail($id, $relation_id)));
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
        $id = request('id');

        return parent::edit($id, $content
            ->title(trans('cp-relations'))
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
            ->title(trans('cp-relations'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $relation_id = request("relation_id");
        if (!$relation_id) {
            abort(400, 'Relation ID is required');
        }

        $grid = new Grid(new CpLevel());
        $grid->model()->where('cp_relation_id', $relation_id);
        $grid->disableRowSelector();
        $grid->column('id', __('Id'));


        if (!request()->filled('_export_')) {
            $grid->column('level', __('Level'))->editable();
            $grid->column('exp', __('Exp'))->display(function ($value) {

                return number_format($value);
            })->editable();
            $grid->column('img', __('Image'))->image('', '30');

            $grid->column(__('Procedures'))->display(function () use ($relation_id) {
                $url1 = url('admin/cp-level-gifts/' . $this->id);
                $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" . __('gift') . "</a>";
                return $button1;
            });
        } else {
            $grid->column('level', __('Level'));
            $grid->column('exp', __('Exp'))->display(function ($value) {

                return number_format($value);
            });
        }
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        return $grid;
    }


    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @param int $relation_id
     * @return Show
     */
    protected function detail($id, $relation_id)
    {
        $show = new Show(CpLevel::where('id', $id)->where('cp_relation_id', $relation_id)->firstOrFail());

        $show->field('id', __('Id'));
        $show->field('tile', __('Tile'));
        $show->field('value', __('Value'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @param int $relation_id
     * @return Form
     */
    protected function form()
    {
        $relation_id = request("relation_id");
        if (!$relation_id) {
            abort(400, 'Relation ID is required');
        }
        $form = new Form(new CpLevel());
        $form->hidden('cp_relation_id')->value($relation_id);
        $form->textarea('name_ar', __('name_ar'));
        $form->textarea('name_en', __('name_en'));
        $form->number('level', __('Level'))->rules('required|integer|min:1');
        $form->number('exp', __('Exp'))->help(__('sender: 1 coin = 1 exp -- receiver: 1 coin = 1 exp'));
        $form->image('img', __('Image'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        });

        return $form;
    }

    public function update($id)
    {
        $id = request('id');
        $request = request();

        if ($request->ajax() && $request->has('_editable')) {
            $field = $request->input('name');
            $value = $request->input('value');

            $allowedFields = ['level', 'exp'];

            if (in_array($field, $allowedFields)) {
                $model = CpLevel::findOrFail($id);
                $model->$field = $value;
                $model->save();

                return response()->json([
                    'status' => true,
                    'message' => __('Updated successfully'),
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => __('Field not allowed to be edited.'),
                ]);
            }
        }

        Parent::update($id);

        $relation_id = $request->route('relation_id');

        return redirect()->route('admin.cp-levels.index', [
            'relation_id' => $relation_id
        ]);
    }
}
