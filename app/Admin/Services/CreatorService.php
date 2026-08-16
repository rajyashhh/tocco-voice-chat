<?php

namespace App\Admin\Services;

use App\Models\AdminUser;

class CreatorService
{
    /**
     *
     * @param  mixed  $creatorModelOrIdOrArray
     * @param  bool   $showUid
     * @param  string|null $showUrl
     * @return string
     */
    // public function show($creatorModelOrIdOrArray, bool $showUid = true, string $showUrl = null): string
    // {
    //     if (!$creatorModelOrIdOrArray) {
    //         return __('No creator');
    //     }

    //     if (is_array($creatorModelOrIdOrArray)) {
    //         $creatorId = $creatorModelOrIdOrArray['id'] ?? null;
    //         if (!$creatorId) return __('No creator');
    //         $creator = AdminUser::find($creatorId);
    //     } elseif (is_numeric($creatorModelOrIdOrArray)) {
    //         $creator = AdminUser::find($creatorModelOrIdOrArray);
    //     } else {
    //         $creator = $creatorModelOrIdOrArray;
    //     }

    //     if (!$creator) return __('No creator');

    //     $uid =  $creator->id;
    //     $name = htmlspecialchars($creator->username ?? 'Unknown', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    //     $url = getImagePath($creator?->avatar);
    //     $url = $creator?->avatar;

    //     $url = $url ?: asset('images/businessman-icon.jpg');


    //     $image = "<img src='{$url}' alt='{$name}' style='width:50px;height:50px;border-radius:50%;object-fit:cover;'>";

    //     $showUrl = $showUrl ?: $this->creatorUrl($creator->id, $creator?->type);

    //     $uidHtml = $showUid ? "UID: <span id='uid-{$creator->id}'>{$uid}</span>
    //         <button onclick=\"event.preventDefault();event.stopPropagation();copyToClipboard('uid-{$creator->id}')\"
    //             style='background:none;border:none;cursor:pointer;margin-left:5px;font-size:13px;color:#007bff;' title='Copy UID'>📝</button>"
    //         : '';

    //     return <<<HTML
    //     <a href="{$showUrl}" style="display:flex;align-items:center;gap:10px;padding:5px;text-decoration:none;color:inherit;">
    //         {$image}
    //         <div>
    //             <strong style="font-size:16px;">{$name}</strong><br>
    //             <span style="font-size:13px;">{$uidHtml}</span>
    //         </div>
    //     </a>
    //     HTML;
    // }

    public function show($creatorModelOrIdOrArray, bool $showUid = true, string $showUrl = null): string
    {
        // If $creatorModelOrIdOrArray is already a model, use it directly
        if ($creatorModelOrIdOrArray instanceof \App\Models\AdminUser) {
            $creator = $creatorModelOrIdOrArray;
        } elseif (is_array($creatorModelOrIdOrArray) && isset($creatorModelOrIdOrArray['id'])) {
            // If an array is passed, assume creator model is loaded elsewhere
            $creator = $creatorModelOrIdOrArray['model'] ?? AdminUser::find($creatorModelOrIdOrArray['id']);
        } elseif (is_numeric($creatorModelOrIdOrArray)) {
            // If just ID is passed, we need to fetch it
            $creator = AdminUser::find($creatorModelOrIdOrArray);
        } else {
            return __('No creator');
        }

        if (!$creator) return __('No creator');

        $uid = $creator->id;
        $name = htmlspecialchars($creator->username ?? 'Unknown', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $url = $creator?->avatar ? getImagePath($creator?->avatar) : asset('images/businessman-icon.jpg');;
        $image = "<img src='{$url}' alt='{$name}' style='width:50px;height:50px;border-radius:50%;object-fit:cover;'>";

        $showUrl = $showUrl ?: $this->creatorUrl($creator->id, $creator?->type);

        $uidHtml = $showUid ? "UID: <span id='uid-{$creator->id}'>{$uid}</span>
        <button onclick=\"event.preventDefault();event.stopPropagation();copyToClipboard('uid-{$creator->id}')\"
            style='background:none;border:none;cursor:pointer;margin-left:5px;font-size:13px;color:#007bff;' title='Copy UID'>📝</button>"
            : '';

        return <<<HTML
    <a href="{$showUrl}" style="display:flex;align-items:center;gap:10px;padding:5px;text-decoration:none;color:inherit;">
        {$image}
        <div>
            <strong style="font-size:16px;">{$name}</strong><br>
            <span style="font-size:13px;">{$uidHtml}</span>
        </div>
    </a>
    HTML;
    }





    public function showV2($creatorInput, bool $showUid = true, ?string $showUrl = null): string
    {
        if (!$creatorInput) {
            return __('No creator');
        }

        if ($creatorInput instanceof AdminUser) {
            $creator = $creatorInput;
        } elseif (is_numeric($creatorInput)) {
            $creator = $this->creator ?? null;
        } elseif (is_array($creatorInput)) {
            $creator = (object) $creatorInput;
        } else {
            return __('No creator');
        }

        if (empty($creator->id)) {
            return __('No creator');
        }

        $uid = $creator->id;
        $name = e($creator->username ?? 'Unknown');

        $avatarUrl = $creator->avatar ?: asset('images/businessman-icon.jpg');

        $imageHtml = <<<HTML
        <img src="{$avatarUrl}" alt="{$name}"
             style="width:50px;height:50px;border-radius:50%;object-fit:cover;">
        HTML;

        $showUrl = $showUrl ?: $this->creatorUrl($uid, $creator?->type);

        $uidHtml = $showUid
            ? <<<HTML
            UID: <span id="uid-{$uid}">{$uid}</span>
            <button onclick="event.preventDefault();event.stopPropagation();copyToClipboard('uid-{$uid}')"
                style="background:none;border:none;cursor:pointer;margin-left:5px;font-size:13px;color:#007bff;"
                title="Copy UID">📝</button>
        HTML
            : '';

        return <<<HTML
        <a href="{$showUrl}"
           style="display:flex;align-items:center;gap:10px;padding:5px;text-decoration:none;color:inherit;">
            {$imageHtml}
            <div>
                <strong style="font-size:16px;">{$name}</strong><br>
                <span style="font-size:13px;">{$uidHtml}</span>
            </div>
        </a>
        HTML;
    }

    protected function creatorUrl(int $id, $type): string
    {
        $prefix = dashboardName();
        if ($type === 'admin') {
            return url($prefix . "/auth/users/{$id}");
        } elseif ($type === 'country') {
            return url($prefix . "/superadmin-users/{$id}");
        } elseif ($type === 'region') {
            return url($prefix . "/area-manager-users/{$id}");
        } elseif ($type === 'sub_region') {
            return url($prefix . "/auth-users/{$id}");
        }
        return url($prefix . "/auth/users/{$id}");
    }
}
