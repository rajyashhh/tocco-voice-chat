<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\Emoji;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\EmojiCategory;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\App;
use App\Admin\Actions\Grid\MoveGroupEmoji;
use App\Admin\Actions\MoveEmojiCategoryAction;
use Encore\Admin\Controllers\HasResourceActions;

class EmojiController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'emoji';
    protected $filterId;

    public function __construct()
    {
        $this->filterId = request('filter');
    }
    public function index(Content $content)
    {
        return $content
            ->title(trans('Emojis'))
            ->body($this->grid());
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
            ->title(trans('Emojis'))
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
            ->title(trans('Emojis'))
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
            ->title(trans('Emojis'))
            ->body($this->form()));
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Emoji);

        // Get the current filter from request or default to first category
        $filterType = request()->get('filter', 'all');
        
        $category  = null;

        if ($filterType !== 'all' && $filterType) {
            $category = EmojiCategory::find($filterType);
        }

        // Header tabs (reuse cached categories from MoveEmojiCategoryAction)
        $grid->header(function () use ($filterType) {
            try {
                $tabs = ['all' => __('All')];
                $categoryOptions = MoveEmojiCategoryAction::getCategoryOptions();
                foreach ($categoryOptions as $id => $title) {
                    $tabs[$id] = $title;
                }

                $html = '<div class="nav-tabs-custom"><ul class="nav nav-tabs">';
                foreach ($tabs as $key => $label) {
                    $active = ($filterType == $key || ($filterType === 'all' && $key === 'all')) ? 'active' : '';
                    $url = request()->fullUrlWithQuery(['filter' => $key]);
                    $html .= "<li class='{$active}'><a href='{$url}'>{$label}</a></li>";
                }
                $html .= '</ul></div>';

                return $html;
            } catch (\Exception $e) {
                //\Log::error('EmojiController header error: ' . $e->getMessage());
                return ''; // Return empty string on error
            }
        });

        // Apply filter to the grid
        if ($filterType !== 'all' && $filterType) {
            $grid->model()->where('emoji_category_id', $filterType);
        }

        // Columns
        $grid->id(__('ID'));
        $grid->name(__('name'));
        $grid->column('name_en', __('name_en'));
         $grid->column('emoji', trans('emoji'))->display(function ($path) {
            $url = getImagePath($path);
             return handleShowImageWithTypes($this->id, $url, 50, 50);
         });
        $grid->column('enable', trans('enable'))->switch(Common::getSwitchStates());

        // $this->extendGrid($grid);

        $grid->disableExport();
        $grid->disableCreateButton();

        if ($category && $filterType != 'all') {
            $grid->tools(function (Grid\Tools $tools) use ($filterType) {
                $url =  url('/admin/emojis/create/' . $filterType); // Use Laravel route helper
                $add = __('add');

                $customButtonHTML = <<<HTML
                <a href="{$url}" class="btn btn-sm btn-success" style="margi    n-right: 10px;">
                    <i class="fa fa-plus"></i> {$add}
                </a>
            HTML;

                $tools->append($customButtonHTML);
            });
        }

         Admin::style("
             .rtl .column-emoji .rtlSvga{
                 direction: ltr;
             }
         ");
         // Optional: remove table-responsive for large screens
            Admin::script("
                if (window.innerWidth >= 1024) {
                    $('.table-responsive').removeClass('table-responsive');
            }
         ");
        
        $permission    = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            try {
                $model = $actions->row;

                if ((Admin::user()->can('move-switch-' . $permission) || Admin::user()->can('*'))) {
                    $actions->add(new MoveEmojiCategoryAction());
                }
            } catch (\Exception $e) {
                \Log::error('EmojiController actions error: ' . $e->getMessage());
            }
        });

   
        $grid->batchActions(function ($batch) {
            try {
                $batch->disableDelete();
                if (class_exists(MoveGroupEmoji::class)) {
                    $batch->add(new MoveGroupEmoji());
                }
            } catch (\Exception $e) {
                \Log::error('EmojiController batchActions error: ' . $e->getMessage());
            }
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
        $show = new Show(Emoji::findOrFail($id));

        $show->id('ID');
        $show->pid('pid');
        $show->name('name');
        $show->emoji('emoji');
        $show->t_length('t_length');
        $show->enable('enable');
        $show->sort('sort');
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
        $form = new Form(new Emoji);
        $this->disableFormTools($form);
       

        if ($form->isEditing()) {
            $form->display('id', __('ID'));
        }
        if (!$form->isEditing()) {
            $form->hidden('emoji_category_id', __('type'))->default(request('filter'));
        }
        $form->select('pid', __('pid'))->options(function () {
            $ops = [0 => 'root'];
            $ps = Emoji::query()->where('enable', 1)->where('pid', 0)->where('id', '!=', $this->id)->get();
            foreach ($ps as $p) {
                $ops[$p->id] = $p->name;
            }
            return $ops;
        });
        $form->text('name', __('name'));
        $form->text('name_en', __('name_en'));
        $form->file('emoji', __('emoji'));
        $form->select('image_type', __('image_type'))->options(
            [
                'svga' => __('svga'),
                'alpha' => __('alpha'),
                'mp4' => __('mp4'),
                'vap' => __('vap'),
                 'png' => __('image:(jpg, jpeg, png,gif, bmp, tiff, svg, webp, mov, avi, wmv, flv, mkv, webm)'),
            ]
        )->required();
        $form->number('t_length', __('t_length'));
        $form->switch('enable', __('enable'))->states(Common::getSwitchStates());
       $form->number('sort', __('sort'));

        $form->saved(function (Form $form) {
            $model = $form->model();
            $type = $form->model()->emoji_category_id;
            $url = url('admin/emojis') . '?filter=' . $type;
            return redirect()->to($url);
        });

        return $form;
    }


    public function gitImage()
    {
        $gifts = Emoji::whereNotNull('emoji')->get();
        foreach ($gifts as $gift) {
            $ImageType =     pathinfo($gift->emoji, PATHINFO_EXTENSION);
            $gift->image_type = $ImageType == 'alpha' ? 'mp4' : $ImageType;
            $gift->save();
        }
        return $gifts;
    }
}
