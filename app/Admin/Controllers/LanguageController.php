<?php

namespace App\Admin\Controllers;


use App\Models\Language;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;

class LanguageController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Language';
    public $permission_name = 'language';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Languages'))
            ->description(__('Manage the available languages'))
            ->body($this->grid()));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('Languages'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Languages'))
            ->body($this->form()));
    }
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Languages'))
            ->body($this->detail($id)));
    }

    protected function grid()
    {
        $grid = new Grid(new Language());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('code', __('Code'));
        $grid->column('direction', __('Direction'))->editable('select', [
            'LTR' => __('Left to Right (LTR)'),
            'RTL' => __('Right to Left (RTL)')
        ]);
        $grid->column('default', __('default language'))->display(function () {
            if (request()->filled('_export_')) {
                return $this->is_default;
            }

            if ($this->is_default == 1) {
                return <<<HTML
                    <span style="display: flex; align-items: center;">
                        <span style="
                            font-size: smaller;
                            background: red;
                            display: inline-block;
                            border-radius: 50%;
                            width: 10px;
                            height: 10px;
                            margin-left: 5px;
                        " title=""></span>
                    </span>
                HTML;
            } else {
                return '<span style="color: #999;"></span>';
            }
        });

        if ((Admin::user()->can('edit-' . $this->permission_name) || Admin::user()->can('*'))) {
            //   $grid->column('is_enabled', __('Is enabled'))->switch();

            $grid->column('is_enabled', __('Is enabled'))
                ->switch() // normal switch
                ->display(function ($value) {
                    // $this is the model here

                    if ($this->is_default == 1) {
                        $enable = __('Enabled');
                        // For default language, just show the value, no switch
                        return $value ? '<span class="label label-success">' . $enable . '</span>' : $value;
                    }
                    return $value;
                });
        }


        $grid->disableCreateButton();  // تعطيل زر الإنشاء
        $grid->disableActions();       // تعطيل زر العرض والتعديل والحذف لكل صف
        $grid->disableRowSelector();   // تعطيل تحديد الصفوف للحذف الجماعي
        $grid->disableExport();        // تعطيل زر التصدير (اختياري)
        
            $grid->tools(function (Grid\Tools $tools) {
                $url = url('/admin/settings?tab=timeSettings');
                $add = __('set default language');

              $customButtonHTML = <<<HTML
                        <a href="javascript:void(0)" onclick="window.location.href='{$url}';" class="btn btn-sm btn-success" style="margin-right:10px;">
                            <i class="fa fa-plus"></i> {$add}
                        </a>
                    HTML;

                $tools->append($customButtonHTML);
            });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
   

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Language());
        $this->disableFormTools($form);

        $form->text('name', __('Name'));
        $form->text('code', __('Code'));
        $form->select('direction', __('Direction'))
            ->options([
                'LTR' => 'Left to Right (LTR)',
                'RTL' => 'Right to Left (RTL)',
            ])
            ->default('LTR');
        $form->switch('is_enabled', __('Is enabled'))->default(1);

        return $form;
    }
}
