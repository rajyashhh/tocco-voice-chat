<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Helpers\Common;
use Illuminate\Support\Str;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Box;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Row;

class DedicateWareController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'gift-from-the-store';

    public function index(Content $content)
    {
        session(['last_ware_type' => request()->get('type', 1)]);
        return parent::index($content
            ->title(trans('Gift from the store'))
            ->row(function (Row $row) {
                $row->column(12, $this->tabsComponent());
            })
            ->row(function (Row $row) {
                $row->column(12, $this->grid());
            }));
    }
    private function tabsComponent()
    {
        $content = new Row();

        // Define your type mapping
        $typeMap = SELECTED_USED_WARE;

        $types =  collect($typeMap);
        $currentType = request()->get('type', $types->keys()->first());

        $box = new Box(content: view('admin.grid.Form.wareTables', [
            'types' => $types,
            'currentType' => $currentType
        ]));

        $content->column(12, $box);

        return $content;
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
            ->title(trans('wares'))
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
            ->title(trans('wares'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('wares'))
            ->body($this->form()));
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $type = request()->get('type', 4);
        $typeSpecial = false;

        $grid = new Grid(new Ware);
        $grid->model()->orderByDesc('created_at');

        $grid->model()->where('type',  $type)->whereIn('get_type', [4,6])->where('type', '!=', 25);

        $grid->id('ID');
        $grid->column('name', __('name'));
        $grid->column('price', __('price'))->currency();
        $grid->column('show_img', __('show_img'))->display(function ($path) {
            /** @var Ware $this */
            $defaultImage = asset("images/ware-image.jpg");
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $grid->column('img2', __('show_img'))->display(function ($path) {
            /** @var Ware $this */
            $defaultImage = asset("images/ware-image.jpg");
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });

        if (!$typeSpecial) {
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
                    25 => trans('Special Id'),
                    21 => trans('sound effect'),
                    22 => trans('upload GIF image')
                ]
            );
        }

        if ($typeSpecial) {
            $grid->value(__('value'));
        }
        if (Admin::user()->can('gift-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $grid->column('return', __('dedicate'))->display(function () {

                return (new \App\Admin\Actions\WareDedicateAction($this->id))->render();
            });
        }

        $grid->disableExport();
        $grid->disableActions();
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            // $actions->add(new DedicateAction());
        });
        $grid->disableCreateButton();
        $grid->disableRowSelector();

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
        return $grid;
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
                4 => __('purchase'),
                //               5=>trans ('background modification'),
                //    6=>trans ('limited time purchase'),
                //               7=>trans ('treasure box point exchange'),
                //               8=>trans ('cp level unlock'),
            ]
        )->default(4);
        $form->select('type', trans('type'))->options(
            translate(WARE_DEDICATE)
        )->rules('required');
        //        ->rules (function ($form){
        //            if (!$id = $form->model()->id) {
        //                return 'required';
        //            }
        //        });
        $form->text('name', trans('name'));
        $form->text('title', trans('title'));
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
        $form->number('level', trans('level'));
        $form->text('key', trans('key'));
        $form->image('show_img', trans('img'))->default('1.png')->rules('required');

        //        $form->image('img1', trans('img'));
        $form->file('img2', trans('svg'))->name(function ($file) {
            return 'svga_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        });
        $form->select('image_type', __('image_type'))->options(
            [
                'svga' => __('svga'),
                'alpha' => __('alpha'),
                'mp4' => __('mp4'),
            ]
        )->required();
        //        $form->file('img3', trans('video'));
        $form->color('color', trans('color'));
        $form->number('expire', trans('expire(in days)'))->placeholder(trans('0 if permanent'));

        //        $form->number('sort', 'sort');
        $form->number('num', __('num'));

        // $form->image('show_img', trans('img'))->default('1.png')->rules(function ($rules) {
        //     $rules->required(); // Add a custom rule to ensure a file is uploaded
        //     $rules->image(); // Add the image validation rule to check if the uploaded file is an image
        //     return $rules;
        // });

        return $form;
    }
}
