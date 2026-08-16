<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use Modules\Vip\Entities\Vip;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Admin\Fields\Image;

use App\Services\AppFeatureService;
use App\Admin\Controllers\MainController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class VipController extends MainController
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

    public $permission_name = 'level';
    public $hiddenColumns = [];

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("vips");
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Level List'))
            ->body($this->grid()));
    }


    /**
     * Replace the default create button so the form opens pre-locked to the
     * grid's own level type instead of defaulting to the sender type.
     */
    protected function typedCreateButton(Grid $grid, string $tab): void
    {
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) use ($tab) {
            $tools->append('<a href="' . admin_url('vips/create?tab=' . $tab) . '" class="btn btn-sm btn-success">
            <i class="fa fa-plus"></i>&nbsp;' . trans('admin.new') . '</a>');
        });
    }

    public function receiverIndex(Content $content)
    {
        return $content
            ->title(trans('level'))
            ->body($this->receiverGrid());
    }
    protected function receiverGrid()
    {
        $grid = new Grid(new Vip());
        $grid->model()->where('type', 1)->orderByDesc('type')->orderBy('exp');
        $grid->quickSearch();
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'))->select(
            [
                1 => __('broadcaster'),
                2 => __('honor'),
                3 => __('cp'),
                4 => __('room'),
                5 => __('charge'),
            ]
        );
        $grid->column('level', __('Level'))->editable();
        $grid->column('exp', __('Exp'))->display(function ($column, Grid\Column $value) {
            $value = $value->getOriginal();
            return number_format($value);
        })->editable();
        //        $grid->column('di', __('Diamonds'));
        //        $grid->column('co', __('Coins'));
        $grid->column('img', __('Image'))->image('', '30');
        $this->extendGrid($grid);
        $grid->disableExport();
        $grid->setResource('vips');

        return $grid;
    }
    public function cpIndex(Content $content)
    {
        return $content
            ->title(trans('CP Levels'))
            ->body($this->cpGrid());
    }

    protected function cpGrid()
    {
        $grid = new Grid(new Vip());
        $grid->model()->where('type', 3)->orderByDesc('type')->orderBy('exp');
        $grid->quickSearch();
        $grid->column('id', __('Id'));
        $grid->column('level', __('Level'))->editable();
        $grid->column('exp', __('Exp'))->display(function ($column, Grid\Column $value) {
            $value = $value->getOriginal();
            return number_format($value);
        })->editable();
        //        $grid->column('di', __('Diamonds'));
        //        $grid->column('co', __('Coins'));
        $grid->column('img', __('Image'))->image('', '30');
        $this->extendGrid($grid);
        $grid->disableExport();
        $grid->setResource('vips');
        $this->typedCreateButton($grid, 'Appcp');

        return $grid;
    }


    public function roomIndex(Content $content)
    {
        return $content
            ->title(trans(' level'))
            ->body($this->roomGrid());
    }

    protected function roomGrid()
    {
        $grid = new Grid(new Vip());
        $grid->model()->where('type', 4)->orderByDesc('type')->orderBy('exp');
        $grid->quickSearch();
        $grid->column('id', __('Id'));
        $grid->column('type', __('Type'))->select(
            [
                1 => __('broadcaster'),
                2 => __('honor'),
                3 => __('cp'),
                4 => __('room'),
                5 => __('charge'),
            ]
        );
        $grid->column('level', __('Level'))->editable();
        $grid->column('exp', __('Exp'))->display(function ($column, Grid\Column $value) {
            $value = $value->getOriginal();
            return number_format($value);
        })->editable();
        //        $grid->column('di', __('Diamonds'));
        //        $grid->column('co', __('Coins'));
        $grid->column('img', __('Image'))->image('', '30');
        $this->extendGrid($grid);
        $grid->disableExport();
        $grid->setResource('vips');

        return $grid;
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
            ->title(trans('level'))
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
            ->title(trans('level'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('level'))
            ->body($this->form()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Vip());

        // Sort by type desc then exp asc
        $grid->model()->orderByDesc('type')->orderBy('exp');

        // Filter model by selected tab. Only the three level products live on
        // this page (owner 2026-08-09): Wealth/Sender (2), Charisma/Receiver (1),
        // Charge (5). CP (3) and Room (4) have their own sections.
        $grid->model()->when(request('tab', 'Appsender'), function ($query, $tab) {
            switch ($tab) {
                case 'Appreceived':
                    $query->where('type', 1);
                    break;
                case 'Appcharge':
                    $query->where('type', 5);
                    break;
                case 'Appsender':
                default:
                    $query->where('type', 2);
                    break;
            }
        });

        // Tabs at top rendered from the Blade view
        $grid->header(function () {
            $tabs = [
                'Appsender' => __('AppSender'),
                'Appreceived' => __('AppReceived'),
                'Appcharge' => __('AppCharge'),
            ];

            // Render the Blade view with tabs data
            return view('admin.tabs', compact('tabs'));
        });

        // Other grid settings
        $grid->quickSearch();

        /* $grid->column('id', __('Id'));

        $grid->column('type', __('Type'))->select([
            1 => __('broadcaster'),
            2 => __('honor'),
            3 => __('cp'),
            4 => __('room'),
            5 => __('charge'),
        ]); */

        $grid->column('level', __('Level'))->editable();

        $grid->column('exp', __('Exp'))->display(function ($column, Grid\Column $value) {
            return number_format($value->getOriginal());
        })->editable();

        $grid->column('img', __('Image'))->display(function ($img) {
            $defaultImage = asset("images/image.png");
            $url = getImagePath($img) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));

            $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',];

            $inner = handleShowImageWithTypes($this->id, $url, 50, 50);

            if (in_array($ext, $imageTypes)) {
                return "<a href='{$url}' target='_blank'><img src='{$url}' style='width:50px'/></a>";
            } else {
                return $inner;
            }
        });

        // Any custom grid extensions
        $this->extendGrid($grid);

        // No export button
        $grid->disableExport();
        $currentTab = request('tab', 'Appsender');
        $grid->disableCreateButton();
        $grid->tools(function (Grid\Tools $tools) use ($currentTab) {
            $tools->append('<a href="' . admin_url('vips/create?tab=' . $currentTab) . '" class="btn btn-sm btn-success">
            <i class="fa fa-plus"></i>&nbsp;' . trans('admin.new') . '</a>');
        });
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
        $show = new Show(Vip::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('type', __('Type'))->number();
        $show->field('level', __('Level'))->number();
        $show->field('exp', __('Exp'))->number();
        //        $show->field('di', __('Diamonds'))->number ();
        //        $show->field('co', __('Coins'))->number ();
        $show->field('img', __('Image'))->image();
        //        $show->field('created_at', __('Created at'));
        //        $show->field('updated_at', __('Updated at'));
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
        $form = new Form(new Vip());
        $this->disableFormTools($form);

        $tabToTypeMap = [
            'Appsender' => 2,   // honor
            'Appreceived' => 1, // broadcaster
            'Appcp' => 3,       // cp
            'Approom' => 4,     // room
            'Appcharge' => 5,   // charge
        ];

        $currentTab = request('tab', 'Appsender');
        $currentType = $tabToTypeMap[$currentTab] ?? 2; // Default to 2 if tab not found

        if ($form->isCreating()) {
            $form->hidden('type')->default($currentType);

            $typeLabels = [
                1 => __('broadcaster'),
                2 => __('honor'),
                3 => __('cp'),
                4 => __('room'),
                5 => __('charge'),
            ];
            $form->display('type_display', __('Type'))->default($typeLabels[$currentType]);
        } else {
            $form->select('type', __('Type'))->options([
                1 => __('broadcaster'),
                2 => __('honor'),
                3 => __('cp'),
                4 => __('room'),
                5 => __('charge'),
            ]);
        }

        //        $form->textarea('name_ar', __('name_ar'));
        //        $form->textarea('name_en', __('name_en'));
        $form->number('level', __('Level'))->required();
        $form->number('exp', __('Exp'))->help(__('sender: 1 coin = 1 exp -- receiver: 1 coin = 1 exp'));
        //        $form->number('di', __('Diamonds'));
        //        $form->number('co', __('Coins'));
        $form->file('img', __('Image'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        })->removable()->rules('required');
        

        $form->footer(function ($footer) {
            $footer->disableReset();        // Disables the "Reset" button
            $footer->disableViewCheck();    // Disables the "View" checkbox
            $footer->disableEditingCheck(); // Disables the "Continue editing" checkbox
            $footer->disableCreatingCheck(); // Disables the "Continue creating" checkbox
        });

        return $form;
    }
}
