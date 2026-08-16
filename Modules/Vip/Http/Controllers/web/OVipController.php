<?php

namespace Modules\Vip\Http\Controllers\web;

use App\Models\Ware;
use App\Models\Config;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Modules\Vip\Entities\OVip;
use App\Selectables\Privileges;
use Encore\Admin\Facades\Admin;
use Illuminate\Validation\Rule;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use App\Services\AppFeatureService;
use App\Http\Controllers\Controller;
use Modules\Vip\Services\VipService;
use Modules\Vip\Entities\VipPrivilege;
use Illuminate\Support\Facades\Session;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\HasResourceActions;


class OVipController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'VIPs';
    public $permission_setting = 'ovip-settings';


    public $hiddenColumns = [];
    public function __construct()
    {
        $this->middleware(\Modules\Vip\Http\Middleware\CheckVipFeatureEnabled::class);
    }

    public function vipSettings(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_setting);
        }

        $config = Config::pluck('value', 'name')->toArray();
        $config['enable_vip_auto'] = true;
        return $content->title(trans('VIP Settings'))->view('vip_settings', compact('config'));
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('VIP Levels'))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    public function show($id, Content $content)
    {
        $oVip = OVip::findOrFail($id);
        return parent::show($id, $content->title(__('OVip'))->view('ovip_profile', compact('oVip')));
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
            ->title(trans('vip'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('vip'))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */



    protected function grid()
    {

        $grid = new Grid(new OVip);
        $grid->model()->with('privilegs');
        $grid->id('ID');
        $grid->column('level', __('level'));
        $grid->column('name', __('name'));
        $grid->column('img', __('img'))->display(function ($path) {
            /** @var OVip $this */
            $defaultImage = asset("images/image.png");
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $grid->column('price', __('price'))->display(function ($coin) {
            $icon = asset('images/coin.jpg');
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });
        $grid->column('expire', __('expire'));

        if (Admin::user()->can('browse-' . 'vip-gift') || Admin::user()->can('*')) {
            $grid->column(__('file'))->display(function () {
                $privilegeTypes = optional($this->privilegs)->pluck('en_name', 'type')->sortKeys();
                $type = $privilegeTypes?->keys()->first();
                $url1 = url('admin/ovip-gift/' . $this->id . '?type=' . $type);

                $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" . __('setting') . "</a>";
                return $button1;
            });
        }

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
        $show = new Show(OVip::findOrFail($id));
        $this->extendShow($show);
        return $show;
    }



    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {

        $form = new Form(new OVip);
        $this->disableFormTools($form);

        if (Session::has('show_alert_vip')) {
            $form->html('<script>
             $(document).ready(function () {
                 alert("___");
             });
         </script>');
        }
        $form->display(__('ID'));
        $form->number('level', __('level'))
            ->rules(function () use ($form) {
                return [
                    'required',
                    $form->isCreating()
                        ? Rule::unique('o_vips', 'level')
                        : Rule::unique('o_vips', 'level')->ignore($form->model()->id),
                ];
            });

        $form->text('name', __('name'));

        if (request()->is('*edit*')) {
            $form->html(function (Form $form) {
                $path = $form->model()->img;
                if (!$path) {
                    return '';
                }

                return badgeFilePreview('ovip_badge_' . $form->model()->id, $path);
            }, __('current badge'));
        }

        $form->file('img', __('badge image'))
            ->options(['showPreview' => false])
            ->name(function ($file) {
                return 'svga_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            });

        $form->image('background_img', __('level background'))->uniqueName();

        $form->currency('price', __('price'))->symbol('🪙')->rules('required|numeric|gt:0');

        if (Admin::user()->can('*')) {
            $form->number('expire', __('expire'))->rules('required|numeric|gt:0');
        } else {
            $form->number('expire', __('expire'))->max(30)->rules('required|numeric|gt:0');
        }
        $form->belongsToMany('privilegs', Privileges::class, __('privileges'))->rules('required|array|min:1');

        $form->saving(function (Form $form) {
            app(VipService::class)->handleSaving($form);
        });

        return $form;
    }
}
