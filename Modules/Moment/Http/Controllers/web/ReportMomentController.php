<?php

namespace Modules\Moment\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Entities\ReportMoment;

class ReportMomentController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */

    public $permission_name = 'report-moment';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('report-moments'))
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
        return $content
            ->title(trans('report-moments'))
            ->body($this->detail($id));
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
        return $content
            ->title(trans('report-moments'))
            ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
            ->title(trans('report-moments'))
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */



    protected function grid()
    {
        $grid = new Grid(new ReportMoment());
        $grid->model()->whereHas('moment')->orderByDesc('id');
        $grid->model()->with([
            'moment' => fn($query) => $query->withExists(['likes', 'comments']),
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
        ]);

        $grid->column('id', __('Id'));

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->reporter);
        });

        $grid->column('name', __('Reported User'))->display(function () {
            return app(UserService::class)->adminUserCard($this->reportedUser);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());







        $grid->column('moment_id', __('View Moment'))->modal(__('moment'), function ($model) {
            return self::getRoomsShow($model->moment);
        });

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


        $grid->column('type', __('Type'));
        if (!Admin::user()->can('*') || !Admin::user()->can('delete-' . 'Moment')) {
            $grid->column(__('redirect_button'))->display(function () {
                $delete_moment = 'حذف اللحظة';
                if (app()->getLocale() == 'en') {
                    $delete_moment = "Delete moment";
                }
                $redirectRoute = 'delete-moment';
                return '<a href="' . route($redirectRoute, ['moment_id' => $this->moment_id, 'id' => $this->id]) . '" class="btn btn-xs btn-primary">' . $delete_moment . ' </a>';
            });
        }
        $this->extendGrid($grid);
        $grid->disableCreateButton();
        return $grid;
    }

    public static function getRoomsShow(Moment $moment)
    {
        $show = new Show($moment);

        $show->field('img', __('Image'))->unescape()->as(function ($img) {
            if (!$img) {
                return "<span style='color: #e74c3c;'>No Image Available</span>";
            }
            $url = getImagePath($img); // استخدام `getImagePath` بدلًا من بناء الرابط يدويًا
            return "<img src='$url' style='max-width: 500px; max-height: 500px; border-radius: 10px;' class='img-thumbnail'/>";
        });

        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableList();
            $tools->disableDelete();
        });

        return $show;
    }


    public static function getDescriptionShow(Real $reel)
    {
        $show = new Show($reel);

        $show->field('description', __('Description'))->unescape()->as(function ($description) {
            $limitedDescription = mb_substr($description, 0, 40) . (strlen($description) > 40 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
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
    //     $grid = new Grid(new ReportMoment());
    //     $grid->model()->whereHas('moment')->orderByDesc('id');
    //     $grid->model()->with(['moment' => fn($query) => $query->withExists(['likes','comments'])]);
    //     $grid->column('id', __('Id'));
    //     // $grid->column('moment_id', __('Moment id'));
    //     $grid->column('Reporter_id', __('Reporter id'));
    //     $grid->column('Reported_id', __('Reported id'));
    //     $grid->column('description', __('Description'));
    //     $grid->column('type', __('Type'));
    //     $grid->column(__('redirect_button'))->display(function ($_) {
    //         $redirectRoute = 'delete-moment';
    //         $button = '<a href="'.route($redirectRoute, ['moment_id' => $this->moment_id,'id' => $this->id]).'" class="btn btn-xs btn-primary">حذف اللحظة</a>';
    //         return $button;
    //     });

    //     $grid->column('moment_id', __('View Moment'))->modal('test', function ($model){
    //         return self::getRoomsShow($model->moment);
    //     });
    //     return $grid;
    // }

    // public static function getRoomsShow(Moment $moment){

    //     $show = new Show($moment);
    //     $show->field('id', 'ID');
    //     $show->field('img', __('Image'))->image(getDriverUrl() . DIRECTORY_SEPARATOR, 500, 500);
    //     $show->field('description', __('description'));
    //     $show->field('likes_exists', __('Likes count'))->number();
    //     $show->field('comments_exists', __('Comments count'))->number();

    //     $show->panel()
    //          ->tools(function ($tools) {
    //              $tools->disableEdit();
    //              $tools->disableList();
    //              $tools->disableDelete();
    //          });

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
        $show = new Show(ReportMoment::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('moment_id', __('Moment id'));
        $show->field('Reporter_id', __('Reporter id'));
        $show->field('Reported_id', __('Reported id'));
        $show->field('description', __('Description'));
        $show->field('type', __('Type'));
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
        $form = new Form(new ReportMoment());
        $this->disableFormTools($form);

        $form->number('moment_id', __('Moment id'));
        $form->number('Reporter_id', __('Reporter id'));
        $form->number('Reported_id', __('Reported id'));
        $form->text('description', __('Description'));
        $form->text('type', __('Type'));

        return $form;
    }
}
