<?php

namespace Modules\SalaryTransaction\Http\Controllers;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\Emoji;
use App\Http\Controllers\Controller;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\SalaryTransaction\Entities\AdminCheck;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Collection;
use Modules\SalaryTransaction\Actions\AcceptAgentRequestAction;
use Modules\SalaryTransaction\Actions\AccepRequestAction;
use Modules\SalaryTransaction\Actions\CancelRequestAction;
use Modules\SalaryTransaction\Actions\RejectedAgentRequestAction;
use Modules\SalaryTransaction\Entities\AgentSalaryRequest;

class AgentRequestHistoryController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'agent-request-history';

    protected function grid()
    {
        $grid = new Grid(new AgentSalaryRequest());
        $grid->model()->where("status",1);
        $grid->filter (function (Grid\Filter $filter){
            $filter->disableIdFilter();
            $filter->column(1/2, function ($filter) {
                $filter->equal('type',__('طريقه الدفع'))->select([1=>__('coins'),2=>__('usd')]);

            });
        });
        $grid->column('id', __('Id'));
        $grid->column('agency.name',__("agency"));
        $grid->column('agent.name',__("name"));
        $grid->column('agent.uuid',__("Id"));
        $grid->column('type','طريقه الدفع')->display(function($q){
            return $this->type == 1 ? 'coins' : 'usd' ;
        });
        $grid->column('usd',__("amount usd"));
        $grid->column('coins',__("amount coins"));
        // $grid->column('payment_gateway.title',__("payment title"));
        // $grid->column('country.name',__("country"));
        $grid->disableCreateButton();
        
        
        $grid->disableActions ();
        return $grid;
    }
}