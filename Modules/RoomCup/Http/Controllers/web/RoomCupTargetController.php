<?php

namespace Modules\RoomCup\Http\Controllers\web;

use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;
use Modules\RoomCup\Entities\RoomCupTarget;

class RoomCupTargetController extends MainController
{
    protected $title = '';

    public $permission_name = 'room-cup-target';

    public function index(Content $content)
    {
        return parent::index($content
            ->header(__('Room Cup Targets'))
            ->description(__('Room Cup Targets'))
            ->row(function (Row $row) {
                $row->column(12, $this->grid2());
            })
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    protected function grid2()
    {
        return (new Box(
            title: __('admin.description'),
            content: view('admin.grid.roomTarget'),
        ));
    }
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Room Cup Targets'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('Room Cup Targets'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Room Cup Targets'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new RoomCupTarget());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('total', __('Total'))->display(function ($coins) {
            $image = asset('images/coin.jpg');
            return "<div style='display: flex; align-items: center;'>

                        <span>{$coins}</span>
                         <img src='{$image}' alt='Coins' width='20' height='20'>
                    </div>";
        });
        $grid->column('number_of_visitors', __('Number of Visitors'))->sortable();
        $grid->column('number_of_admins', __('Number of Admins'))->sortable();
        $grid->column('owner_profit', __('Owner Profit'))->display(function ($coins) {
            $image = asset('images/coin.jpg');
            return "<div style='display: flex; align-items: center;'>

                        <span>{$coins}</span>
                         <img src='{$image}' alt='Coins' width='20' height='20'>
                    </div>";
        });
        $grid->column('admin_profit', __('Admin Profit'))->display(function ($coins) {
            $image = asset('images/coin.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center;'>

                        <span>{$coins}</span>
                         <img src='{$image}' alt='Coins' width='20' height='20'>
                    </div>";
        });

        $grid->filter(function ($filter) {
            $filter->between('total', __('Total'));
            $filter->between('number_of_visitors', __('Visitors Count'));
            $filter->between('number_of_admins', __('admins'));
        });
        $this->extendGrid($grid);
        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(RoomCupTarget::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('total', __('Total'));
        $show->field('number_of_visitors', __('Number of Visitors'));
        $show->field('number_of_admins', __('Number of Admins'));
        $show->field('owner_profit', __('Owner Profit'));
        $show->field('admin_profit', __('Admin Profit'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new RoomCupTarget());

        $form->number('total', __('Target of Room Diamond (Total)'))->default(0)
            ->help(__('TargetDiamond'));
        $form->number('number_of_visitors', __('Condition for Room Growth (Num of Visitors)'))->default(0)
            ->help(__('ConditionGroeth'));
        $form->number('number_of_admins', __('Number of Admin Seats (Num of Admins)'))
            ->default(0)
            ->rules([
                function ($attribute, $value, $fail) {
                    $limit = Common::getConfig('max_room_admin') ?? 4;
                    if ($value < $limit) {
                        $fail(__('api.admins_greater_than', ['limit' => $limit]));
                    }
                },
            ])->help(__('NumberSeats'));

        $form->number('total_profit', __('total profit'))->default(0)->help(__('ProfitsTargetTotal'));
        $form->decimal('owner_percentage', __('Owner Profit Percentage (Owner Profit %)'))->default(0.00)->help(__('Specifies the percentage of the total profit allocated to the room owner.'));
        $form->decimal('admin_percentage', __('Admin Profit Percentage (Admin Profit %)'))->default(0.00)->help(__('Specifies the percentage of the total profit distributed among all admins.'));

        $form->saving(function (Form $form) {
            $ownerPercentage = request('owner_percentage');
            $adminPercentage = request('admin_percentage');
            $total = $adminPercentage + $ownerPercentage;
            if ($total != 100) {
                $error = new \Illuminate\Support\MessageBag([
                    'title' => 'Error',
                    'message' => trans('admin.percent_total_error', ['total' => $total]),
                ]);

                return back()->with(compact('error'))->withInput();
            }

            $form->model()->owner_profit = $form->total_profit * ($ownerPercentage / 100);
            $form->model()->admin_profit = $form->total_profit * ($adminPercentage / 100);
        });
        return $form;
    }

    public function cupTargetHtml(Request $request)
    {
        $lang = $request->get('lang', 'en');
        app()->setLocale($lang);
        $cupTargets = RoomCupTarget::get();
        return view('cupTarget', compact("cupTargets"));
    }
}
