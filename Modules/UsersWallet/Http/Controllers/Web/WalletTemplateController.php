<?php

namespace Modules\UsersWallet\Http\Controllers\Web;

use App\Admin\Controllers\MainController;
use App\Models\Language;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\UsersWallet\Entities\WalletTemplate;

class WalletTemplateController extends MainController
{
    public $permission_name = 'wallet-template';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Wallet Template'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(__('Wallet Template'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__('Wallet Template'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('Wallet Template'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new WalletTemplate());

        $grid->column('id', __('ID'))->sortable();

        $grid->column('title', __('Name'))->display(function ($title) {
            if (is_array($title)) {
                return $title[app()->getLocale()] ?? reset($title);
            }
            return $title;
        });

        $grid->column('type', __('Type'))->using([
            'digital_wallet' => __('Digital Wallet'),
            'bank_account' => __('Bank Account'),
        ]);

        $grid->column('minimum', __('Minimum'))->display(function ($value) {
            return number_format($value, 2);
        });

        $grid->column('transfer_fee', __('Transfer Fee'))->display(function ($value) {
            return number_format($value, 2);
        });

        if (Admin::user()->can('browse-wallet-fields') || Admin::user()->can('*')) {
            $grid->column(__('Procedures'))->display(function () {
                $url = url('admin/wallet-fields/'.$this->id);
                $gifts = __('Wallet Fields');
                return "<a href='{$url}' class='btn btn-sm btn-info'>{$gifts}</a>";
            });
        }

        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(WalletTemplate::findOrFail($id));

        $show->field('id', __('ID'));

        $show->field('title', __('Title'))->as(function ($title) {
            if (is_array($title)) {
                $html = '';
                foreach ($title as $code => $value) {
                    $html .= "<b>$code:</b> $value<br>";
                }
                return $html;
            }
            return $title;
        })->unescape();

        $show->field('type', __('Type'))->as(function ($type) {
            return $type === 'digital_wallet'
                ? __('Digital Wallet')
                : __('Bank Account');
        });

        $show->field('minimum', __('Minimum'))->as(function ($value) {
            return number_format($value, 2);
        });

        $show->field('transfer_fee', __('Transfer Fee'))->as(function ($value) {
            return number_format($value, 2);
        });

        $show->field('created_at', __('Created At'))->as(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new WalletTemplate());

        $languages = Language::where('is_enabled', 1)->get();
        $form->fieldset(__('Name'), function (Form $form) use ($languages) {
            foreach ($languages as $lang) {
                $code = $lang->code;
                $name = $lang->name;

                $form->text("title.{$code}", __("Name")."($name)")->rules('nullable');
            }
        });

        $form->select('type', __('Type'))
            ->options([
                'digital_wallet' => __('Digital Wallet'),
                'bank_account' => __('Bank Account'),
            ])
            ->rules('required|in:digital_wallet,bank_account')
            ->when('bank_account', function (Form $form) {
                $form->decimal('transfer_fee', __('Transfer Fee'))
                    ->default(0.00)
                    ->rules('required|numeric|min:0');
            });

        $form->decimal('minimum', __('Minimum'))
            ->default(0.00)
            ->rules('required|numeric|min:0');

        Admin::script('
            $(".collapse.in").removeClass("in"); // Bootstrap 3
            $(".collapse.show").removeClass("show"); // Bootstrap 4/5
        ');

        return $form;
    }
}
