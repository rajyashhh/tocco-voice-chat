<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Helpers\Common;
use App\Models\RoomCategory;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class RoomCategoryController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'categories';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('categories'))
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
            ->title(trans('categories'))
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
            ->title(trans('categories'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('categories'))
            ->body($this->form()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new RoomCategory);

        $grid->id(__('ID'));
        $grid->name(trans('name'));
        $grid->column('name_en', trans('name_en'));
        $grid->column('img', trans('img'))->display(function ($img) {
            $defaultImage = asset("images/background_room.jpg");
            $path = getImagePath($img);
            if (!isImageExists(@$path)) {
                $path = $defaultImage;
            }
            $parsedUrl = parse_url($path);
            $correctUrl = isset($parsedUrl['host']) ? $path : url("/$path");

            return "
                    <img src='$correctUrl' style='width: 50px; height: 50px; border-radius: 5px; cursor: pointer;' onclick='openModal(\"$correctUrl\")' />

                    <div id='imageModal' class='modal' style='display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); text-align:center;'>
                        <span onclick='closeModal()' style='position:absolute; top:10px; right:20px; font-size:30px; color:white; cursor:pointer;'>&times;</span>
                        <img id='modalImage' style='display:block; margin:auto; max-width:90%; max-height:90%; margin-top:50px; border-radius:5px;' />
                    </div>

                    <script>
                        function openModal(src) {
                            let modal = document.getElementById('imageModal');
                            let modalImage = document.getElementById('modalImage');
                            modal.style.display = 'block';
                            modalImage.src = src;
                        }

                        function closeModal() {
                            document.getElementById('imageModal').style.display = 'none';
                        }

                        // Close modal when clicking outside the image
                        document.getElementById('imageModal').addEventListener('click', function(event) {
                            if (event.target === this) {
                                closeModal();
                            }
                        });
                    </script>
                ";
        });
        $grid->column('type', trans('type'));
        $grid->column('enable', trans('enable'))->switch(Common::getSwitchStates());
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
        $show = new Show(RoomCategory::findOrFail($id));

        //        $show->id('ID');
        //        $show->parent_id('parent_id');
        //        $show->name('name');
        //        $show->img('img');
        //        $show->enable('enable');
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
        $form = new Form(new RoomCategory);
        $this->disableFormTools($form);


        $form->display(__('ID'));
        $form->select('parent_id', trans('parent'))->options(function () {
            $options = [0 => trans('root')];
            $cats = RoomCategory::query()->where('id', '!=', $this->id)->where('enable', 1)->where('parent_id', 0)->get();
            foreach ($cats as $cat) {
                $options[$cat->id] = $cat->name;
            }
            return $options;
        });
        $form->text('name', trans('name'))->rules('required');
        $form->text('name_en', trans('name_en'))->rules('required');
        $form->select('type', trans('type'))->options([
            'party' => trans('party')
        ]);
        $form->image('img', trans('img'));
        $form->switch('enable', trans('enable'))->states(Common::getSwitchStates());

        return $form;
    }
}
