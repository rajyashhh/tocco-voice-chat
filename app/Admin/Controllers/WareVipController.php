<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Illuminate\Support\Str;
use Modules\Vip\Entities\VipPrivilege;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;

class WareVipController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    use HasResourceActions;
    public $permission_name = 'wares-vips';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('wares-vips'))
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
        return parent::show($id,$content
            ->title(trans('wares-vips'))
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
        return parent::edit($id,$content
            ->title(trans('wares-vips'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('wares-vips'))
            ->body($this->form()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Ware());

        $grid->model()->where('get_type', 1)->orderByDesc('is_active_for_vip');
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('level', __('level'));
            });

            $filter->column(1 / 2, function ($filter) {

                $filter->equal('type', __('type'))->select(
                    VipPrivilege::pluck('name', 'type')->toArray()
                );
            });
        });

        $grid->id(__('ID'));
        $grid->column('get_type', __('get_type'))->select(
            [
                1 => trans('vip level automatic acquisition'),
                //               2=>trans ('activity'),
                //               3=>trans ('treasure box'),
                4 => trans('purchase'),
                //               5=>trans ('background modification'),
                6 => trans('limited time purchase'),
                //               7=>trans ('treasure box point exchange'),
                //               8=>trans ('cp level unlock'),
            ]
        );
        $grid->column('type', __('type'))->select(
            [
                1 => trans('Gemstone'),
                3 => trans('Card Scroll'),
                4 => trans('Avatar Frame'),
                5 => trans('Bubble Frame'),
                6 => trans('Entering Special Effects'),
                7 => trans('Microphone Aperture'),
                8 => trans('Badge'),
                9 => trans('NoKick'),
                10 => trans('Icon'),
                11 => trans('intro animation'),
                12 => trans('wapel'),
                13 => trans('hide country and last login'),
                14 => trans('vip gifts'),
                15 => trans('no pan'),
                16 => trans('hidden room'),
                17 => trans('anonymous man'),
                18 => trans('colored name'),
                19 => trans('profile visitors hide in'),
                20 => trans('hide last active'),
                21 => trans('sound effect'),
                22 => trans('upload GIF image'),
                28 => trans('profile frame')


            ]
        );
        $grid->column('name', __('name'))->editable();
        $grid->title(__('title'));
        $grid->column('price', __('price'))->currency();
        //        $grid->score('score');
        $grid->level(__('level'));
        $grid->column('show_img', __('show_img'))->image('', 30);
        $grid->column('color', __('color'));
        $grid->expire(__('expire'));
        if (Admin::user()->can('edit_ware_price') || Admin::user()->can('*')) {
        $grid->column('enable', __('enable'))->switch(Common::getSwitchStates());
        }
        $grid->column('is_active_for_vip', __('active_for_vip'))->switch(Common::getSwitchStates());
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

        $form->display('ID');
        $form->select('get_type', trans('get_type'))->options(
            [
                1 => trans('vip level automatic acquisition'),
                //               2=>trans ('activity'),
                //               3=>trans ('treasure box'),
                //  4=>trans ('purchase'),
                //               5=>trans ('background modification'),
                //   6=>trans ('limited time purchase'),
                //               7=>trans ('treasure box point exchange'),
                //               8=>trans ('cp level unlock'),
            ]
        )->default(1);
        $form->select('type', trans('type'))->options(function ($value) {
            $privileges = [];
            foreach (VipPrivilege::get() as $pri) {
                $privileges[$pri->type] =  $pri->name;
            }
            return $privileges;
        })->rules('required');
        //        ->rules (function ($form){
        //            if (!$id = $form->model()->id) {
        //                return 'required';
        //            }
        //        });
        $form->text('name', trans('name'));
        $form->text('name_en', trans('Name en'));
        $form->text('title', trans('title'));
        $form->text('title_en', trans('Title en'));
        if (!$form->isEditing()) {
            if (Admin::user()->can('add_ware_price') || Admin::user()->can('*')) {
                $form->currency('price', __('price'))->symbol('💰');
                $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());
            }
        }
        if ($form->isEditing()) {
            if (Admin::user()->can('edit_ware_price') || Admin::user()->can('*')) {
                $form->currency('price', __('price'))->symbol('💰');
                $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());
            }
        }
        //        $form->number('score', trans('score'));
        $form->number('level', trans('level'))->rules(
            'required|numeric|min:1',
            [
                'min'   => 'levels can not be 0',
            ]
        );
        $form->text('key', trans('key'));
        $form->image('show_img', trans('img'))->name(function ($file) {
            $extension = $file->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = $file->guessExtension();
            }
            return now()->timestamp . rand(0, 999) . '.' . $extension;
        })->default('1.png');
        $form->switch('half_image_profile', trans('half image'))->states(Common::getSwitchStates());
        //        $form->image('img1', trans('img'));
        $form->file('img2', trans('svg'))->name(function ($file) {

            $wareId = request()->route('wares-vips'); // Retrieve the current Ware ID (if editing)
            $wareId = $wareId ?? Ware::max('id') + 1; // Predict next ID if creating

            // Determine the file prefix based on type and environment
            $type = request()->input('type'); // Get the selected type
            $prefix = '';

            if (app()->environment('local')) {
                $prefix = 't-';
            }

            $ext = $file->getClientOriginalExtension();
            if (empty($ext)) {
                $ext = $file->guessExtension();
            }
            if ($type == 4) { // For "Avatar Frame"
                return $prefix . 'w-f' . $wareId . '.' . $ext;
            } elseif ($type == 5) { // For "Bubble Frame"
                return $prefix . 'w-b' . $wareId . '.' . $ext;
            } elseif ($type == 10) { // For "Bubble Frame"
                return $prefix . 'w-vb' . $wareId . '.' . $ext;
            } else {
                // Default fallback naming (optional)
                return $prefix . 'w-default' . $wareId . '.' . $ext;
            }
        });
        $form->select('image_type', __('image_type'))->options(
            [
                'svga' => __('svga'),
                'vap' => __('vap'),
                'alpha' => __('alpha'),
                'mp4' => __('mp4'),
                'image' => __('image'),
            ]
        )->rules(function ($form) {
            // Add a conditional validation rule for 'image_type'
            if ($form->model()->img2) {  // Check if img2 is uploaded
                return 'required';
            }
            return 'nullable';  // If no image uploaded, 'image_type' is not required
        });
        //        $form->file('img3', trans('video'));
        $form->color('color', trans('color'));
        $form->number('expire', trans('expire(in days)'))->placeholder(trans('0 if permanent'));

        $form->switch('is_active_for_vip', __('active_for_vip'))->states(Common::getSwitchStates());
        //        $form->number('sort', 'sort');
        $form->number('num', __('num'));

        return $form;
    }
}
