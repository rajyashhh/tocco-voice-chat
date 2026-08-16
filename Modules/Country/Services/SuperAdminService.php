<?php

namespace Modules\Country\Services;



use App\Helpers\LogHelper;
use App\Helpers\UserLevelHelper;
use App\Models\Admin;
use Modules\Vip\Entities\Vip;

class SuperAdminService
{

    public function adminUserAvatar($user, bool $withoutLevels = false): string
    {
        if (! $user) {
            return __('No user');
        }

        // UUIDs
        $uid     = e($user->id ?? nullValue());
        $special = e($user->id ?? '-');

        // Avatar
        $defaultImage = asset('images/businessman-icon.jpg');
        $path = $user->avatar  ?? null; // 👈 fallback

        $url = getImagePath($path) ?? $defaultImage;
        if (! isImageExists($url)) {
            $url = $defaultImage;
        }

        $image = handleShowImageWithTypes($user->id ?? 0, $url, 50, 50);

        // Level-related data
        $levelImages = '';
        if (! $withoutLevels && method_exists(UserLevelHelper::class, 'getReceiverImage')) {
            $receiverImg = getImagePath(UserLevelHelper::getReceiverImage($user));
            $senderImg   = getImagePath(UserLevelHelper::getSenderImage($user));

            foreach ([$receiverImg, $senderImg] as $img) {
                if (!empty($img)) {
                    $levelImages .= "<img src='{$img}' style='width:32px;height:14px;margin-right:2px;'>";
                }
            }
        }

        // اسم المستخدم
        $rawName   = $user->username ?? '';
        $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', $rawName);
        $name      = htmlspecialchars($cleanName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
            <a href="{$this->adminUserUrl($user->id ?? 0)}" 
               style="display:flex;align-items:center;gap:10px;padding:10px;text-decoration:none;color:inherit;">
                {$image}
                <div>
                    <strong style="font-size:16px;">{$name}</strong><br>
                    <span style="font-size:13px;">
                        UID: <span id="uid-{$user->id}">{$uid}</span>
                        <button onclick="event.preventDefault();event.stopPropagation();copyToClipboard('uid-{$user->id}')"
                            style="background:none;border:none;cursor:pointer;margin-left:5px;font-size:13px;color:#007bff;"
                            title="Copy UID">📝</button>
                    </span><br>
                    <span style="font-size:13px;">Special: {$special}</span><br>
                    {$levelImages}
                </div>
            </a>
            HTML;
    }

    protected function adminUserUrl($id): string
    {
        $prefix = request()->segment(1); // admin / superadmin / areaManager
        return url("/{$prefix}/users/{$id}");
    }
}
