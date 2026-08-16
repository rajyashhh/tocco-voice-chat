<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;
use Modules\Public\Http\Services\UserCounterServices;

class WareController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'wares';
    public $hiddenColumns = [];

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Products'))
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
        $grid = new Grid(new Ware);
        // $grid->model()->whereNot('get_type', 1);
        $grid->model()->where('get_type', '!=', 1);

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('level', __('level'));
            });

            $filter->column(1 / 2, function ($filter) {

                $filter->equal('type', __('type'))->select([
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
                    28 => trans('profile frame')


                ]);
            });


            $filter->where(function ($query) {
                $query->whereNull('expire')->orWhere('expire', 0);
            }, trans('filters.expired_zero_or_null'))->checkbox([
                1 => trans('filters.yes'),
            ]);
            
            
    
        });

        $grid->id(__('ID'));
        $grid->column('name', __('name'))->editable();
        if (Admin::user()->can('edit_ware_price') || Admin::user()->can('*')) {
            $grid->column('price', __('price'))->editable();
            // $grid->setResource('wares/toggle-enable');
            //  $grid->column('enable', __('enable'))->switch(Common::getSwitchStates());
            $grid->column('enable', __('enable'))->display(function ($value) {
                $checked = $value ? 'checked' : '';
                $id = $this->id;

                return <<<HTML
                    <label class="switch">
                        <input type="checkbox" class="toggle-enable" data-id="{$id}" {$checked}>
                        <span class="slider round"></span>
                    </label>
                HTML;
            });

            Admin::style("
                    .switch {
                        position: relative;
                        display: inline-block;
                        width: 50px;
                        height: 24px;
                    }

                    .switch input {
                        opacity: 0;
                        width: 0;
                        height: 0;
                    }

                    .slider {
                        position: absolute;
                        cursor: pointer;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-color: #ccc;
                        transition: .4s;
                        border-radius: 24px;
                    }

                    .slider:before {
                        position: absolute;
                        content: '';
                        height: 18px;
                        width: 18px;
                        left: 3px;
                        bottom: 3px;
                        background-color: white;
                        transition: .4s;
                        border-radius: 50%;
                    }

                    input:checked + .slider {
                        background-color: #4CAF50;
                    }

                    input:checked + .slider:before {
                        transform: translateX(26px);
                    }
                ");

            Admin::script("
                    $(document).off('change', '.toggle-enable').on('change', '.toggle-enable', function () {
                        var id = $(this).data('id');
                        var enable = $(this).is(':checked') ? 1 : 0;

                        $.ajax({
                            url: '/admin/wares/toggle-enable/' + id,
                            method: 'PUT',
                            data: {
                                enable: enable,
                                _token: LA.token
                            },
                            success: function (res) {
                                if (res.status) {
                                    toastr.success(res.message);
                                } else {
                                    toastr.error(res.message || 'حدث خطأ.');
                                }
                            },
                            error: function (xhr) {
                                let msg = xhr.responseJSON?.message || 'خطأ في الاتصال بالسيرفر.';
                                toastr.error(msg);
                            }
                        });
                    });
                ");
        } else {
            $grid->column('price', __('price'));
        }

        $grid->column('show_img', __('show_img'))->image('', 30);
        $grid->column('img2', __('show_img'))->display(function ($path) {
            /** @var Ware $this */
            $url = getImagePath($path);
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $grid->column('get_type', __('get_type'))->select(
            [
                //  1=>trans ('vip level automatic acquisition'),
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
                28 => trans('profile frame')


            ]
        );

        $grid->title(__('title'));
        $states = [
            'on' => ['value' => 1, 'text' => 'open', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => 'close', 'color' => 'default'],
        ];
        //        $grid->score('score');
        $grid->level(__('level'));

        $grid->column('color', __('color'));
        $grid->expire(__('expire'));
        $grid->column('is_active_for_vip', __("active vip"))->switch($states);

        $grid->sort(__('sort'), __('sort'));
        $this->extendGrid($grid);
        $grid->disableExport();

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");
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

        //        $show->id('ID');
        //        $show->get_type('get_type');
        //        $show->type('type');
        //        $show->name('name');
        //        $show->title('title');
        //        $show->price('price');
        //        $show->score('score');
        //        $show->level('level');
        //        $show->show_img('show_img');
        //        $show->img1('img1');
        //        $show->img2('img2');
        //        $show->img3('img3');
        //        $show->color('color');
        //        $show->expire('expire');
        //        $show->enable('enable');
        //        $show->sort('sort');
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
        $form = new Form(new Ware());

        if ($form->isEditing()) {
            $form->display('id', __('ID'));
        }
        $form->select('get_type', trans('get_type'))->options(
            translate(GET_TYPE_WARE)
        )->default(4);
        $form->select('type', trans('type'))->options(
            translate(TYPE_WARE)
        )->attribute(['id' => 'type'])->rules('required');
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
                $form->currency('price', __('price'));
                // $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());
            }
        }
        if ($form->isEditing()) {
            if (Admin::user()->can('edit_ware_price') || Admin::user()->can('*')) {
                $form->currency('price', __('price'));
                // $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());
            }
        }
        //        $form->number('score', trans('score'));
        // $form->number('level', trans('level'));
        $states = [
            'on' => ['value' => 1, 'text' => 'open', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => 'close', 'color' => 'default'],
        ];
        // $form->switch('is_active_for_vip', __("active vip"))->states($states);
        // $form->number('exp', __('exp'));
        $form->image('show_img', trans('img'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        })->default('1.png');
        // $form->switch('half_image_profile', trans('half image'))->states(Common::getSwitchStates());
        //        $form->image('img1', trans('img'));
        $form->file('img2', trans('svg'))->name(function ($file) {
            return 'svga_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
        });
        $form->select('image_type1', __('animation file type'))
            ->options([
                'svga' => __('svga'),
                'alpha' => __('alpha'),
                'mp4' => __('mp4'),
                'vap' => __('vap'),
                'png' => __('image:(jpg, jpeg, png,gif, bmp, tiff, svg, webp, mov, avi, wmv, flv, mkv, webm)'),
            ])
            ->help(__('animation file type help'))
            ->attribute(['id' => 'image_type1']);
        $form->fieldset(__('Advanced settings'), function (Form $form) {
            $form->text('key', __('feature key'))->help(__('feature key help'));
            $form->keyValue('key_json', __('extra data (JSON)'))->help(__('extra data json help'));
        })->collapsed();



        if (Session::has('show_alert')) {
            $form->html('<script>
             $(document).ready(function () {
                 alert("الرجاء اختيار نوع  الصوره");
             });
         </script>');
        }
        //        $form->file('img3', trans('video'));
        // $form->color('color', trans('color'));
        $form->number('expire', trans('expire(in days)'))->placeholder(trans('0 if permanent'));

        //        $form->number('sort', 'sort');
        // $form->number('num', __('num'));
        if (!$form->isEditing()) {
            if (Admin::user()->can('add_ware_price') || Admin::user()->can('*')) {
                $form->switch('enable', trans('enable'))->states(Common::getSwitchStates())->default('on');
            }
        }
        if ($form->isEditing()) {
            if (Admin::user()->can('edit_ware_price') || Admin::user()->can('*')) {
                $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());
            }
        }
        $form->saving(function (Form $form) {
            $imageType1 = $form->input('image_type1');
            $profileFrameType = $form->input('profile_frame_type');
            $form->model()->image_type = $imageType1 ?? $profileFrameType;

            if (is_null($imageType1) && is_null($profileFrameType)) {

                session()->flash('show_alert', 'Your alert message');
                return redirect()->back();
            }


            (new UserCounterServices)->eventUsers('ware');
        });
        $form->disableReset();


        return $form;
    }



    public function toggleEnable($id, Request $request)
    {
        try {
            $ware = Ware::findOrFail($id);

            if (!$request->has('enable')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Missing enable value.',
                ], 422);
            }


            $enable = $request->input('enable') == '1' ? true : false;

            $ware->enable = $enable;

            $ware->save();
            // dd($ware);
            return response()->json([
                'status' => true,
                'message' => 'تم تحديث الحالة بنجاح.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء الحفظ: ' . $e->getMessage(),
            ], 500);
        }
    }
}
