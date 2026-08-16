<?php

namespace Modules\SalaryTransaction\Http\Controllers;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\Emoji;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use Modules\SalaryTransaction\Actions\AcceptAgentRequestAction;
use Modules\SalaryTransaction\Actions\RejectedAgentRequestAction;
use Modules\SalaryTransaction\Entities\AgentSalaryRequest;

class AgentRequestController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'internal-sales-system-report';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('report'))
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
            ->title(trans('agent-salary-requests'))
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
            ->title(trans('agent-salary-requests'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('agent-salary-requests'))
            ->body($this->form()));
    }


    protected function grid()
    {
        $grid = new Grid(new AgentSalaryRequest());
        $countryID = Common::filterCountryIds();

        $grid->model()
            ->with([
                'agency',
                'agent',
                'agent.profile',
                'agent.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ])
            ->when($countryID, fn($q) =>
            $q->where(function ($q) use ($countryID) {
                $q->whereHas('agent', fn($q) => $q->whereIn('country_id', $countryID))
                    ->orWhereHas('agency', fn($q) => $q->whereIn('country_id', $countryID));
            }))
            ->where("status", 0);
        $grid->column('id', __('Id'));
        $grid->column('agency.name', __("agency"))->display(function ($name) {
            $path = @$this->agency->img;
            $defaultImage = asset("images/icon-agency.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            if ($this->agency) {
                $showUrl = url("admin/agencies/{$this->agency->id}");
                $link = "
                    <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                        <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                    </a>
                ";
            } else {
                $link = "<span style='color: gray;'>No Agency</span>"; // Handle missing agency
            }

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    $link
                </div>
            ";
        });
        $grid->column('agent.name', __("name"))->display(function ($name) {
            $name = $name ?? '';
            $uid = @$this->agent->uuid;
            $path = @$this->agent?->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = url("admin/users/{$this->agent->id}");
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                    </div>
                </div>
            ";
        });;
        $grid->column('type', __('Payment method'))->display(function ($q) {
            return $this->type == 1 ? __('coins') : 'usd';
        });
        $grid->column('usd', __('Usd'))->display(function ($usd) {
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center;'>

                        <span>{$usd}</span>
                          <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });

        $grid->column('coins', __('Coins'))->display(function ($coins) {
            $image = asset('images/coin.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center;'>

                        <span>{$coins}</span>
                         <img src='{$image}' alt='Coins' width='20' height='20'>
                    </div>";
        });
        // $grid->column('payment_gateway.title',__("payment title"));
        // $grid->column('country.name',__("country"));
        $grid->disableCreateButton();
        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            $actions->disableEdit();
            $actions->disableDelete();
            if (Admin::user()->can('accept-request-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new AcceptAgentRequestAction());
            }
            if (Admin::user()->can('rejected-request-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new RejectedAgentRequestAction());
            }
        });
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append('<a href="' . url('/admin/agent-requests-history') . '"  class="btn btn-sm btn-success">' . __('admin.history') . '</a>');
        });

        return $grid;
    }

    // protected function detail($id)
    // {

    //     $show = new Show(Emoji::findOrFail($id));

    //     $show->id(__('admin.ID'));
    //     $show->pid('pid');
    //     $show->name('name');
    //     $show->emoji('emoji');
    //     $show->t_length('t_length');
    //     $show->enable('enable');
    //     $show->sort('sort');
    //     $this->extendShow ($show);
    //     return $show;
    // }

    protected function detail($id)
    {
        $show = new Show(AgentSalaryRequest::findOrFail($id));

        $show->id(__('Id'));
        $show->field('agency.name', __('Agency'));
        $show->field('agent.name', __('Agent Name'));
        $show->field('agent.uuid', __('Agent ID'));
        $show->field('type', __('Payment Method'))->as(function ($type) {
            return $type == 1 ? __('Coins') : 'USD';
        });
        $show->field('usd', __('USD'));
        $show->field('coins', __('Coins'));
        $show->field('created_at', __('Created At'));

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
        $form = new Form(new Emoji);

        $form->display(__('admin.ID'));
        $form->select('pid', __('pid'))->options(function () {
            $ops = [0 => 'root'];
            $ps = Emoji::query()->where('enable', 1)->where('pid', 0)->where('id', '!=', $this->id)->get();
            foreach ($ps as $p) {
                $ops[$p->id] = $p->name;
            }
            return $ops;
        });
        $form->text('name', __('name'));
        $form->file('emoji', __('emoji'));
        $form->number('t_length', __('t_length'));
        $form->switch('enable', __('enable'))->states(Common::getSwitchStates());
        $form->number('sort', __('sort'));

        return $form;
    }
}
