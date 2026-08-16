<?php

namespace App\Admin\Controllers;

use App\Models\Config;
use Encore\Admin\Form;
use App\Models\Setting;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Auth\Permission;


class InvitationSettingsController extends AdminController
{
    protected $title = 'setting';

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . 'invitation-code-setting');
        }

        $content = $content->title(trans('Invitation Code Settings'));

        $content = $content->body($this->form());


        return $content;
    }

    public function store()
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . 'invitation-code-setting');
        }

        return $this->form()->store();
    }

    // protected function grid()
    // {
    //     $stop_invite_code = settings()->get('stop_invite_code');

    //     return (new Box(
    //         title: __('admin.Actions'),
    //         content: view('admin.grid.users.invitationCodeStop', compact(['stop_invite_code'])),
    //     ));
    // }



    protected function form()
    {
        $form = new Form(new Config());
        Admin::style('.box-header { display: none !important; }');
        Admin::style('
            [dir="rtl"] .form-horizontal .form-group,
            .rtl .form-horizontal .form-group {
                display: flex;
                flex-direction: row-reverse;
                flex-wrap: wrap;
                align-items: flex-start;
            }
            [dir="rtl"] .form-horizontal .control-label,
            .rtl .form-horizontal .control-label {
                text-align: right !important;
            }
            [dir="rtl"] .form-horizontal .help-block,
            .rtl .form-horizontal .help-block {
                text-align: right;
            }
            [dir="rtl"] .form-horizontal .box-footer,
            .rtl .form-horizontal .box-footer {
                text-align: right;
            }
        ');
        $form->setAction(admin_url('invitation-code/settings'));

        // Invitation Tab
        $form->tab(__('percentage invitation code'), function (Form $form) {
            $form->decimal('value', __('invitation.referrer_commission_percentage'))
                ->default($this->getValue('earn_from_invitation'))
                ->rules('required|numeric|min:0|max:100')
                ->help(__('invitation.referrer_commission_percentage_help'));

            $form->hidden('name')->default('earn_from_invitation');

            $form->decimal('host_reward', __('invitation.host_reward'))
                ->default($this->getValue('invitation_host_reward'))
                ->rules('required|numeric|min:0')
                ->help(__('invitation.host_reward_help'));

            $form->decimal('invitee_reward', __('invitation.invitee_reward_once'))
                ->default($this->getValue('invitation_invitee_reward'))
                ->rules('required|numeric|min:0')
                ->help(__('invitation.invitee_reward_once_help'));

            $form->decimal('invitation_code_date', __('invitation.commission_duration_days'))
                ->default($this->getValue('invitation_code_date', 0))
                ->rules('required|numeric|min:0')
                ->help(__('invitation.commission_duration_days_help'));

            $form->decimal('invitation_withdrawal_limit', __('invitation.withdrawal_limit'))
                ->default($this->getValue('invitation_withdrawal_limit', 50000))
                ->rules('required|numeric|min:0')
                ->help(__('invitation.withdrawal_limit_help'));
        });

        // General Tab — نص صفحة شروط الدعوة الذي يظهر داخل شاشة الدعوة في التطبيق
        $form->tab(__('invitation code setting'), function (Form $form) {


            $content = $this->getSettingValue('invitation_content_ar');

            // Replace only if it's a string (always true here)
            if (is_string($content)) {
                $content = str_replace('search_string', 'replacement_string', $content);
            }

            $form->textarea('content', __('invitation.rules_text_ar'))
                ->default($content)
                ->help(__('invitation.rules_text_ar_help'));

            $content_en = $this->getSettingValue('invitation_content_en');

            if (is_string($content_en)) {
                $content_en = str_replace('search_string', 'replacement_string', $content_en);
            }

            $form->textarea('content_en', __('invitation.rules_text_en'))
                ->default($content_en)
                ->help(__('invitation.rules_text_en_help'));
        });

        $form->saving(function (Form $form) {
            Config::updateOrCreate(['name' => 'earn_from_invitation'], ['value' => $form->value]);
            Config::updateOrCreate(['name' => 'invitation_host_reward'], ['value' => $form->host_reward]);
            Config::updateOrCreate(['name' => 'invitation_invitee_reward'], ['value' => $form->invitee_reward]);
            Config::updateOrCreate(['name' => 'invitation_code_date'], ['value' => $form->invitation_code_date]);
            Config::updateOrCreate(['name' => 'invitation_withdrawal_limit'], ['value' => $form->invitation_withdrawal_limit]);

            Setting::updateOrCreate(['key' => 'invitation_content_ar'], ['value' => $form->content]);
            Setting::updateOrCreate(['key' => 'invitation_content_en'], ['value' => $form->content_en]);


            admin_toastr(__('invitation.saved_successfully'), 'success');
            return back();
        });

        return $form;
    }




    private function getValue(string $key, $default = 0)
    {
        return Config::where('name', $key)->value('value') ?? $default;
    }

    private function getSettingValue(string $key, $default = '')
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }




    public function inviteCode(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . 'invitation-code-setting');
        }

        $config = Config::whereIn('name', [
            'earn_from_invitation',
            'invitation_host_reward',
            'invitation_invitee_reward',
            'invitation_code_date',
            'invitation_withdrawal_limit',
        ])->pluck('value', 'name')->toArray();
        return $content->view('inviteCode', [
            'config' => $config,

        ]);
    }
}
