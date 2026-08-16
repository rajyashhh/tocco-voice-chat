<?php

namespace Modules\TribeReward\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\TribeReward\Entities\TribePeriod;
use Modules\TribeReward\Entities\TribeTop;
use function request;

class TribeTopController extends MainController
{
    public $permission_name = 'tribe-tops';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Tribe Tops'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__('Tribe Top'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        return parent::edit($id, $content
            ->title(__('Edit Tribe Top'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('Create Tribe Top'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new TribeTop());

        $tribe_period_id = request('tribe_period_id');
        $grid->model()->where('tribe_period_id', $tribe_period_id);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('tribe_period_id', __('Tribe Period'))->display(function ($pid) {
            $period = TribePeriod::find($pid);
            return $period
                ? (Carbon::parse($period->start_date)->format('Y-m-d') . ' ~ ' .
                    Carbon::parse($period->end_date)->format('Y-m-d'))
                : '-';
        });
        $grid->column('min', __('min'));
        $grid->column('max', __('max'));
        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        if (Admin::user()->can('browse-tribe-rewards') || Admin::user()->can('*')) {
            $grid->column(__('Procedures'))->display(function () {
                $url = url('admin/tribe_rewards/' . $this->id);
                $text = __('Tribe Rewards');
                return "<a href='{$url}' class='btn btn-sm btn-info'>{$text}</a>";
            });
        }
        if (method_exists($this, 'extendGrid')) {
            $this->extendGrid($grid);
        }

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(TribeTop::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('tribe_period_id', __('Tribe Period'))->as(function ($pid) {
            $period = TribePeriod::find($pid);
            return $period
                ? (Carbon::parse($period->start_date)->format('Y-m-d') . ' ~ ' .
                    Carbon::parse($period->end_date)->format('Y-m-d'))
                : '-';
        });
        $show->field('min', __('min'));
        $show->field('max', __('max'));
        $show->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });
        $show->column('updated_at', __('Updated At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new TribeTop());

        $tribe_period_id = request('tribe_period_id');

        $form->hidden('tribe_period_id')->default($tribe_period_id);

        $form->number('min', __('min'))->required();
        $form->number('max', __('max'))->required();

        $form->saving(function ($form) {
            $exists = TribeTop::where('tribe_period_id', $form->tribe_period_id)
                ->where(function ($q) use ($form) {
                    $q->where('min', '<=', $form->max)
                        ->where('max', '>=', $form->min);
                });

            if ($form->model()->id) {
                $exists->where('id', '!=', $form->model()->id);
            }

            if ($exists->exists()) {
                $error = __('There is already a top with an overlapping range for this period!');
                admin_error($error);
                return back()->withInput();
            }
        });

        return $form;
    }

    public function store()
    {
        $form = $this->form();

        $form->saved(function (Form $form) {
            $tribe_period_id = $form->model()->tribe_period_id;
            admin_toastr(__('Created successfully'));
            return redirect()->to('admin/tribe_tops/' . $tribe_period_id);
        });

        return $form->store();
    }

    public function update($id)
    {
        $id = request()->route('id');
        $form = $this->form()->edit($id);

        $form->saved(function (Form $form) {
            $tribe_period_id = $form->model()->tribe_period_id;
            admin_toastr(__('Updated successfully'));
            return redirect()->to('admin/tribe_tops/' . $tribe_period_id);
        });

        return $form->update($id);
    }

    public function destroy($id)
    {
        $top = TribeTop::findOrFail($id);
        $tribe_period_id = $top->tribe_period_id;
        $top->delete();

        admin_toastr(__('Deleted successfully'));

        return [
            'status' => true,
            'message' => __('Deleted successfully'),
            'redirect' => admin_url('tribe_tops?tribe_period_id=' . $tribe_period_id),
        ];
    }
}
