<?php

namespace Modules\SalaryTransaction\Http\Controllers;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\Emoji;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
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
use Modules\SalaryTransaction\Entities\ChargeCountry;

class ChargeCountryController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'salary-payment-countries';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('charge-country'))
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
            ->title(trans('charge-country'))
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
            ->title(trans('charge-country'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('charge-country'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new ChargeCountry());
        $countryID = Common::filterCountryIds();

        $grid->model()->with(['country'])->when($countryID, fn($q) => $q->whereHas('country', fn($q) => $q->whereIn('id', $countryID)));

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->orWhereHas('country', function ($q) use ($input) {
                        $q->where('name', 'like', "%$input%")
                            ->orWhere('e_name', 'like', "%$input%");
                    });
                }, __('name'));
            });
        });

        $grid->id(__('Id'));
        $grid->column('country.name', trans('arabic name'));
        $grid->column('country.e_name', trans('english name'));
        $grid->column('country.phone_code', trans('phone code'));
        if (!request()->filled('_export_')) {
            $grid->column('country.flag', __('flag'))
                ->display(function ($value) {
                    if (request()->filled('_export_')) {
                        return '=IMAGE("' . getImagePath($value) . '","flag",1)';
                    }

                    $url = getImagePath($value);
                    return "<img src=\"{$url}\" style=\"max-height:30px\" class=\"img img-thumbnail\" />";
                });
        }

        $this->extendGrid($grid);
        $grid->actions(function ($actions) {
            $actions->disableView();
        });


        return $grid;
    }

    // protected function detail($id)
    // {
    //     $show = new Show(ChargeCountry::findOrFail($id));

    //     $show->id(__('admin.ID'));
    //     $show->pid('pid');
    //     $show->name('name');
    //     $show->emoji('emoji');
    //     $show->t_length('t_length');
    //     $show->enable('enable');
    //     $show->sort('sort');
    //     $this->extendShow($show);
    //     return $show;
    // }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ChargeCountry);
        $this->disableFormTools($form);

        $form->display(__('admin.ID'));
        if (!$form->isEditing()) {
            $form->select('country_id', __('country'))->options(function () {
                $ps = Country::query()->where('status', 1)->WhereDoesntHave('chargeCountry')->get();
                $ops = [];
                foreach ($ps as $p) {
                    $ops[$p->id] = app()->getLocale() === 'ar' ? $p->name : $p->e_name;
                }
                return $ops;
            })->rules(function ($form) {
                if (!$id = $form->model()->id) {
                    return 'unique:charge_countries,country_id';
                }
            });
        } else {

            $form->select('country_id', __('Country'))
                ->options(function () use ($form) {
                    $ps = Country::query()
                        ->where(function ($q) use ($form) {
                            $q->whereDoesntHave('chargeCountry')
                                ->orWhere('id', $form->model()->country_id); // include current country
                        })
                        ->get();

                    foreach ($ps as $p) {
                        $ops[$p->id] = app()->getLocale() === 'ar' ? $p->name : $p->e_name;
                    }

                    return $ops;
                })
                ->rules(function ($form) {
                    if (!$id = $form->model()->id) {
                        return 'unique:charge_countries,country_id';
                    }
                });
        }

        Admin::script(<<<'JS'
            $(document).on('pjax:start', function () {
                $('.select2-container--open').each(function () {
                    $(this).remove();
                });
            });
        JS);

        return $form;
    }
}
