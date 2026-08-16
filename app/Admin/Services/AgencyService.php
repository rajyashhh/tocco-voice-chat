<?php

namespace App\Admin\Services;

use Illuminate\Support\Facades\Cache;

class AgencyService
{
    /**
     * @param $agency
     * @return string
     */
    function adminAgencyData($agency): string
    {
        if (! @$agency) {
            return '<span class="ug-no-agency"><i class="fa fa-minus-circle"></i> ' . __('Unknown agency') . '</span>';
        }

        $cacheKey = "agency_card_image_{$agency->id}";
        $imageUrl = Cache::remember($cacheKey, 3600, function () use ($agency) {
            $path = @$agency->img;
            $defaultImage = asset("images/icon-agency.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            return $url;
        });

        $profileUrl = route('admin.agency.profile', ['id' => $agency->id]);
        $name = e($agency->name ?? __('No name'));
        $id = $agency->id;

        return <<<HTML
        <a href="{$profileUrl}" class="ug-agency-card">
            <img src="{$imageUrl}" class="ug-agency-avatar" alt="{$name}">
            <div>
                <div class="ug-agency-name">{$name}</div>
                <div class="ug-agency-id">ID: {$id}</div>
            </div>
        </a>
        HTML;
    }


    function adminShippingAgencyData($agency): string
    {
        if (! @$agency) {
            return '<span class="ug-no-agency"><i class="fa fa-minus-circle"></i> ' . __('Unknown agency') . '</span>';
        }

        $cacheKey = "shipping_agency_card_image_{$agency->id}";
        $imageUrl = Cache::remember($cacheKey, 3600, function () use ($agency) {
            $path = @$agency->img;
            $defaultImage = asset("images/icon-agency.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            return $url;
        });

        $profileUrl = url('admin/shipping-agencies/profile', ['id' => $agency->id]);
        $name = e($agency->name ?? __('No name'));
        $id = $agency->id;

        return <<<HTML
        <a href="{$profileUrl}" class="ug-agency-card">
            <img src="{$imageUrl}" class="ug-agency-avatar" alt="{$name}">
            <div>
                <div class="ug-agency-name">{$name}</div>
                <div class="ug-agency-id"><i class="fa fa-truck" style="font-size:10px;margin-right:3px;"></i>ID: {$id}</div>
            </div>
        </a>
        HTML;
    }
}
