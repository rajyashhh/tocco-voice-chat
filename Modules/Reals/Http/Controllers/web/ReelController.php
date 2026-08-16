<?php

namespace Modules\Reals\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Modules\Reals\Entities\Real;
use App\Admin\Controllers\MainController;
use App\Helpers\Common;

class ReelController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */


    public $permission_name = 'Real';
    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('reels'))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('reels'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('reels'))
            ->body($this->form()));
    }
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('reels'))
            ->body($this->detail($id)));
    }
    protected function grid2()
    {
        $form = new Box();
        $form->view('admin.grid.common.reels');

        return $form;
    }
    protected function grid()
    {
        $grid = new Grid(new Real());
        $countryID = Common::filterCountryIds();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column('1/2', function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;

                    $query->whereHas('user', function ($query) use ($input) {
                        //                        $query->where('name', 'like', "%$input%")
                        $query->where('uuid',  trim($input));
                    });
                }, __('User'))->placeholder(__('Search by name or UUID'));
            });
        });
        $grid->model()->when($countryID, function ($query) use ($countryID) {
            $query->where(function ($q) use ($countryID) {
                $q->whereHas('user', function ($subQuery) use ($countryID) {
                    $subQuery->whereIn('country_id', $countryID);
                });
            });
        })->orderByDesc('created_at');

        // $grid->column('description', __('Description'))->display(function ($description) {
        //     $limitedDescription = mb_substr($description, 0, 50);

        //     return $limitedDescription;
        // });



        $grid->column('user.name', __('user'))->display(function ($name) {
            $defaultImage = asset("images/businessman-icon.jpg"); // الصورة الافتراضية
            $avatarPath = @$this->user->avatar;
            $userId = @$this->user->id;
            $uid = @$this->user->uuid;

            $avatar = getImagePath($avatarPath) ?? $defaultImage;

            if (!isImageExists($avatar)) {
                $avatar = $defaultImage;
            }



            return "<div style='display: flex; align-items: center; gap: 10px; cursor: pointer;' onclick=\"window.location.href='/admin/users/$userId'\">
                        <img src='$avatar' alt='User Avatar' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover;'>
                        <div>
                            <span style='color: #3498db; font-weight: bold;'>$name</span><br>
                            <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                        </div>
                    </div>";
        });


        $grid->column('comment_num', __('status'))->display(function ($commentNum) {
            $like = count(@$this->likes);
            $commentNum = count(@$this->comments);
            return "<span class=\"fa fa-comment\"> $commentNum</span>  <span class=\"fa fa-thumbs-up\"> $like</span> ";
        });
        $grid->column('created_at', __('Created at'))->sortable()->diffForHumans();
        $grid->column('video', __('video'))->display(function () {
            // Assuming you have a 'video_path' field in your model
            $videoPath = getDriverUrl() . '/' . $this->url;

            // You can customize the HTML to embed the video
            return "<video width='150' height='100' controls><source src='$videoPath' type='video/mp4'>Your browser does not support the video tag.</video>";
        });

        $grid->column('description', __('Description'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 20) . (strlen($description) > 30 ? '...' : '');

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

        $grid->disableCreateButton();
        $permission_name = $this->permission_name;
        $grid->actions(function ($actions) use ($permission_name) {
            $actions->disableEdit();
            if (!Admin::user()->can('delete-' . $permission_name) && !Admin::user()->can('*')) {
                $actions->disableDelete();
            }
            if (!Admin::user()->can('show-' . $permission_name) && !Admin::user()->can('*')) {
                $actions->disableView();
            }
        });
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
        $show = new Show(Real::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('description', __('Description'));
        $show->field('comment_num', __('Comment num'))->as(function () {
            return count($this->comments);
        });
        $show->field('like_num', __('Like num'))->as(function () {
            return count($this->likes);
        });
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('video', __('Video'))->as(function () {
            // Assuming you have a 'video_path' field in your model
            $videoPath = getDriverUrl() . '/' . $this->url;

            // You can customize the HTML to embed the video
            return "<video width='150' height='100' controls><source src='$videoPath' type='video/mp4'>Your browser does not support the video tag.</video>";
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Real());
        $this->disableFormTools($form);

        $form->number('user_id', __('User id'));
        $form->text('description', __('Description'));
        // $form->number('comment_num', __('Comment num'));
        // $form->number('like_num', __('Like num'));
        $form->image('img', __('Img'));

        return $form;
    }
}
