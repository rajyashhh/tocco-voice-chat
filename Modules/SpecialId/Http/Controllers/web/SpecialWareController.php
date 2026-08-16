<?php

namespace Modules\SpecialId\Http\Controllers\web;

use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;

class SpecialWareController extends  MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    use HasResourceActions;
    protected $title = 'Ware';
    public $permission_name = 'featured-ids';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Featured ids'))
            ->body($this->grid()));
    }
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__($this->title))
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
            ->title(__($this->title))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__($this->title))
            ->body($this->form()));
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     *
     */
    protected function grid()
    {
        $grid = new Grid(new Ware());
        $grid->model()->where('type', 25);
        $grid->column('id', __('Id'));

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('value', __('value'));
            });
        });
        // $grid->column('get_type', __('get_type'))->select(
        //     [
        //         4 => trans('purchase'),
        //         6 => trans('limited time purchase'),
        //     ]
        // );
        $grid->column('value', __('value'))->display(function ($coin) {
            $icon = asset('images/coin.png'); // Ensure this path is correct
            return '<img src="' . $icon . '" alt="coin" style="width: 20px; height: 20px; margin-right: 5px;">' . $coin ?? 0;
        });
        $grid->column('price', __('price'))->display(function ($coin) {
            $icon = asset('images/coin.png'); // Ensure this path is correct
            return '<img src="' . $icon . '" alt="$" style="width: 20px; height: 20px; margin-right: 5px;">' . number_format($coin);
        });
        $grid->column('show_img', __('show_img'))->image('', 30);
        // $grid->column('color', __('color'));
        $grid->column('expire', __('expire'))->display(function ($value) {

            if (@$this->get_type == 6) {
                return $value;
            }
            if ($this->get_type == 4) {
                return '∞';
            }
        });
        if (Admin::user()->can('edit_ware_price') || Admin::user()->can('*')) {
            $grid->column('enable', __('enable'))->switch(Common::getSwitchStates());
        }
        $grid->sort(__('sort'), __('sort'));
        $this->extendGrid($grid);
        $grid->disableExport();
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
        $show = new Show(Ware::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('get_type', __('Get type'));
        $show->field('type', __('Type'));
        $show->field('name', __('Name'));
        $show->field('title', __('Title'));
        $show->field('price', __('Price'));
        $show->field('score', __('Score'));
        $show->field('level', __('Level'));
        $show->field('show_img', __('Show img'));
        $show->field('img1', __('Img1'));
        $show->field('img2', __('Img2'));
        $show->field('img3', __('Img3'));
        $show->field('color', __('Color'));
        $show->field('expire', __('Expire'));
        $show->field('enable', __('Enable'));
        $show->field('sort', __('Sort'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('num', __('Num'));
        $show->field('is_active_for_vip', __('Is active for vip'));
        $show->field('name_en', __('Name en'));
        $show->field('title_en', __('Title en'));
        $show->field('value', __('Value'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Ware());
        $this->disableFormTools($form);

        $form->select('get_type', trans('get_type'))
            ->options(
                [
                    4 => trans('purchase'),
                    6 => trans('limited time purchase'),
                ]
            )->when(6, function (Form $form) {
                $form->number('expire', trans('expire(in days)'))->placeholder(trans('0 if permanent'));
            })->default(4);
        $form->hidden('type')->value(25);
        $form->text('value', __('value'))
            ->creationRules([
                'required',
                Rule::unique('users', 'uuid'),
                function ($attribute, $value, $fail) {
                    if (DB::table('wares')->where('value', $value)->exists()) {
                        return $fail(__('لا يمكنك استخدام القيمه هذه'));
                    }
                }
            ])
            ->updateRules([
                'required',
                Rule::unique('users', 'uuid')->ignore(request()->route('id')),
                // نفس الشيء هنا مع التحقق من عدم وجود القيمة في جدول wares
                // function ($attribute, $value, $fail) {
                //     if (DB::table('wares')->where('value', $value)->exists()) {
                //         return $fail(__('لا يمكنك استخدام القيمه هذه'));
                //     }
                // }
            ]);
        $form->text('title', trans('title'));
        $form->text('title_en', trans('Title en'));
        if (!$form->isEditing()) {
            if (Admin::user()->can('add_ware_price') || Admin::user()->can('*')) {

                $form->number('price', trans('price'))->rules('required|max:9', [

                    'max' => __('The maximum price allowed is 9 hundred million'),
                ])/*->symbol ('💰')*/;
                $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());
            }
        }
        if ($form->isEditing()) {
            if (Admin::user()->can('edit_ware_price') || Admin::user()->can('*')) {
                $form->number('price', trans('price'))->rules('required|max:9', [

                    'max' => __('The maximum price allowed is 9 hundred million'),
                ])/*->symbol ('💰')*/;
                $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());
            }
        }

        $form->number('level', trans('level'));
        $form->text('key', trans('key'));
        $form->image('show_img', trans('img'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        })->default('1.png')->rules('required');
        $form->file('img2', trans('svg'))->name(function ($file) {
            return 'svga_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        })->rules('required');
        $form->color('color', trans('color'));


        $form->number('num', __('num'));
        $form->saving(function (Form $form) {
            if (request('get_type') == 4) {
                $form->expire = 0;
            }
        });


        return $form;
    }
}
