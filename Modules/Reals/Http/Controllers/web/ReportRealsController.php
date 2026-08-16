<?php

namespace Modules\Reals\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\Reals\Entities\Real;
use Modules\Reals\Entities\ReportReals;

class ReportRealsController extends MainController
{
    use HasResourceActions;

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'ReportReals';
    public $permission_name = 'report-real';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Report Reel'))
            ->body($this->grid()));
    }


    protected function grid()
    {
        $grid = new Grid(new ReportReals());
        $grid->model()->with([
            'reporter.profile:user_id,avatar',
                'reporter.country',
                'reporter.senderLevel',
                'reporter.receiverLevel',
                'reporter.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'reportedUser.profile:user_id,avatar',
                'reportedUser.country',
                'reportedUser.senderLevel',
                'reportedUser.receiverLevel',
                'reportedUser.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
        ])->whereHas('reel')->orderByDesc('id');

        $grid->column('id', __('ID'));

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->reporter);
        });

        $grid->column('name', __('Reported User'))->display(function () {
            return app(UserService::class)->adminUserCard($this->reportedUser);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('description', __('Description'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 40) . (strlen($description) > 40 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
        });

        Admin::script("
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


        if (Admin::user()->can('delete-' . 'Real') || Admin::user()->can('*')) {
            $grid->column(__('redirect_button'))->display(function () {
                $redirectRoute = 'delete-reel';
                return '<a href="' . route($redirectRoute, ['real_id' => $this->real_id, 'id' => $this->id]) . '" class="btn btn-xs btn-danger">' . __('admin.delete_video') . '</a>';
            });
        }

        $grid->column('real_id', __('View Reel'))->modal('Video Preview', function ($model) {
            return self::getRoomsShow($model->reel);
        });

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();

        $permission_name = $this->permission_name;

        $grid->actions(function ($actions)  use ($permission_name) {
            $actions->disableEdit();
            if (! Admin::user()->can('delete-' . $permission_name) && !Admin::user()->can('*')) {
                $actions->disableDelete();
            }
            if (!Admin::user()->can('show-' . $permission_name) && !Admin::user()->can('*')) {
                $actions->disableView();
            }
        });

        return $grid;
    }

    public static function getRoomsShow(Real $reel)
    {
        $show = new Show($reel);

        $show->field('url', __('Video'))->unescape()->as(function ($path) {
            $url = getImagePath($path);
            return "<video width='100%' controls>
                    <source src='$url' type='video/mp4'>
                    Your browser does not support the video tag.
                </video>";
        });



        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableList();
            $tools->disableDelete();
        });

        Admin::script("
        if (window.innerWidth >= 1024) {
            $('.table-responsive').removeClass('table-responsive');
        }
    ");

        return $show;
    }





    // protected function grid()
    // {
    //     $grid = new Grid(new ReportReals());
    //     $grid->model()->whereHas('reel')->orderByDesc('id');

    //     $grid->column('id', __('Id'));
    //     $grid->column('Reporter_id', __('Reporter id'));
    //     $grid->column('Reported_id', __('Reported id'));
    //     $grid->column('description', __('Description'));
    //     $grid->column(__('redirect_button'))->display(function ($_) {
    //         $redirectRoute = 'delete-reel';
    //         $button = '<a href="'.route($redirectRoute, ['real_id' => $this->real_id, 'id' => $this->id]).'" class="btn btn-xs btn-primary">'.__('admin.delete_video').'</a>';
    //         return $button;
    //     });

    //     $grid->column('real_id', __('View Reel'))->modal('test', function ($model){
    //         return self::getRoomsShow($model->reel);
    //     });

    //     $grid->disableCreateButton();
    //     $grid->disableExport();
    //     $grid->actions(function ($actions) {
    //         $actions->disableEdit();
    //     });
    //             return $grid;
    // }

    // public static function getRoomsShow(Real $reel){

    //     $show = new Show($reel);
    //     $show->field('id', 'ID');
    //     $show->field('url', __('Video'))->display(function ($path) {
    //         /** @var Ware $this */
    //         $url = getImagePath($path);
    //         return handleShowImageWithTypes($this->id, $url, 50, 50);
    //     });
    //     $show->field('description', __('description'));


    //     $show->panel()
    //          ->tools(function ($tools) {
    //              $tools->disableEdit();
    //              $tools->disableList();
    //              $tools->disableDelete();
    //          });
    //          Admin::script("
    //          if (window.innerWidth >= 1024) { // Example threshold for desktop screens
    //              $('.table-responsive').removeClass('table-responsive');
    //              }
    //          ");
    //     return $show;
    // }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ReportReals::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('real_id', __('Real id'));
        $show->field('Reporter_id', __('Reporter id'));
        $show->field('Reported_id', __('Reported id'));
        $show->field('description', __('Description'));
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
        $form = new Form(new ReportReals());
        $this->disableFormTools($form);

        $form->number('real_id', __('Real id'));
        $form->number('Reporter_id', __('Reporter id'));
        $form->number('Reported_id', __('Reported id'));
        $form->text('description', __('Description'));

        return $form;
    }
}
