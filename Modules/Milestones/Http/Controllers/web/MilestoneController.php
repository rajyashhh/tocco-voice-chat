<?php

namespace Modules\Milestones\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Jobs\MilestoneJob;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Modules\Milestones\Entities\Milestone;

class MilestoneController extends MainController
{
    public $permission_name = 'milestone';
    /**
     * Title for current resource.
     *
     * @var string
     */
    // protected $title = 'Milestone';

    public function index(Content $content)
    {
        return $content
            ->header(__('Milestone'))
            ->description(__('Milestone'))
            ->body($this->grid());
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Milestone'))
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
            ->title(trans('Milestone'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Milestone'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new Milestone());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', __('Name'))->display(function ($value) {
            return __($value);
        });
        if (Admin::user()->can('dedicate-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $grid->column('rewards', __('rewards'))->display(function () {
                $url =  admin_url("milestone-rewards/" . $this->id);
                return "<a href='{$url}' class='btn btn-xs btn-info'>
                        <i class='fa fa-eye'></i> " . __('rewards') . "
                    </a>";
            });
        }

        $grid->column('sync', __('Rewards'))->display(function () {
            $url = admin_url("milestones/{$this->id}/sync");
            return "<button type='button' class='btn btn-xs btn-success sync-milestone-btn' data-url='{$url}'>
                        <i class='fa fa-sync'></i> " . __('Reapply Rewards') . "
                    </button>";
        });

        Admin::script("
            document.querySelectorAll('.sync-milestone-btn').forEach(function(button){
                button.addEventListener('click', function(){
                    var url = this.dataset.url;
                    Swal.fire({
                        title: '" . __('Are you sure?') . "',
                        text: '" . __('Are you sure you want to reapply rewards?') . "',
                        type: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '" . __('Yes, reapply!') . "',
                        cancelButtonText: '" . __('Cancel') . "'
                    }).then((result) => {
                        if (result.value) {
                            window.location.href = url;
                        }
                    });
                });
            });
        ");

        $grid->filter(function ($filter) {
            $filter->like('name', __('Name'));
            $filter->equal('type', __('Type'));
        });

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            // $actions->disableEdit();
            // $actions->disableView();
        });
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableRowSelector();
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
        $show = new Show(Milestone::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Name'));
        $show->field('type', __('Type'));
        $show->field('reward_achievement', __('Reward Achievement'));
        $show->field('expire', __('Expire'));
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
        $form = new Form(new Milestone());

        $form->text('name', __('Name'))->required();
        $form->select('slug', __('Type'))->options([
            'owner' => 'Owner',
            'host' => 'Host',
            'agency' => 'Agency',
            'family' => 'Family',
        ])->required();
        $form->switch('is_active', __('Active'))->default(1);

        return $form;
    }

    public function syncMilestone($id)
    {

        dispatch(new MilestoneJob($id))->onQueue('milestones-job');

        admin_success(__('milestone_rewards_synced'));
        return redirect()->back();
    }
}
