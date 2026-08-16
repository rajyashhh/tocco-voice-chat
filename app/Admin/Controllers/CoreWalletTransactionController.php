<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\AdminUser;
use App\Models\CoreWallets;
use Encore\Admin\Layout\Content;
use App\Models\CoreWalletTransaction;
use Encore\Admin\Controllers\AdminController;

class CoreWalletTransactionController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    public $permission_name = 'core-wallet-transactions';

    protected $title = '';


    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('core wallet transactions'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->body($this->form()->edit($id)));
    }


    protected function grid()
    {
        $grid = new Grid(new CoreWalletTransaction());
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($from = request('from_date')) {
                        $start = Carbon::parse(convertArabicToEnglishNumbers($from))->startOfDay();
                        $query->whereDate('created_at', '>=', $start);
                    }
                }, __('From Date'), 'from_date')->date();

                $filter->where(function ($query) {
                    if ($to = request('to_date')) {
                        $end = Carbon::parse(convertArabicToEnglishNumbers($to))->endOfDay();
                        $query->whereDate('created_at', '<=', $end);
                    }
                }, __('To Date'), 'to_date')->date();
            });
        });

        $grid->model()->latest();

        $walletsLookup = CoreWallets::pluck('name', 'id');
        $adminIds = CoreWalletTransaction::distinct()->pluck('admin_id')->filter();
        $adminsLookup = AdminUser::whereIn('id', $adminIds)->pluck('name', 'id');

        $grid->column('id', __('#'));
        $grid->column('from_wallet', __('From Wallet'))->display(function ($val) use ($walletsLookup) {
            return ucfirst(str_replace('_', ' ', $walletsLookup->get($val, '')));
        });
        $grid->column('to_wallet', __('To Wallet'))->display(function ($val) use ($walletsLookup) {
            return ucfirst(str_replace('_', ' ', $walletsLookup->get($val, '')));
        });
        $grid->column('amount', __('coins'));
        $grid->column('admin_id', __('By'))->display(function ($val) use ($adminsLookup) {
            return $adminsLookup->get($val) ?? __('Unknown');
        });
        $grid->column('created_at', __('Transfer Time'))->display(function ($val) {
            return \Carbon\Carbon::parse($val)->format('Y-m-d H:i');
        })->sortable();
        $grid->disableCreateButton();
        $grid->disableActions();
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
        $show = new Show(CoreWalletTransaction::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('from_wallet', __('From wallet'));
        $show->field('to_wallet', __('To wallet'));
        $show->field('admin_id', __('Admin id'));
        $show->field('amount', __('Amount'));
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
        $form = new Form(new CoreWalletTransaction());

        $form->text('from_wallet', __('From wallet'));
        $form->text('to_wallet', __('To wallet'));
        $form->text('admin_id', __('Admin id'));
        $form->decimal('amount', __('Amount'));

        return $form;
    }
}
