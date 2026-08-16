<?php

namespace Modules\Moment\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\MomentGallery;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use App\Helpers\Common;
use Encore\Admin\Layout\Content;
use Modules\Moment\Entities\Moment;
use App\Admin\Controllers\MainController;

class MomentController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Moment';

    public $permission_name = 'moment';
    /**
     * Make a grid builder.
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(__($this->title))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }
    protected function grid2()
    {
        $form = new Box();
        $form->view('admin.grid.common.moments');

        return $form;
    }


    protected function grid()
    {
        $grid = new Grid(new Moment());
        $countryID = Common::filterCountryIds();
        // 🔹 **إضافة الفلتر للبحث عن المستخدم بالاسم أو UUID**
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column('1/2', function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;

                    $query->whereHas('user', function ($query) use ($input) {
                        $query->where('name', 'like', "%$input%")
                            ->orWhere('uuid', 'like', "%$input%");
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

        // 🔹 **عرض الوصف في مودال عند النقر عليه**
        $grid->column('description', __('Description'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 40) . (strlen($description) > 40 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
        });

        // 🔹 **عرض معلومات المستخدم**
        $grid->column('user.name', __('User'))->display(function ($name) {
            $uid = @$this->user->uuid;
            $defaultImage = asset("images/businessman-icon.jpg");
            $avatarPath = @$this->user->avatar;
            $avatar = getImagePath($avatarPath) ?? $defaultImage;
            if (!isImageExists($avatar)) {
                $avatar = $defaultImage;
            }
            $userUrl = admin_url('users/' . $this->user_id); // رابط صفحة المستخدم في لوحة التحكم

            return "<div style='display: flex; align-items: center; gap: 10px;'>
                    <img src='$avatar' alt='User Avatar' style='width: 40px; height: 40px; border-radius: 50%;'>
                    <div>
                        <a href='$userUrl' style='font-weight: bold; text-decoration: none;'>$name</a><br>
                        <span style='font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>";
        });


        // 🔹 **عرض إحصائيات (التعليقات + الإعجابات)**
        $grid->column('comment_num', __('status'))->display(function () {
            $likeCount = count(@$this->likes);
            $commentCount = count(@$this->comments);
            return "<span class=\"fa fa-comment\"> $commentCount</span>  <span class=\"fa fa-thumbs-up\"> $likeCount</span>";
        });

        // 🔹 **عرض تاريخ الإنشاء**
        $grid->column('created_at', __('Created at'))->sortable()->diffForHumans();

        // $grid->column('img', __('Image'))->display(function () {
        //     $id = $this->id;
        //     $galleries = MomentGallery::where('moment_id', $id)->get();

        //     $html = '<div id="image-gallery-' . $id . '" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; padding: 20px;">';

        //     foreach ($galleries as $image) {
        //         $imgUrl = getDriverUrl() . '/' . $image->image;

        //         $html .= '<div style="overflow: hidden; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
        //                     <img src="' . $imgUrl . '"
        //                          style="width: 100%; height: 200px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
        //                          data-original="' . $imgUrl . '"
        //                          loading="lazy"
        //                          class="gallery-image">
        //                  </div>';
        //     }

        //     $html .= '</div>';

        //     Admin::script("
        //         new Viewer(document.getElementById('image-gallery-$id'));
        //     ");

        //     return $html;
        // });

        $grid->column('img', __('Image'))->display(function () {
            $id = $this->id;
            $galleries = MomentGallery::where('moment_id', $id)->get();

            if ($galleries->isEmpty()) {
                return 'No Image';
            }

            $html = '<div id="image-gallery-' . $id . '" style="display: none;">';
            $imgUrl = '';
            foreach ($galleries as $image) {
                $imgUrl = getDriverUrl() . '/' . $image->image;

                $html .= '<img src="' . $imgUrl . '"
                         style="width: 100%; height: 200px; object-fit: cover;"
                         data-original="' . $imgUrl . '"
                         loading="lazy"
                         class="gallery-image">';
            }

            $html .= '</div>';

            // Show only the first image

            $html .= '<img src="' . $imgUrl . '"
                      style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"
                      onclick="document.querySelector(`#image-gallery-' . $id . ' img`).click()">';

            Admin::script("
            new Viewer(document.getElementById('image-gallery-$id'));
        ");

            return $html;
        });

        $grid->disableCreateButton();
        $this->extendGrid($grid);
        return $grid;
    }

    // protected function grid()
    // {
    //     $grid = new Grid(new Moment());
    //     $grid->filter(function (Grid\Filter $filter) {
    //         $filter->expand();
    //         $filter->disableIdFilter();
    //         $filter->column('1/2', function ($filter) {
    //             $filter->where(function ($query) {
    //                 $input = $this->input;

    //                 $query->whereHas('user', function ($query) use ($input) {
    //                     $query->where('name', 'like', "%$input%")
    //                     ->orWhere('uuid', 'like', "%$input%");
    //                 });
    //             }, __('User'))->placeholder(__('Search by name or UUID'));
    //         });
    //     });
    //     $grid->model()->orderByDesc('created_at');
    //     $grid->column('description', __('Description'));
    //     $grid->column('user.name', __('user_id'))->display(function ($name) {
    //         $uid = @$this->user->uuid;

    //         return "$name <br>
    //         <span style=\"color: #aaa; font-size: smaller;\">UID: $uid</span>";
    //     });
    //     $grid->column('comment_num', __('Stats'))->display(function ($commentNum) {
    //         $like = count(@$this->likes);
    //         $commentNum = count(@$this->comments);
    //         return "<span class=\"fa fa-comment\"> $commentNum</span>  <span class=\"fa fa-thumbs-up\"> $like</span> ";
    //     });
    //     $grid->column('created_at', __('Created at'))->sortable()->diffForHumans();
    //     $grid->column('img',__('Img'))->modal('show image' , function ($model, ) {
    //         $img = $model->img;
    //         if($img == null || $img == ''){
    //             return 'No image founded';
    //         }

    //         $img = getDriverUrl().'/'.$img;
    //         $img = "<img src='" . $img ."' style='width:500px;height:500px' class='img img-thumbnail'$ />";

    //         return (new WidgetsTable([''], [[$img]]));
    //     });

    //     return $grid;
    // }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(Moment::findOrFail($id));

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
        $show->field('img', __('Img'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Moment());
        $this->disableFormTools($form);

        $form->number('user_id', __('User id'));
        $form->text('description', __('Description'));
        // $form->number('comment_num', __('Comment num'));
        // $form->number('like_num', __('Like num'));
        $form->image('img', __('Img'));

        return $form;
    }

    public function momentGallery(Content $content, $id)
    {
        $galleries = MomentGallery::where('moment_id', $id)->get();
        return $content
            ->header(__('images'))
            ->description('')

            ->body(view('momentGallery', compact('galleries')));
    }
}
