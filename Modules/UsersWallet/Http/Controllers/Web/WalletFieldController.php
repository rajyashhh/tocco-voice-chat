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
use Modules\UsersWallet\Entities\WalletField;

class WalletFieldController extends MainController
{
    public $permission_name = 'wallet-fields';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Wallet Fields'))
            ->body($this->grid()));
    }

    public function show($id, Content $content)
    {
        $id = request()->route('id');

        return parent::show($id, $content
            ->title(__('Wallet Fields'))
            ->body($this->detail($id)));
    }

    public function edit($id, Content $content)
    {
        $id = request()->route('id');
        return parent::edit($id, $content
            ->title(__('Edit Wallet Fields'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__('Create Wallet Fields'))
            ->body($this->form()));
    }

    protected function grid()
    {
        $grid = new Grid(new WalletField());

        $walletTemplateId = request('wallet_template_id');
        $grid->model()->where('wallet_template_id', $walletTemplateId);

        $grid->column('id', __('ID'))->sortable();

        $grid->column('title', __('Name'))->display(function ($title) {
            if (is_array($title)) {
                return $title[app()->getLocale()] ?? reset($title);
            }
            return $title;
        });

        $grid->column('placeholder', __('Placeholder'))->display(function ($title) {
            if (is_array($title)) {
                return $title[app()->getLocale()] ?? reset($title);
            }
            return $title;
        });

        $grid->column('type', __('Type'));
        $grid->column('is_required', __('Required'))->bool();
        $grid->column('order', __('Order'));
        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(WalletField::findOrFail($id));

        $show->field('id', __('ID'));

        $show->field('title', __('Title'))->as(function ($title) {
            if (is_array($title)) {
                $html = '';
                foreach ($title as $code => $value) {
                    $html .= "<b>{$code}:</b> {$value}<br>";
                }
                return $html;
            }
            return $title;
        })->unescape();

        $show->field('placeholder', __('Placeholder'))->as(function ($placeholder) {
            if (is_array($placeholder)) {
                $html = '';
                foreach ($placeholder as $code => $value) {
                    $html .= "<b>{$code}:</b> {$value}<br>";
                }
                return $html;
            }
            return $placeholder;
        })->unescape();

        $show->field('type', __('Type'));
        $show->field('is_required', __('Required'))->as(function ($value) {
            return $value ? __('Yes') : __('No');
        });
        $show->field('order', __('Order'));
        $show->field('created_at', __('Created At'))->as(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        return $show;
    }

    protected function form()
    {
        $form = new Form(new WalletField());

        $walletTemplateId = request('wallet_template_id');

        $form->hidden('wallet_template_id')->default($walletTemplateId);

        $languages = Language::where('is_enabled', 1)->get();
        $form->fieldset(__('Name'), function (Form $form) use ($languages) {
            foreach ($languages as $lang) {
                $code = $lang->code;
                $name = $lang->name;

                $form->text("title.{$code}", __("Name")."($name)")->rules('nullable');
            }
        });

        $form->fieldset(__('Placeholder'), function (Form $form) use ($languages) {
            foreach ($languages as $lang) {
                $code = $lang->code;
                $name = $lang->name;
                $form->text("placeholder.{$code}", __("Placeholder")."($name)")->rules('nullable');
            }
        });

        $form->select('type', __('Type'))->options([
            'text' => __('text'),
            'number' => __('number'),
            'email' => __('email'),
        ])->rules('required');

        $form->switch('is_required', __('Required'))->states([
            'on' => ['value' => 1, 'text' => __('Yes'), 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => __('No'), 'color' => 'default'],
        ])->default(0);

        $form->number('order', __('Order'))->default(1)->rules('required|integer|min:1');

        $form->saving(function (Form $form) {
            $form->model()->setTranslations('title', request('title') ?? []);
            $form->model()->setTranslations('placeholder', request('placeholder') ?? []);
        });

        Admin::script('
            $(".collapse.in").removeClass("in"); // Bootstrap 3
            $(".collapse.show").removeClass("show"); // Bootstrap 4/5
        ');
        return $form;
    }

    public function store()
    {
        $form = $this->form();

        $form->saved(function (Form $form) {
            $walletTemplateId = $form->model()->wallet_template_id;
            admin_toastr(__('Created successfully'));
            return redirect()->to('admin/wallet-fields/' . $walletTemplateId);
        });

        return $form->store();
    }

    public function update($id)
    {
        $id = request()->route('id');
        $form = $this->form()->edit($id);

        $form->saved(function (Form $form) {
            $walletTemplateId = $form->model()->wallet_template_id;
            admin_toastr(__('Updated successfully'));
            return redirect()->to('admin/wallet-fields/' . $walletTemplateId);
        });

        return $form->update($id);
    }

    public function destroy($id)
    {
        $top = WalletField::findOrFail($id);
        $walletTemplateId  = $top->wallet_template_id;
        $top->delete();

        admin_toastr(__('Deleted successfully'));

        return [
            'status' => true,
            'message' => __('Deleted successfully'),
            'redirect' => admin_url('wallet-fields?wallet_template_id=' . $walletTemplateId),
        ];
    }
}
