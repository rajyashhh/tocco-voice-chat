<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Config;
use App\Models\Language;
use App\Models\PaymentGateway;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Form;

use Request;

class MangerSettingController extends MainController
{
    public $permission_name = 'updates_group_chat';
    public $permission_setting = 'agency-manger-setting';



    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_setting);
        }

        $config = Config::where('name', 'system_default_manger')->first();
        $configAll = Config::all();
        $languages = Language::all();
        $configValue = $config->value ?? '';

        $payment_gateways = PaymentGateway::all();
        return  $content
            ->title(title: trans('Payment Gateways'))
            ->view('mangerSetting', compact('config', 'configValue', 'languages', 'configAll', 'payment_gateways'));
    }

    public function deletePaymentGateway($id)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('delete-' . $this->permission_setting);
        }

        $result = PaymentGateway::findOrFail($id);
        $result->delete();
        return back();
    }
    public function editPaymentGateway($id, Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_setting);
        }

        $gateway = PaymentGateway::findOrFail($id);

        return parent::edit($id, $content
            ->body($this->editForm()->edit($id)));
        // view('paymentGatewayEdit', compact('gateway'));
    }

    protected function editForm()
    {
        $form = new Form(new PaymentGateway);
        $this->disableFormTools($form);

        // $form->setMethod('PUT');
        $form->setAction(route('admin.update-payment-gateway', ['id' => request()->route('id')]));

        $form->display(__('ID'));
        $form->text('title', __('title'));
        $form->image('photo', trans('image'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        });

        return $form;
    }

    public function updatePaymentGateway($id)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_setting);
        }

        $gateway = PaymentGateway::findOrFail($id);

        request()->validate([
            'title' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $title = request('title');

        if (request()->hasFile('photo')) {
            $uploaded = Common::upload('images', request('photo'));
            $gateway->update([
                'photo' => $uploaded
            ]);
        }
        $gateway->update([
            'title' => $title
        ]);

        return redirect('/admin/agency-setting-manger');
    }

    public function createPaymentGateway(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_setting);
        }

        return $content
            ->title(title: trans('Payment Gateways'))
            ->body($this->form());
        // ->view('paymentGatewayCreate');
    }
    protected function form()
    {
        $form = new Form(new PaymentGateway);
        $form->setAction(route('admin.store-payment-gateway'));

        $form->display(__('ID'));
        $form->text('title', __('title'));
        $form->image('photo', trans('image'))->name(function ($file) {
            return now()->timestamp . rand(0, 999) . '.' . $file->guessExtension();
        });

        return $form;
    }

    public function storePaymentGateway()
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_setting);
        }

        request()->validate([
            'title' => ['required', 'string', 'max:255'],
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $title = request('title');
        $photo = Common::upload('images', request('photo'));

        PaymentGateway::create([
            'title' => $title,
            'photo' => $photo
        ]);

        return redirect('/admin/agency-setting-manger');
    }
}
