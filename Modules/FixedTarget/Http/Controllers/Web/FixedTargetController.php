<?php

namespace Modules\FixedTarget\Http\Controllers\Web;

use App\Admin\Controllers\MainController;
use App\Models\Target;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Modules\FixedTarget\Entities\FixedTarget;

class FixedTargetController extends MainController
{
    public $permission_name = 'fixed-target';


    public function grid()
    {
        $grid = new Grid(new FixedTarget());
        $grid->model()->orderBy('usd');

        $grid->id( __ ('ID'));

        $grid->diamonds(__('diamonds'))->display(function($column, Grid\Column $value) {
            $value = $value->getOriginal();
            return number_format($value);
        })->editable ();
        $grid->usd(__('usd'))->editable ();

        $grid->hours(__('hours'))->editable ();
        $grid->days(__('days'))->editable ();
        $grid->count_real(__('Count Real'))->editable();
        $grid->count_moment(__('Count Moment'))->editable();

        $grid->agency_share(__('agency share').'(%)')->display(function($column, Grid\Column $value) {
            $value = $value->getOriginal();
            return number_format($value, 2);
        })->editable ();
        $this->extendGrid ($grid);
        $grid->disableExport();
        $grid->tools(function (Grid\Tools $tools) {
            $tools->append('<a href="' . url('admin/special-users') . '" class="btn btn-sm btn-success">Special Users</a>');
        });
        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new FixedTarget);

        $form->display(__ ('ID'));
        $form->number('diamonds', __('diamonds'));
        $form->decimal('usd', __('usd'));
        $form->number('hours', __('hours'));
        $form->number('days', __('days'));
        $form->number('count_moment', __('Count Moment'));
        $form->number('count_real', __('Count Real'));
        $form->decimal('agency_share', __('agency share').'(%)');


        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(FixedTarget::findOrFail($id));

        $show->id('ID');
        $show->diamonds('diamonds');
        $show->hours('hours');
        $show->days('days');
        $this->extendShow ($show);
        return $show;
    }

}
