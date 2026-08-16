<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Ticket;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin as AdminScript;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;

class TicketController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    use HasResourceActions;
    public $permission_name = 'complaints';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Tickets'))
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
            ->title(trans('Tickets'))
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
            ->title(trans('Tickets'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Tickets'))
            ->body($this->form()));
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Ticket);
        $countryID = Common::filterCountryIds();

        $grid->model()->with([
            'user:id,name,uuid,phone',
            'user.agency',
            'user.profile:user_id,avatar',
            'user.country',
            'user.senderLevel',
            'user.receiverLevel',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
        ])->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)));


        $grid->id(__('ID_tiket'));

        $grid->column('name', __('User Info'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        AdminScript::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('contact_num', __('contact'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 40) . (strlen($description) > 20 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
        });
        $grid->column('problem', __('problem'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 20) . (strlen($description) > 40 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
        });
        $grid->column('description', __('Description'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 40) . (strlen($description) > 40 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
        });
        AdminScript::script("
            $(document).ready(function () {
                $('.view-description').click(function (e) {
                    e.preventDefault();

                    var description = $(this).data('description');

                    $('#modalDescriptionTitle').text('Full Description');
                    $('#modalDescriptionContent').text(description);

                    $('#descriptionModal').modal('show');
                });

                $('.view-image').click(function (e) {
                    e.preventDefault();
                    var imgSrc = $(this).data('img');
                    $('#modalImageContent').attr('src', imgSrc);
                    $('#imageModal').modal('show');
                });
            }); ");

        $grid->column('img', __('img'))->display(function ($img) {

            $defaultImage = asset('images/image.png');
            $url = getImagePath($img) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return $url;
        })->image('', 30);
        $grid->column('status', __('status'))->switch(Common::getSwitchStates());
        $this->extendGrid($grid);
        $grid->disableExport();
        $grid->disableCreateButton();
        $grid->actions(function ($actions) {
            $actions->disableEdit();
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
        $show = new Show(Ticket::findOrFail($id));

        $show->id('ID');
        //        $show->user_id('user_id');
        $show->field('contact_num', __('contact'));
        $show->field('problem', __('problem'));
        $show->field('description', __('description'));
        $show->field('img', __('img'))->image('', 80);
        $show->field('status', __('status'))->using([0 => "closed", 1 => "open"]);
        //        $show->admin_id('admin_id');
        //        $show->created_at(trans('admin.created_at'));
        //        $show->updated_at(trans('admin.updated_at'));
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
        $form = new Form(new Ticket);
        $this->disableFormTools($form);

        $form->display('ID');
        //        $form->text('user_id', 'user_id');
        $form->text('contact_num', __('contact'));
        $form->text('problem', __('problem'));
        $form->textarea('description', __('description'));
        $form->image('img', __('img'));
        $form->switch('status', __('status'))->states(Common::getSwitchStates());
        //        $form->text('admin_id', 'admin_id');
        //        $form->display(trans('admin.created_at'));
        //        $form->display(trans('admin.updated_at'));

        return $form;
    }
}
