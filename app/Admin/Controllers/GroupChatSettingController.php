<?php

namespace App\Admin\Controllers;

use App\Models\Config;
use Encore\Admin\Layout\Content;

class GroupChatSettingController extends MainController
{
    public $permission_name = 'updates_group_chat';

    public function index(Content $content)
    {
        $config = Config::whereIn('name', ['group_chat', 'max_message'])->get();

        $groupChat = $config->firstWhere('name', 'group_chat')->value ?? 0;
        $maxMessage = $config->firstWhere('name', 'max_message')->value ?? 3;
        return parent::index(
            $content->title(__('settings'))
                ->view('group_chat_settings', compact('groupChat', 'maxMessage'))
        );
    }
}
