<?php

namespace App\Classes;

use App\Helpers\Common;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use App\Models\ChatSetting as ChatSettingModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ChatSetting
{


    private $packIds;
    private $user;
    private $packs;
    private $wares;
    private $data;
    private $userOVipLevel;
    private $chat_setting;
    public function __construct(User $user, array $data,$chat_setting)
    {
        $this->user = $user;
        $this->data = $data;
        $this->chat_setting = $chat_setting;
    }

    public function getData(): array
    {
        $data = [];

        foreach ($this->data as $key => $value) {
            $data[]    = [
                'key' => $key,
                'title' => __('api.' . $key . '_title'),
                'description' => $this->getDescription($key, $this->chat_setting->$key),
                'is_active' => ($this->chat_setting->$key == 1? true : false ),
            ];
        }

        return $data;
    }

    private function getDescription(string $key, $isAllow)
    {
        if ($isAllow) return __('api.' . $key . '_description_allow');

        return __('api.' . $key . '_description');
    }
}
