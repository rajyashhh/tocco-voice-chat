<?php

namespace Modules\Vip\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Illuminate\Support\Str;
use Modules\Vip\Entities\VipPrivilege;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Reals\Http\Services\FfmpegService;
use Encore\Admin\Controllers\HasResourceActions;
use Illuminate\Support\Facades\Log;
use Modules\Public\Http\Services\UserCounterServices;
use Modules\Vip\Services\WareSaveService;


class OvipGiftTapController extends MainController
{

    use HasResourceActions;
    public $permission_name = 'vip-gift';

    public function index(Content $content)
    {
        $url = url('/admin/ovip');
        $back = __('back');

        $buttonHTML = <<<HTML
        <a href="{$url}" class="btn btn-sm btn-success" style="margin-bottom: 20px;">
            <i class="fa fa-arrow-left"></i> {$back}
        </a>
        HTML;

        $ovip = null;
        if (request('ovip_id')) {
            $ovip = OVip::find(request('ovip_id'));
        } elseif (request('level')) {
            $ovip = OVip::where('level', request('level'))->first();
        }

        return parent::index($content
            ->title(trans('Privileges'))
            ->row($buttonHTML)
            ->row(function (Row $row) use ($ovip) {
                $row->column(12, $this->tabsComponent($ovip?->privilegs, $ovip?->id, $ovip?->privilegs->first()?->type));
            })
            ->row(function (Row $row) use ($ovip) {
                $type = request('type');

                $privilegeTypesInfo = [
                    13 => __('hide user country'),
                    17 => __('user ender room anonymous'),
                    14 => __('user can send vip gift'),
                    19 => __('hide visitors to client pages'),
                    16 => __('hide room'),
                    20 => __('last login'),
                    9  => __('user can not kick out from room'),
                    22 => __('user can upload Gif image'),
                    15 => __('can not ban this user'),
                ];

                if (isset($privilegeTypesInfo[$type])) {
                    $vipPrivilege = VipPrivilege::where('type', $type)->first();
                    $image = getImagePath($vipPrivilege->img1 ?? '');
                    $text = $privilegeTypesInfo[$type];

                    $row->column(12, '
                        <div style="display: flex; align-items: center; justify-content: center; gap: 20px;">
                            <img src="' . $image . '" alt="VIP Image" style="max-height: 60px;">
                            <div style="font-size: 48px; font-weight: bold;">' . $text . '</div>
                        </div>
                    ');
                } else {
                    $row->column(12, $this->gridDynamic($ovip?->level, $ovip?->privilegs->first()?->type));
                }
            }));
    }
    public function create(Content $content)
    {
        return parent::create($content
            ->header(trans('admin.create'))
            ->description(trans('admin.description'))
            ->body($this->form()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('gift')));
    }

    public function edit($id, Content $content)
    {

        return parent::edit($id, $content
            ->title(trans('gift'))
            ->body($this->form()->edit($id)));
    }

    protected function gridDynamic($level, $firstType)
    {

        $type = request()->get('type', $firstType);
        $grid = new Grid(new Ware);

        $baseQuery = Ware::query()
            ->where('level', $level)
            ->where('get_type', 1)
            ->where('type', $type)
            ->where('is_active_for_vip', 1);

        $grid->model()->setModel($baseQuery->getModel());
        $grid->model()->setQuery($baseQuery->toBase());

        $count = (clone $baseQuery)->count();

        $grid->id(__('ID'));
        if ($type == 18 ||  $type == 21) {
            $grid->column('color', __('Color'))->display(function ($color) {
                return "<div style='width: 30px; height: 30px; background-color: {$color}; border: 1px solid #ccc; border-radius: 4px;'></div>";
            });
        } else {
            $grid->column('name', __('name'))->display(function ($name) {

                return app()->getLocale() == 'ar' ? $name : $this->name_en;
            });

            $grid->column('show_img', __('show_img'))->display(function ($path) {
                /** @var Ware $this */
                $defaultImage = asset("images/image.png");
                $url = getImagePath($path) ?? $defaultImage;
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                return handleShowImageWithTypes($this->id, $url, 101, 50);
            });
            $grid->column('img2', __('show_img'))->display(function ($path) {
                /** @var Ware $this */
                $defaultImage = asset("images/image.png");
                $url = getImagePath($path) ?? $defaultImage;
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                return handleShowImageWithTypes($this->id, $url, 101, 50);
            });


            $grid->title(__('title'))->display(function ($name) {

                return app()->getLocale() == 'ar' ? $name : $this->title_en;
            });;
        }

        if (Admin::user()->can('delete-' . $this->permission_name) || Admin::user()->can('*') || Admin::user()->can('edit-' . $this->permission_name)) {
            $permission = $this->permission_name;
            $grid->column('actions', __('Actions'))->display(function () use ($type, $permission) {

                $id = $this->id;

                $editUrl = admin_url("ware-gifts/{$id}/edit");
                $deleteUrl = admin_url("ware-gifts/{$id}");
                $csrf = csrf_token();

                $editText = __('admin.edit');
                $deleteText = __('admin.delete');
                $confirmText = __('Are you sure?');

                $editBtn = '';
                $deleteBtn = '';

                if (\Admin::user()->can('edit-' . $permission) || \Admin::user()->can('*')) {
                    $editBtn = <<<HTML
            <a href="{$editUrl}" class="btn btn-xs btn-primary" style="margin-right: 5px">
                <i class="fa fa-edit"></i> {$editText}
            </a>
        HTML;
                }

                if (\Admin::user()->can('delete-' . $permission) || \Admin::user()->can('*')) {
                    $deleteBtn = <<<HTML
            <form action="{$deleteUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('{$confirmText}')">
                <input type="hidden" name="_token" value="{$csrf}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-xs btn-danger">
                    <i class="fa fa-trash"></i> {$deleteText}
                </button>
            </form>
        HTML;
                }

                return $editBtn . $deleteBtn;
            })->style('min-width:120px')->setAttributes(['style' => 'text-align:center']);
        }

        $grid->disableActions();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->disableCreateButton();
        $this->extendGrid($grid);
        $grid->disableExport();
        if ($firstType && (Admin::user()->can('create-' . $this->permission_name) || Admin::user()->can('*'))) {

            if ($count < 1) {
                $grid->tools(function (Grid\Tools $tools) use ($level, $type,) {
                    $level = $level ?? request('level');
                    $url =    url('admin/ware-gift/' . $level . '/' . $type);
                    $add = __('add');

                    $customButtonHTML = <<<HTML

                <a href="{$url}" class="btn btn-sm btn-success" style="margin-right: 10px;">
                        <i class="fa fa-plus"></i> {$add}
                    </a>

                HTML;
                    $tools->append($customButtonHTML);
                });
            }
        }

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");

        Admin::style("
            .box-body{
                overflow: auto !important;
                scrollbar-width: none;      
            }

            .box-body::-webkit-scrollbar{
                display: none;              
            }
        ");
        return $grid;
    }
    public function destroy($id)
    {
        $ware = Ware::where('id', $id)->first();
        $ovip = Ovip::where('level', $ware->level)->first();
        $type = $ware->type;
        $ware->delete();
        $url = url('admin/ovip-gift/' . $ovip->id) . '?type=' . $type;
        return redirect()->to($url);
    }


    protected function form()
    {
        $form = new Form(new Ware());
        $this->disableFormTools($form);
        $isEditing = $form->isEditing();
        if (!$isEditing) {
            $form->hidden('level')->value(request('level'));
            $form->hidden('type')->value(request('type'));
            $form->hidden('is_active_for_vip')->value(1);
            $form->hidden('get_type')->value(1);
            $form->hidden('enable')->value(1);
        }


        $id = request()->route('ware_gift');
        $ware = Ware::find($id);

        $isType18or21 = in_array(request('type'), [18, 21]);


        if (!$isType18or21 && ($isEditing && $ware && !in_array($ware->type, [18, 21]))) {

            // dd($isType18or21, $isEditing, $ware?->type);
            $form->display('ID');
            $form->text('name', trans('name'));
            $form->text('name_en', trans('Name en'));
            $form->text('title', trans('title'));
            $form->text('title_en', trans('Title en'));

            $form->image('show_img', trans('img'))->name(fn($file) => now()->timestamp . rand(0, 999) . '.' . $file->guessExtension())
                ->default('1.png');

            $form->file('img2', trans('show'))->name(fn($file) => 'svga_' . Str::random(6) . '.' . $file->getClientOriginalExtension());

            $form->keyValue('key_json', 'key_json');
            $form->text('key', trans('key'));

            if ($isEditing) {
                $form->select('image_type1', __('image_type'))->options([
                    'svga' => __('svga'),
                    'alpha' => __('alpha'),
                    'mp4' => __('mp4'),
                    'vap' => __('vap'),
                    'png' => __('image:(jpg, jpeg, png,gif, bmp, tiff, svg, webp, mov, avi, wmv, flv, mkv, webm)'),
                ])->attribute(['id' => 'image_type1']);
            }

            if (request('type') == 5 || ($isEditing && $ware && $ware->type == 5)) {
                $form->html('<h1>' . __('padding') . '</h1>');
                $form->decimal('top', __('top'))->default(20);
                $form->decimal('left', __('left'))->default(15);
                $form->decimal('right', __('right'))->default(15);
                $form->decimal('bottom', __('bottom'))->default(15);
            }
        }



        if ($isType18or21 || ($isEditing && $ware && in_array($ware->type, [18, 21]))) {


            $form->html('
                    <div class="form-group">
                        <label for="color">' . trans("color") . ':</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="color" id="color" name="color"
                                value="' . ($ware->color ?? '#ccc') . '"
                                style="width: 50px; height: 45px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer;">
                            <input type="text" id="color_text" class="form-control"
                                value="' . ($ware->color ?? '#ccc') . '"
                                placeholder="ادخل لون" style="flex: 1;" readonly>
                        </div>
                    </div>

                    <script>
                        const colorInput = document.getElementById("color");
                        const colorText = document.getElementById("color_text");
                        colorInput.addEventListener("input", function() {
                            colorText.value = this.value;
                        });
                    </script>
                ');
        }


        $form->saving(function (Form $form) use ($isEditing, $isType18or21) {
            if (request('color')) $form->model()->color = request('color');
            if (isset($form->key_json) && is_array($form->key_json)) {
                // Normalize posted structure to ['keys' => [...], 'values' => [...]]
                // Handle both formats: the KeyValue widget posts ['keys'=>[], 'values'=>[]]
                // but $form->key_json may also be an associative map like ['k'=>'v'] when coming from model.
                if (array_key_exists('keys', $form->key_json) && array_key_exists('values', $form->key_json)) {
                    $keys = $form->key_json['keys'] ?? [];
                    $values = $form->key_json['values'] ?? [];

                    $newKeys = [];
                    $newValues = [];
                    foreach ($keys as $i => $k) {
                        $v = $values[$i] ?? null;
                        if ($k !== null && $k !== '' || $v !== null && $v !== '') {
                            $newKeys[] = $k;
                            $newValues[] = $v;
                        }
                    }

                    if (empty($newKeys)) {
                        $form->key_json = ['keys' => [], 'values' => []];
                    } else {
                        $form->key_json = ['keys' => $newKeys, 'values' => $newValues];
                    }
                } else {
                    // Convert associative map to keys/values arrays
                    $newKeys = [];
                    $newValues = [];
                    foreach ($form->key_json as $k => $v) {
                        if ($k !== null && $k !== '' || $v !== null && $v !== '') {
                            $newKeys[] = $k;
                            $newValues[] = $v;
                        }
                    }

                    if (empty($newKeys)) {
                        $form->key_json = ['keys' => [], 'values' => []];
                    } else {
                        $form->key_json = ['keys' => $newKeys, 'values' => $newValues];
                    }
                }
            } else {
                // Ensure the field has the expected structure for KeyValue
                $form->key_json = ['keys' => [], 'values' => []];
            }


            $id = $form->model()->id;
            $level = $form->model()->level ?? request('level');
            $type = $form->model()->type ?? request('type');
            $exists = Ware::where('level', $level)
                ->where('type', $type)
                ->where('get_type', 1)
                ->when($id, fn($q) => $q->where('id', '!=', $id))
                ->exists();

            $query = Ware::where('level', $level)
                ->where('type', $type)
                ->where('get_type', 1);
            // Exclude current record when editing (use !== null for proper null check)
            if ($id !== null) {
                $query->where('id', '!=', $id);
            }

            if (!in_array($type, [18, 21])) {
                $imageType1 = $form->input('image_type1');
                $profileFrameType = $form->input('profile_frame_type') ?? $form->input('detected_profile_frame_type');
                $final = $profileFrameType ?? $imageType1;

                if ($isEditing && is_null($final)) {
                    session()->flash('show_alert', 'الرجاء اختيار نوع الصوره');
                    return redirect()->back();
                }

                $form->model()->image_type = $final;
            }

            (new UserCounterServices())->eventUsers('ware');
        });

        $form->saved(function (Form $form) {
            $ovip = Ovip::where('level', $form->model()->level)->first();
            $url = url('admin/ovip-gift/' . $ovip->id) . '?type=' . $form->model()->type;
            return redirect()->to($url);
        });

        return $form;
    }




    private function tabsComponent($privileges, $level, $type)
    {
        $content = new Row();

        $privilegeTypes = app()->getLocale() === 'en'
            ? $privileges?->pluck('en_name', 'type')->sortKeys()
            : $privileges?->pluck('name', 'type')->sortKeys();

        $hasTypes = $privilegeTypes->isNotEmpty();

        $currentType = request()->get('type', $privilegeTypes?->keys()->first());

        $alert = !$type;

        if (!$type && $hasTypes && !request()->has('type')) {
            $firstType = $privilegeTypes->keys()->first();

            \Encore\Admin\Admin::script(<<<'SCRIPT'
                const url = new URL(window.location.href);
                url.searchParams.set('type', '{$firstType}');
                window.location.href = url.toString(); // Reload with type
            SCRIPT);
        }

        $box = new Box(
            content: view('admin.grid.Form.privilegeTabs', [
                'types' => $privilegeTypes,
                'currentType' => $currentType,
                'alert' => $alert,
                'level' => $level,
            ])
        );

        $content->column(12, $box);

        return $content;
    }
}
