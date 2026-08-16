<?php

namespace Modules\AgencyApp\Http\Controllers\web;

use App\Models\User;
use App\Models\Agency;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use App\Services\AppFeatureService;

use Encore\Admin\Controllers\AdminController;
use Modules\AgencyApp\Entities\AdditionalInfo;


class RecommendationAgencyController extends AdminController
{

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("agencies");
    }
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'RecommendationAgencies';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());
        $grid->model()->whereHas('additionalInfo',function($q) 
        {
            $q->where('status', 1);

        });
        
        $grid->quickSearch();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('uuid', __('uuid'));
            });
        });
        $grid->column('uuid', __('uuid'));
        $grid->column('name', __('name'));
        $grid->column('additional', __('agencies'))->modal('الوكالات', function ($model) {
            $agenciesIds =AdditionalInfo::where('user_id',$this->id)->pluck('agency_id');
            $agencies = Agency::whereIn('id',$agenciesIds)->select("name", 'id',)->get();
            $filteredAgencies = $agencies->map(function ($agency) {
                return $agency->only(["id", "name", ]);
            });
            return new Table([ __('ID'),__('Name')], $filteredAgencies->toArray());
        });
        $grid->column('count', __('count'))->display(function () {
            return AdditionalInfo::where('user_id',$this->id)->count();
        });
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableEdit();
            $actions->disableView();
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
        $show = new Show(AdditionalInfo::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('agency_id', __('Agency id'));
        $show->field('gmail', __('Gmail'));
        $show->field('face_image_nationalId', __('Face image nationalId'));
        $show->field('back_image_nationalId', __('Back image nationalId'));
        $show->field('country', __('Country'));
        $show->field('history_app_info', __('History app info'));
        $show->field('salary', __('Salary'));
        $show->field('host', __('Host'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('status', __('Status'));
        $show->field('user_id', __('User id'));
        $show->field('video', __('Video'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AdditionalInfo());

        $form->number('agency_id', __('Agency id'));
        $form->text('gmail', __('Gmail'));
        $form->text('face_image_nationalId', __('Face image nationalId'));
        $form->text('back_image_nationalId', __('Back image nationalId'));
        $form->text('country', __('Country'));
        $form->textarea('history_app_info', __('History app info'));
        $form->number('salary', __('Salary'));
        $form->number('host', __('Host'));
        $form->switch('status', __('Status'));
        $form->number('user_id', __('User id'));
        $form->text('video', __('Video'));

        return $form;
    }
}
