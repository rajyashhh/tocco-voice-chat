<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Charge;
use App\Http\Controllers\Controller;
use App\Models\User;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class ChargesDetailsController extends MainController
{
    use HasResourceActions;
    //public $permission_name = 'charge';
    public $hiddenColumns = [];
    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Charge Details'))
            ->body($this->grid()));
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Charge());
        $grid->disableRowSelector();

        $grid->model()->orderByDesc('created_at')->with(['sender', 'receiver']);


        $grid->model()->where('charger_type', "dash");
        $grid->filter(function (Grid\Filter $filter) {

            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('receiver.uuid', __("sendTo"));
            });
            // $filter->column(1/2, function (Grid\Filter $filter) {
            //     $filter->date(__('from_date'), __("From"));
            // });
            // $filter->between('created_at')->datetime();
            $filter->where(function ($query) {
                $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);
                $query->whereDate('created_at', '>=', $datt);
            }, __('from_date'), 'from_date')->date();

            $filter->where(function ($query) {
                $datt = \App\Helpers\UserCommon::arabicToEnglishNumbers($this->input);

                $query->whereDate('created_at', '<=', $datt);
            }, __('to_date'), 'to_date')->date();
        });
        $grid->column('id', __('id'));
        // $grid->column('charger_id', __("sender"))->display(function () {

        //     return @$this->admin_user->name . "<br>" . "#" . @$this->admin_user->id;
        // });
        $grid->column('user_id', __('sendTo'))->display(function ($recever) {
            if (!isset($this->receiver->name)) {
                return "not user found";
            }
            return @$this->receiver->name . "<br>" . "#" . @$this->receiver->uuid;
        });
        $grid->column('amount', __("amount"));

        $grid->column('created_at', __('created_at'))->display(function () {
            $timezone = auth()->user()?->time_zone ?? getTimezone();
            return \Carbon\Carbon::createFromTimestamp(strtotime($this->created_at))
                ->timezone($timezone)->format("Y-m-d h:i A");
        });
        $grid->disableActions();
        $grid->disableCreateButton();
        return $grid;
    }
}
