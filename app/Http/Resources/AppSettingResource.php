<?php

namespace App\Http\Resources;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class AppSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $user = $this;

        $chatStatus = settings()->get('chat_status');
        $showChat = $user->userSetting?->hide_chat ?? $chatStatus;
        $stopInviteCode = getSettingCash('invite_code') ?? 0;

        $inviteCode = false;
        if ($stopInviteCode == 1) {
            $inviteCode = true;
        } elseif ($user->userSetting && $user->userSetting->show_invite_code == 1) {
            $inviteCode = true;
        }

        return [
            'version' => [
                'android_version' => settings()->get('android_current_version'),
                'ios_version' => settings()->get('ios_current_version'),
                'huawei_version' => settings()->get('huawei_current_version'),
            ],
            'hide_invite' => $inviteCode,
            'show_chat' => ($chatStatus == null ? false : ($showChat == 0 ? false : true)),
            'shared_key' => Common::getConfig('shared') ?? '1234',
            'stop_transfer_salary' => settings()->get('transfer_salary') == 0 ? $user->transfer_salary : (settings()->get('transfer_salary') == 1 ? true : false),
            'have_pending_request' => $this->salaryRequests->isNotEmpty(),
            'group_ban' => (bool) $this->bans->first(),
        ];
    }
}
