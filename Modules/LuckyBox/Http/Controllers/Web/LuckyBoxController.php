<?php

namespace Modules\LuckyBox\Http\Controllers\Web;

use App\Models\Config;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use Modules\LuckyBox\Entities\Box;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;

class LuckyBoxController extends MainController
{
    public $permission_name = 'boxes';
    public $permission_setting = 'box-settings';
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('boxes'))
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
            ->title(trans('boxes'))
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
        return  parent::edit($id, $content
            ->title(trans('boxes'))
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
            ->title(trans('boxes'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Box);

        $grid->id(__('ID'));
        $grid->column('type', __('type'))->using([0 => __('normal'), 1 => __('super')]);
        $grid->column('coins', __('coins'))->display(function ($coins) {

            $image = asset('images/coin.png'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$coins}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('users', __('users'));
        $grid->column('image', __('image'))->image('', 30);
        $grid->column('duration', __('duration'));
        $grid->disableExport();
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
        $show = new Show(Box::findOrFail($id));

        //        $show->id('ID');
        //        $show->type('type');
        //        $show->coins('coins');
        //        $show->users('users');
        //        $show->image('image');
        //        $show->has_label('has_label');
        //        $show->duration('duration');
        //        $show->created_at(trans('admin.created_at'));
        //        $show->updated_at(trans('admin.updated_at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Box);
        $this->disableFormTools($form);

        $form->display(__('ID'));
        $form->select('type', __('type'))
            ->options([0 => __('normal'), 1 => __('super')])
            ->attribute(['id' => 'box_type'])
            ->required();

        $form->decimal('coins', __('coins'));
        $form->decimal('users', __('users'))->attribute(['id' => 'users_field']);
        $form->decimal('duration', __('duration'))->help(__('in minutes'))->attribute(['id' => 'duration_field']);


        $addPlaceholder = __('Enter users count');
        $deleteText = __('Delete');

        $form->dynamicFields('dynamic_users_values', __('Dynamic Fields'))
            ->attribute(['name' => 'dynamic_users_values'])
        ;

        $form->image('image', __('image'));
        $form->switch('has_label', __('has label'))->states(Common::getSwitchStates());
        $form->text('default_label', __('default label'));
        $form->html(<<<HTML
        <script>
           $(document).ready(function () {
                initDynamicFieldsScript();

            });
        </script>
        HTML);
        $form->html("
    <script>
        window.translations = {
            add_placeholder: " . json_encode($addPlaceholder) . ",
            delete_text: " . json_encode($deleteText) . "
        };
    </script>
");


        $form->saving(function (Form $form) {
            $dynamicFields = request('dynamic_fields', []);
            $combinedValues = implode(',', array_filter($dynamicFields));
            $form->model()->dynamic_users_values = $combinedValues;
            $normalDuration = Common::getConf('normal_box_duration') ?? 1;
        });

        return $form;
    }

    public function box_settings(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_setting);
        }
        $config = Config::whereIn('name', ['app_wallet_lucky_box', 'normal_box_duration','lucky_box_percentage'])->pluck('value', 'name')->toArray();
        return $content->view('box_settings', compact('config'));
    }
}
