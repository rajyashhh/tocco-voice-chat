<?php

namespace Modules\HostLevel\Http\Controllers\web;


use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Column;
use App\Admin\Controllers\MainController;
use Illuminate\Support\MessageBag;
use Modules\HostLevel\Entities\HostLevel;

class HostLevelController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Host level';
    public $permission_name = 'host-level';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans($this->title))
            ->row(function (Row $row) {



                $row->column(12, function (Column $column) {
                    $column->row($this->grid2());
                    $column->row($this->grid());
                });
            }));
        // ->body($this->grid()));
    }

    protected function grid2()
    {
        $form = new Box();
        $form->view('hostlevel::generalRole');

        return $form;
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
            ->title(trans($this->title))
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
            ->title(trans($this->title))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans($this->title))
            ->body($this->form()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HostLevel());

        $grid->model()->orderBy('level', 'asc');
        $grid->column('id', __('Id'));
        $grid->column('img', __('Img'))->display(function ($path) {
            /** @var Ware $this */
            $defaultImage = asset("images/image.png");
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
        $grid->column('level', __('Level'));

        $grid->column('diamonds', __('diamonds'))->display(function ($usd) {

            $image = asset('images/diamond.jpg'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$usd}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });

        if (Admin::user()->can('browse-' . 'host_level_reward') || Admin::user()->can('*')) {
            $grid->column(__('procedures'))->display(function () {

                if (request()->filled('_export_')) {
                    return '';
                }
                $url1 = url('admin/host-level-reward/' . $this->id);
                $gifts = __('gifts');
                $button1 = "<a href='{$url1}' class='btn btn-sm btn-info'>" .   $gifts . "</a>";

                return $button1;
            });
        }
        $this->extendGrid($grid);

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
        $show = new Show(HostLevel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('img', __('Img'));
        $show->field('level', __('Level'));
        $show->field('diamonds', __('diamonds'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new HostLevel());
        $form->text('name', __('name'));
        $form->image('img', __('Img'))->required();
        $form->number('level', __('Level'))->rules('required|unique:host_levels,level,{{id}}');
        $form->number('diamonds', __('diamonds'))->required();


        $form->saving(function (Form $form) {

            $level = $form->level;
            $diamonds = $form->diamonds;


            // Get previous level (< this one)
            $previous = HostLevel::where('level', '<', $level)
                ->when($form->model()->id, function ($q) use ($form) {
                    return $q->where('id', '!=', $form->model()->id);
                })
                ->orderBy('level', 'desc')
                ->first();
            if ($previous && ($diamonds <= $previous->diamonds)) {
                $error = new MessageBag([
                    'title'   => 'Forbidden',
                    'message' => __("Diamonds must be greater than previous level (:level) diamonds (:diamonds)", [
                        'level'    => $previous->level,
                        'diamonds' => $previous->diamonds,
                    ]),
                ]);

                return redirect()->back()->with(compact('error'));
            }
        });
        return $form;
    }
}
