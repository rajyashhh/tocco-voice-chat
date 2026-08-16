<?php

namespace App\Admin\Services;



use App\Helpers\LogHelper;
use App\Helpers\UserLevelHelper;
use App\Models\Admin;
use Encore\Admin\Facades\Admin as Super;
use Modules\Vip\Entities\Vip;

class UserService
{
    public function adminUserAvatar($user, bool $withoutLevels = false, $showUrl = null): string
    {
        if (! $user) return __('No user');

        $uid = e($user->original_uuid);
        $special = e($user->uuid);

        $defaultImage = asset('images/businessman-icon.jpg');
        $path = $user->profile?->avatar; // prevent null crash

        $url = getImagePath($path) ?? $defaultImage;

        if (! isImageExists($url)) {
            $url = $defaultImage;
        }

        $image = handleShowImageWithTypes($user->id, $url, 50, 50);

        // Level-related data
        $levelImages = '';
        if (! $withoutLevels) {
            $receiverImg = getImagePath(UserLevelHelper::getReceiverImage($user));
            $senderImg   = getImagePath(UserLevelHelper::getSenderImage($user));

            foreach ([$receiverImg, $senderImg] as $img) {
                if (!empty($img)) {
                    $levelImages .= "<img src='{$img}' style='width:32px;height:14px;margin-right:2px;'>";
                }
            }
        }

        $rawName = $user->name ?? '';

        // remove NULL bytes and control chars
        $cleanName = preg_replace('/[\x00-\x1F\x7F]/u', '', $rawName);

        // now safely escape
        $name = htmlspecialchars($cleanName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $showUrl = $showUrl ?: $this->adminUserUrl($user->id);

        $flagHtml = '';
        if (!empty($user->country?->flag)) {
            $flagPath = getImagePath($user->country->flag);
            $flagTitle = app()->getLocale() === 'ar'
                ? e($user->country->name)
                : e($user->country->e_name);

            $flagHtml = "<img src='{$flagPath}' 
                        class='flag-image' 
                        alt='flag Image' 
                        title='{$flagTitle}' 
                        style='width:20px;height:auto;vertical-align:middle;margin-left:5px;'>";
        }
        return <<<HTML
        <a href="{$showUrl}" style="display:flex;align-items:center;gap:10px;padding:10px;text-decoration:none;color:inherit;">
            {$image}
            <div>
                <strong style="font-size:16px;">{$name}</strong>{$flagHtml}<br>
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

    /**
     * Enhanced user card with modern UI - reusable across all admin grids
     * Includes: avatar, name, country flag, UID (copy), special ID, level badges
     * CSS class prefix: 'auc-' (admin-user-card)
     */
    public function adminUserCard($user, bool $withoutLevels = false, ?string $showUrl = null): string
    {
        if (!$user) return '<span class="text-muted">' . __('No user') . '</span>';

        $defaultImage = asset('images/businessman-icon.jpg');
        $path = $user->profile?->avatar;
        $url = getImagePath($path) ?? $defaultImage;
        if (!isImageExists($url)) {
            $url = $defaultImage;
        }

        $rawName = preg_replace('/[\x00-\x1F\x7F]/u', '', $user->name ?? '');
        $name = e($rawName);
        $uid = e($user->original_uuid);
        $special = e($user->uuid);
        $userId = $user->id;
        $showUrl = $showUrl ?: $this->adminUserUrl($userId);

        // Country flag
        $flagHtml = '';
        if (!empty($user->country?->flag)) {
            $flagPath = getImagePath($user->country->flag);
            $flagTitle = app()->getLocale() === 'ar'
                ? e($user->country->name)
                : e($user->country->e_name);
            $flagHtml = "<img src='{$flagPath}' class='auc-flag' alt='flag' title='{$flagTitle}'>";
        }

        // Level badges
        $levelHtml = '';
        if (!$withoutLevels) {
            $receiverImg = getImagePath(UserLevelHelper::getReceiverImage($user));
            $senderImg = getImagePath(UserLevelHelper::getSenderImage($user));
            if ($receiverImg || $senderImg) {
                $levelHtml = '<div class="auc-levels">';
                if ($receiverImg) $levelHtml .= "<img src='{$receiverImg}' title='" . __('Receiver Level') . "'>";
                if ($senderImg) $levelHtml .= "<img src='{$senderImg}' title='" . __('Sender Level') . "'>";
                $levelHtml .= '</div>';
            }
        }

        return <<<HTML
        <a href="{$showUrl}" class="auc-card">
            <div class="auc-avatar">
                <img src="{$url}" class="auc-avatar-img" alt="{$name}" onerror="this.onerror=null;this.src='{$defaultImage}';">
            </div>
            <div class="auc-info">
                <div class="auc-name-row">
                    <span class="auc-name">{$name}</span>
                    {$flagHtml}
                </div>
                <div class="auc-meta">
                    <span class="auc-uid">
                        UID: <span id="auc-uid-{$userId}">{$uid}</span>
                        <button class="auc-copy" onclick="event.preventDefault();event.stopPropagation();copyToClipboard('auc-uid-{$userId}')" title="Copy">📋</button>
                    </span>
                    <span class="auc-uid">Special: {$special}</span>
                </div>
                {$levelHtml}
            </div>
        </a>
        HTML;
    }

    /**
     * Inject the CSS styles for adminUserCard (call once per page via Admin::style)
     */
    public static function adminUserCardStyles(): string
    {
        return '
            .auc-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 8px 12px;
                border-radius: 12px;
                background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
                border: 1px solid #e8ecf3;
                transition: all 0.3s ease;
                text-decoration: none;
                color: inherit;
            }
            .auc-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0,0,0,0.08);
                border-color: var(--primary-color, #667eea);
                text-decoration: none;
                color: inherit;
            }
            .auc-avatar { flex-shrink: 0; }
            .auc-avatar-img {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid #e0e5f0;
                transition: border-color 0.3s;
            }
            .auc-card:hover .auc-avatar-img {
                border-color: var(--primary-color, #667eea);
            }
            .auc-info { flex: 1; min-width: 0; }
            .auc-name-row {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }
            .auc-name {
                font-weight: 700;
                font-size: 14px;
                color: #1a1d29;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 180px;
            }
            .auc-flag {
                width: 20px;
                height: auto;
                vertical-align: middle;
                border-radius: 2px;
            }
            .auc-meta {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-top: 3px;
                flex-wrap: wrap;
            }
            .auc-uid {
                font-size: 11px;
                color: #8892a6;
                background: #f0f2f8;
                padding: 2px 8px;
                border-radius: 6px;
                font-family: monospace;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .auc-copy {
                background: none;
                border: none;
                cursor: pointer;
                font-size: 11px;
                color: var(--primary-color, #667eea);
                padding: 0;
                line-height: 1;
            }
            .auc-copy:hover { transform: scale(1.2); }
            .auc-levels {
                display: flex;
                gap: 3px;
                margin-top: 4px;
            }
            .auc-levels img {
                width: 34px;
                height: 15px;
            }
        ';
    }

    protected function adminUserUrl($id): string
    {
        if (Super::user()->type == "country") {
            return url("superadmin/users/profile/{$id}");
        } elseif (Super::user()->type == "region") {
            return url("areaManager/users/profile/{$id}");
        }
        return url("admin/users/{$id}");
    }
}
