<?php

namespace Modules\Region\Http\Controllers;

use App\Models\Agency;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Services\AppFeatureService;
use App\Admin\Actions\AcceptAgencyAction;
use App\Admin\Actions\RefuseAgencyAction;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\Auth;

class RequestAgencyController extends AdminController
{
    // public $permission_name = 'agencies-request';

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("agencies");
    }


    public function index(Content $content)
    {
        return $content
            ->header(trans('request-agencies'))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            });
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
            ->title(trans('Request agencies'))
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
            ->title(trans('Request agencies'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Request agencies'))
            ->body($this->form()));
    }



    protected function grid()
    {
        $grid = new Grid(new Agency());
        $grid->model()
            ->where('bd_id', Auth::user()->id)
            ->where('status', 0)->orderByDesc("id")
            ->whereHas('additionalInfo', function ($query) {
                $query->where('status', 0);
            });
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('owner.uuid', __('uuid'));
            });
        });
        $grid->column('id', __('Id'));
        $grid->column('owner.name', trans('name'))->display(function ($name) {
            $uid = @$this->owner->uuid;
            $path = @$this?->owner->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl =  ($this->owner) ? url("admin/users/{$this->owner->id}") : 0;
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        });

        $grid->column('name', __('agency'))->display(function () {
            $name = @$this->name ?? '';
            $path = @$this->img;
            $defaultImage = asset("images/icon-agency.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->id, $url, 40, 40);

            return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                $image
                <span>$name</span>
            </div>
        ";
        });
        $grid->column('phone', __('whats app'));
        $grid->column('additionalInfo.country', __('country'));
        $grid->column('additionalInfo.gmail', __('Email'));
        $grid->column('additionalInfo.video', __('video'))->display(function () {
            // Assuming you have a 'video_path' field in your model
            $videoPath = \App\Helpers\StorageHelper::url($this->additionalInfo?->video);

            // You can customize the HTML to embed the video
            return "<video width='150' height='100' controls><source src='$videoPath' type='video/mp4'>Your browser does not support the video tag.</video>";
        });

        $grid->column('additionalInfo.face_image_nationalId', __('face nationalId'))->display(function () {
            $img = $this->additionalInfo?->face_image_nationalId;
            if ($img == null || $img == '') {
                return 'No image founded';
            }

            $imageUrl = \App\Helpers\StorageHelper::url($img);
            return "<a href='{$imageUrl}' target='_blank' rel='noopener noreferrer'><img src='{$imageUrl}' style='height: 50px;'></a>";
        });
        $grid->column('additionalInfo.back_image_nationalId', __('back nationalId'))->display(function () {
            $img = $this->additionalInfo?->back_image_nationalId;
            if ($img == null || $img == '') {
                return 'No image founded';
            }

            $imageUrl = \App\Helpers\StorageHelper::url($img);
            return "<a href='{$imageUrl}' target='_blank' rel='noopener noreferrer'><img src='{$imageUrl}' style='height: 50px;'></a>";
        });


        $grid->column('additionalInfo.salary', __('salary'))->display(function ($salary) {
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center; '>

                        <span>{$salary}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });
        $grid->column('additionalInfo.host', __('host'));
        $grid->column('additionalInfo.user.name', __('The user ID that referred you to us'))->display(function ($name) {
            $uid = @$this->additionalInfo->user->uuid;
            $path = @$this?->additionalInfo->user->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl =  ($this->additionalInfo->user) ? url("admin/users/{$this->additionalInfo->user->id}") : 0;
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        });
        $grid->column('additionalInfo.history_app_info', __('The platform you worked on'));
        $grid->actions(function ($actions) {
            $model = $actions->row;
            $actions->disableEdit();
            $actions->disableView();
            $actions->disableDelete();
            $actions->add(new AcceptAgencyAction($model->id));
            $actions->add(new RefuseAgencyAction($model->id));
        });
        $grid->disableCreateButton();

        // $grid->tools(function (Grid\Tools $tools) {
        //     $url = '/admin/request-agencies-filteration';
        //     $button = '<a href="' . $url . '" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("admin.history") . '</a>';
        //     $tools->append($button);
        // });

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
        $show = new Show(Agency::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('owner_id', __('Owner id'));
        $show->field('name', __('Name'));
        $show->field('notice', __('Notice'));
        $show->field('status', __('Status'));
        $show->field('phone', __('Phone'));
        $show->field('url', __('Url'));
        $show->field('img', __('Img'));
        $show->field('contents', __('Contents'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('old_usd', __('Old usd'));
        $show->field('target_usd', __('Target usd'));
        $show->field('target_token_usd', __('Target token usd'));
        $show->field('app_owner_id', __('App owner id'));
        $show->field('salary', __('Salary'));
        $show->field('Shipping_agency', __('Shipping agency'));
        $show->field('Host_agency', __('Host agency'));
        $show->field('agency_manger_id', __('Agency manger id'));
        $show->field('agency_dash_manger_id', __('Agency dash manger id'));
        $show->field('deleted_at', __('Deleted at'));
        $show->field('monthly_target', __('Monthly target'));
        $show->field('password', __('Password'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Agency());
        $this->disableFormTools($form);


        $form->number('owner_id', __('Owner id'));
        $form->text('name', __('Name'));
        $form->text('notice', __('Notice'))->default('notice');
        $form->switch('status', __('Status'))->default(1);
        $form->mobile('phone', __('Phone'));
        $form->url('url', __('Url'));
        $form->image('img', __('Img'));
        $form->textarea('contents', __('Contents'));
        $form->decimal('old_usd', __('Old usd'));
        $form->decimal('target_usd', __('Target usd'));
        $form->decimal('target_token_usd', __('Target token usd'));
        $form->number('app_owner_id', __('App owner id'));
        $form->decimal('salary', __('Salary'))->default(0.00);
        $form->number('Shipping_agency', __('Shipping agency'));
        $form->number('Host_agency', __('Host agency'));
        $form->number('agency_manger_id', __('Agency manger id'));
        $form->number('agency_dash_manger_id', __('Agency dash manger id'));
        $form->decimal('monthly_target', __('Monthly target'))->default(1000000);
        $form->password('password', __('Password'));

        return $form;
    }
}
