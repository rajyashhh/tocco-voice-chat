<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Admin\Extensions\Form\Field\CustomFile;
use App\Admin\Services\FileService;
use App\Helpers\LogHelper;
use Illuminate\Support\Facades\Log;
use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Helpers\Common;
use Illuminate\Support\Str;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Admin\Controllers\MainController;
use Illuminate\Validation\ValidationException;
use Modules\Reals\Http\Services\FfmpegService;
use Encore\Admin\Controllers\HasResourceActions;


class WareTabController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'store';
    public function index(Content $content)
    {
        session(['last_ware_type' => request()->get('type', 1)]);
        return parent::index($content
            ->title(trans('Products'))
            ->row(function (Row $row) {
                $row->column(12, $this->tabsComponent());
            })
            ->row(function (Row $row) {
                $row->column(12, $this->grid());
            }));
    }

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
        // Get the warehouse first to ensure it exists
        // $ware = Ware::findOrFail($id);

        // // Get type from request or fall back to warehouse's type
        // $currentType = request('type', $ware->type);

        return parent::edit($id, $content
            ->title(trans('wares'))
            // ->row(function (Row $row) use ($id, $currentType) {
            //     $row->column(12, $this->tabsComponentEdit($id, $currentType));
            // })
            ->row(function (Row $row) use ($id) {
                $row->column(12, $this->form($id)->edit($id));
            }));
    }

    public function create(Content $content)
    {
        return  parent::create($content
            ->title(trans('wares'))
            ->row(function (Row $row) {
                $row->column(12, $this->tabsComponentCreate());
            })
            ->row(function (Row $row) {
                $row->column(12, $this->form());
            }));
        //  ->body($this->form());
    }

    protected function grid()
    {
        $type = request()->get('type', 4);
        $grid = new Grid(new Ware());
        //  $types = [6, 4, 5];
        // ✅ Performance: Select only needed columns instead of SELECT *
        $grid->model()
            ->select(['id', 'name', 'price', 'enable', 'show_img', 'img2', 'get_type', 'title', 'level', 'color', 'expire', 'is_active_for_vip', 'sort', 'type'])
            ->where('type', $type)
            ->whereNot('get_type', 1);


        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->equal('get_type', __('get_type'))->select([
                4 => trans('purchase'),
                6 => trans('limited time purchase'),
            ]);
        });

        $grid->id(__('ID'));
        $grid->column('name', __('name'))->editable();
        if (Admin::user()->can('edit_ware_price') || Admin::user()->can('*')) {
            $grid->column('price', __('price'))->editable();
            $grid->column('enable', __('enable'))->switch(Common::getSwitchStates());
        } else {
            $grid->column('price', __('price'));
        }

        $grid->column('show_img', __('show_img'))->display(function ($path) {
            /** @var Ware $this */
            $defaultImage = asset("images/image.png");

            // ✅ Performance: Use simple null/empty check instead of HTTP request (isImageExists)
            // isImageExists() was making a real HTTP call (get_headers) per row = very slow
            $url = !empty($path) ? (getImagePath($path) ?? $defaultImage) : $defaultImage;
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $grid->column('img2', __('show_img'))->display(function ($path) {
            /** @var Ware $this */
            $defaultImage = asset("images/image.png");

            // ✅ Performance: Use simple null/empty check instead of HTTP request (isImageExists)
            $url = !empty($path) ? (getImagePath($path) ?? $defaultImage) : $defaultImage;
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $grid->column('get_type', __('get_type'))->select(
            [
                4 => trans('purchase'),
                6 => trans('limited time purchase'),
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
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) use ($type) {
            $url =  url('/admin/ware-managements/create/' . $type); // Use Laravel route helper
            $add = __('add');

            $customButtonHTML = <<<HTML
                <a href="{$url}" class="btn btn-sm btn-success" style="margi    n-right: 10px;">
                    <i class="fa fa-plus"></i> {$add}
                </a>
            HTML;

            $tools->append($customButtonHTML);
        });
        return $grid;
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

    private function tabsComponentCreate()
    {
        $content = new Row();

        // Define your type mapping
        $typeMap = SELECTED_USED_WARE;

        // $types = Ware::whereIn('type', array_keys($typeMap))->distinct()->pluck('type')->sort()->mapWithKeys(function ($type) use ($typeMap) {
        //     return [$type => $typeMap[$type] ?? "Type $type"];
        // });

        $types = collect($typeMap);


        $currentType = request()->get('type', $types->keys()->first());

        $box = new Box(content: view('admin.grid.Form.wareCreate', [
            'types' => $types,
            'currentType' => $currentType
        ]));

        $content->column(12, $box);

        return $content;
    }

    public function update($id)
    {
        $request = request();

        if ($request->ajax() && $request->has('_editable')) {
            $field = $request->input('name');
            $value = $request->input('value');

            $allowedFields = ['name', 'price'];

            if (in_array($field, $allowedFields)) {
                $model = Ware::findOrFail($id);
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

        $toggleFields = ['enable', 'is_active_for_vip'];

        $editableField = collect($request->except(['_token', '_method', '_edit_inline']))->keys()->first();

        if ($request->ajax() && $request->has('_edit_inline') && in_array($editableField, $toggleFields)) {
            $field = array_key_first($request->all());

            if (in_array($field, $toggleFields)) {
                $model = Ware::findOrFail($id);
                $model->$field = $request->input($field);
                $model->save();

                return response()->json([
                    'status' => true,
                    'message' => __('Updated successfully')
                ]);
            }
        }

        return parent::update($id);
    }



   protected function form($id = null)
    {
        $form = new Form(new Ware());
        $this->disableFormTools($form);

        $form->display('ID');

        $ware = Ware::find($id);

        $form->select('get_type', trans('get_type'))->options(
            translate(GET_TYPE_WARE_TYPES)
        )->default(6)->when('6', function (Form $form) {
            $form->number('expire', trans('expire(in days)'))->placeholder(trans('0 if permanent'));
        });

        if (\Str::contains(request()->fullUrl(), 'edit')) {
            $wareType = Ware::find($id)->type;
            $form->hidden('type', __('type'))->value($wareType)->attribute(['id' => 'type']);
        } else {
            if (request('type')) {
                $form->hidden('type', __('type'))->value(request('type'))->attribute(['id' => 'type']);
            }
        }

        if (!$form->isEditing()) {
            if (request('type')) {
                $form->hidden('type', __('type'))->value(request('type'))->attribute(['id' => 'type']);
            }
        }

        $form->text('name', trans('name'));
        $form->text('name_en', trans('Name en'));
        $form->text('title', trans('title'));
        $form->text('title_en', trans('Title en'));
        $form->currency('price', __('price'))->symbol('🪙');
        $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());

        $form->select('level', __('buy with vip'))->options(function ($value) {
            $ops2 = [];
            foreach (OVip::orderBy('level')->get() as $level) {
                $ops2[$level->level] = $level->level;
            }
            return $ops2;
        });

        // تحسين حقل show_img
        $form->image('show_img', trans('img'))
            ->name(function ($file) {
                // الحصول على الامتداد الحقيقي مع fallback
                $extension = $file->getClientOriginalExtension();
                if (empty($extension)) {
                    $extension = $file->guessExtension();
                }
                return 'img_' . now()->timestamp . '_' . rand(100, 999) . '.' . $extension;
            })->default('1.png');
        $form->switch('half_image_profile', trans('half image'))->states(Common::getSwitchStates());

       /* $form->display('img2', 'Preview')->with(function ($value) {
            if (!$value) return "<div id='preview-display-img2'></div>";

            $url = \Storage::disk(config('admin.upload.disk'))->url($value);
            $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
            $uniqueId = 'file_' . uniqid();

            if (!in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
                return "<div id='preview-display-img2'>" .
                    handleShowImageWithTypes($uniqueId, $url, null, 100, 10) .
                    "</div>";
            }

            return "<div id='preview-display-img2'>
                <img src='{$url}' style='max-height:150px' class='img img-thumbnail' />
            </div>";
        });*/

        // تحسين حقل img2 بشكل كامل
        $form->file('img2', trans('svg'))
            ->name(function ($file) {
                // الحصول على الامتداد الحقيقي مع fallback
                $extension = $file->getClientOriginalExtension();
                if (empty($extension)) {
                    $extension = $file->guessExtension();
                }

                // تطبيع الامتدادات
                $extension = strtolower($extension);
                if ($extension === 'svg') {
                    return 'svga_' . Str::random(8) . '.svg';
                }

                return 'animation_' . Str::random(8) . '.' . $extension;
            });

        $form->select('image_type1', __('image_type'))->options([
            'svga' => __('svga'),
            'alpha' => __('alpha'),
            'mp4' => __('mp4'),
            'vap' => __('vap'),
            'png' => __('image:(jpg, jpeg, png,gif, bmp, tiff, svg, webp, mov, avi, wmv, flv, mkv, webm)'),
        ])->attribute(['id' => 'image_type1']);

        $script = <<<SCRIPT
            $(document).ready(function() {
                function toggleWinProbability() {
                    var type = $('#type').val();
                    if(type == '28') {
                        $('#profile_frame').closest('.form-group').show();
                        $('#image_type1').closest('.form-group').hide();
                    } else {
                        $('#profile_frame').closest('.form-group').hide();
                        $('#image_type1').closest('.form-group').show();
                    }
                }
                toggleWinProbability();
                $('#type').change(function() {
                    toggleWinProbability();
                });
            });
        SCRIPT;
        Admin::script($script);

        if ($form->isEditing()) {
            if (Session::has('show_alert')) {
                $form->html('<script>
                $(document).ready(function () {
                    alert("الرجاء اختيار نوع  الصوره");
                });
            </script>');
            }
        }

        if (request('type') == 18) $form->color('color', trans('color'));
        if ((request('type') && request('type') == 5) || ($form->isEditing() && $ware && ($ware->type == 5))) {
            $form->html('<h1>' . __('padding') . '</h1>');
            $form->decimal('top', __('top'))->default(20);
            $form->decimal('left', __('left'))->default(15);
            $form->decimal('right', __('right'))->default(15);
            $form->decimal('bottom', __('bottom'))->default(15);
        }

        if (request('type') != 18) {
            $form->saving(function (Form $form) {
                $hasShowImg = $form->show_img || $form->model()->show_img;
                $img2 = $form->img2;
                $wareId = $form->model()->id;

       

                $hasImg2 = $img2 || $form->model()->img2;

                if (!$hasShowImg && !$hasImg2) {
                    $error = new MessageBag([
                        'title'   => 'Error',
                        'message' => 'Please upload at least one image',
                    ]);
                    return back()->with(compact('error'));
                }

                // معالجة show_img
                if ($form->show_img instanceof UploadedFile) {
                    $allowedExtensions = ['svga', 'mp4', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg', 'webp', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'webm'];

                    // الحصول على الامتداد الحقيقي
                    $originalExt = strtolower($form->show_img->getClientOriginalExtension());
                    $guessedExt = strtolower($form->show_img->guessExtension());

                    // إعطاء الأولوية للامتداد الأصلي
                    $ext = !empty($originalExt) ? $originalExt : $guessedExt;

               

                    if (!in_array($ext, $allowedExtensions)) {
                        throw ValidationException::withMessages([
                            'show_img' => ['Invalid file type. Allowed extensions are: ' . implode(', ', $allowedExtensions)],
                        ]);
                    }

                    $form->image_type1 = $ext;
                }

                // معالجة img2 - الحل الرئيسي للمشكلة
                if ($img2 instanceof UploadedFile) {
                    /** @var FileService $fileService*/
                    $fileService = app( FileService::class);
                    $ext = $fileService->getExtension($img2, $wareId, getFromService: true);

                    $form->input('detected_profile_frame_type', $ext);
                    $form->profile_frame_type = $ext;

                    // الحصول على الامتداد الحقيقي من الاسم الأصلي
                    $originalExt = strtolower($img2->getClientOriginalExtension());
                    $guessedExt = strtolower($img2->guessExtension());

                    // إعطاء الأولوية للامتداد الأصلي
                    $ext = !empty($originalExt) ? $originalExt : $guessedExt;

                    // إذا كان الملف SVG، تأكد من أنه يحفظ كـ SVG
                    if ($img2->getClientMimeType() === 'image/svg+xml' || $originalExt === 'svg') {
                        $ext = 'svg';
                    }


                    $form->input('detected_profile_frame_type', $ext);
                    $form->profile_frame_type = $ext;

                    // تجاوز FileService إذا كان يسبب المشكلة
                    // /** @var FileService $fileService*/
                    // $fileService = app(FileService::class);
                    // $extFromService = $fileService->getExtension($img2, $wareId, getFromService: true);
                }
            });
        }

        Admin::css('.form-group .control-label, .form-horizontal .control-label { width: 13% !important; }');

        $form->saving(function (Form $form) {
            $isEditing = $form->isEditing();
            if (request('type') != 18 && request('type') != 21) {
                $imageType1 = $form->input('image_type1');
                $profileFrameType = $form->input('profile_frame_type') ?? $form->input('detected_profile_frame_type');

                $image = $profileFrameType ?? $imageType1;

                if ($isEditing && is_null($image)) {
                    session()->flash('show_alert', 'الرجاء اختيار نوع الصوره');
                    return redirect()->back();
                }

                $form->model()->image_type = $image;
            }

            if (request('get_type') == 4) {
                $form->model()->expire = 0;
            }
        
        });

        $form->saved(function (Form $form) {
            $model = $form->model();
            /*if ($form->img2 instanceof UploadedFile) {
                $originalName = $form->img2->getClientOriginalName();
                info('form image', [$form->img2]);
                info('originalName', [$originalName]);
                $model->update(['img2' => $originalName]);
            }*/

            $type = $form->model()->type;
            $url = url('admin/ware-management') . '?type=' . $type;
            return redirect()->to($url);
        });

        return $form;
    }



    private function tabsComponentEdit($id, $currentType)
    {
        $content = new Row();

        // Define your type mapping
        $typeMap = TYPE_WARE;

        $types = Ware::whereIn('type', array_keys($typeMap))
            ->distinct()
            ->pluck('type')
            ->sort()
            ->mapWithKeys(function ($type) use ($typeMap) {
                return [$type => $typeMap[$type] ?? "Type $type"];
            });

        $box = new Box(content: view('admin.grid.Form.wareEdit', [
            'types' => $types,
            'currentType' => $currentType,
            'wareId' => $id
        ]));

        $content->column(12, $box);

        return $content;
    }
}
