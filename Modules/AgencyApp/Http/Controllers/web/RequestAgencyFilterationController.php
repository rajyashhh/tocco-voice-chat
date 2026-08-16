<?php

namespace Modules\AgencyApp\Http\Controllers\web;


use App\Models\Agency;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use App\Services\AppFeatureService;
use App\Admin\Controllers\MainController;

class RequestAgencyFilterationController extends MainController
{

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("agencies");
    }
    public $permission_name = 'request-agency-history';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Agency'))
            ->body($this->grid()));
    }
    protected $title = 'Agency';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Agency());
        $grid->model()->where('status', '!=', 0)->with([
            'owner',
            'additionalInfo',
            'additionalInfo.country',
            'owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')

        ])
            ->orderByDesc("id")->whereHas('additionalInfo', function ($query) {
                $query->where('status', '!=', 0);
            });
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('owner.uuid', __('uuid'));
            });
            $filter->equal('status', __('Status'))->radio([
                ''   => 'All',
                1    => __("accept"),
                2    => __("denied"),
            ]);
        });
        $grid->column('id', __('Id'));
        $grid->column('owner.name', trans('name'));
        $grid->column('ownerUuid', trans('uuid'))->display(function ($q) {
            return @$this->owner->uuid ?? '';
        });
        $grid->column('status', __('status'))->display(function ($q) {
            return $this->status == 1 ? __("accept") : __("denied");
        });
        $grid->column('name', __('Name'));
        $grid->column('phone', __('whats app'));
        $grid->column('additionalInfo.country', __('country'));
        $grid->column('img', __('Img'))->image('', 30);
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


        $grid->column('additionalInfo.salary', __('salary'));
        $grid->column('additionalInfo.host', 'host');
        $grid->column('additionalInfo.user_id', 'معرف المستخدم الذي اوصلك الينا');
        $grid->column('additionalInfo.history_app_info', 'المنصه التي عملت به');
        $grid->actions(function ($actions) {
            $model = $actions->row;
            $actions->disableEdit();
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->disableCreateButton();


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
        // $show->field('Shipping_agency', __('Shipping agency'));
        // $show->field('Host_agency', __('Host agency'));
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
        // $form->number('Shipping_agency', __('Shipping agency'));
        // $form->number('Host_agency', __('Host agency'));
        $form->number('agency_manger_id', __('Agency manger id'));
        $form->number('agency_dash_manger_id', __('Agency dash manger id'));
        $form->decimal('monthly_target', __('Monthly target'))->default(1000000);
        $form->password('password', __('Password'));

        return $form;
    }
}
