<?php

namespace App\Http\Services;

use App\Helpers\Common;
use App\Models\Banner;

class BannerServices
{
    public function index($ids)
    {
        return Banner::query()
                    ->where('is_active', true)
                    ->whereNotNull('publish_at')
                    ->inRandomOrder()
                    ->take(1)
                    ->whereNotIn("id",$ids)
                    ->get();
    }

    public function checkIfUpdateExists(?int $utcTimestamp)
    {
        return Banner::query()
                     ->where('is_active', true)
                     ->where('publish_at', '!=', null)
                     ->whereIsNotSeen($utcTimestamp)
                     ->exists();
    }

    public function store($data)
    {
        $imagePath          = Common::upload('banners', $data['image_url']);
        $data['image_url']  = $imagePath;
        $data['is_active']  = $data['is_active'] == 'on';
        $data['publish_at'] = ($data['publish_at'] == 'on' ? now() : null);
        Banner::query()->create([
                                    // 'title'        => $data['title'],
                                    'image_url'    => $data['image_url'],
                                    'is_active'    => $data['is_active'],
                                    'publish_at'   => $data['publish_at'],
                                    'expire'       => $data['expire'],
                                    // 'redirect_url' => $data['redirect_url'],
                                    // 'button_text'  => $data['button_text'],
                                ]);
    }

    public function update(int $id, $data)
    {
        $banner = Banner::query()->where('id', $id)->first();
        if (!$banner) return false;

        if (array_key_exists('image_url', $data)) {
            $imagePath         = Common::upload('banners', $data['image_url']);
            $data['image_url'] = $imagePath;
        } else {
            $data['image_url'] = $banner['image_url'];
        }

        $data['is_active'] = $data['is_active'] == 'on' || $data['is_active'] == 1 ;
        if (array_key_exists('publish_at', $data)) {
            $data['publish_at'] = ($data['publish_at'] == 'on' ? now() : null);
        } else {
            $data['publish_at'] = $banner['publish_at'];
        }
        if (!array_key_exists('title', $data)) {
            $data['title'] = $banner['title'];
        }

        if (!array_key_exists('is_active', $data)) {
            $data['is_active'] = $banner['is_active'];
        }
        if (!array_key_exists('redirect_url', $data)) {
            $data['redirect_url'] = $banner['redirect_url'];
        }
        if (!array_key_exists('button_text', $data)) {
            $data['button_text'] = $banner['button_text'];
        }
        if (!array_key_exists('expire', $data)) {
            $data['expire'] = $banner['expire'];
        }
        $banner->update([
                            // 'title'        => $data['title'],
                            // 'image_url'    => $data['image_url'],
                            'is_active'    => $data['is_active'],
                            'publish_at'   => $data['publish_at'],
                            'expire'       => $data['expire'],
                            // 'redirect_url' => $data['redirect_url'],
                            // 'button_text'  => $data['button_text'],
                        ]);
        return true;
    }

    public function index2($ids)
    {
        $query = Banner::query()
            ->where('is_active', true)
            ->whereNotNull('publish_at')
            ->whereNotIn("id",$ids)
            ->inRandomOrder()
            ->take(1);

        // if (Banner::query()->count() > 1) {
        //     if ($ids != null && $query->count() > 1) $query->where('id', '!=', $ids->banner_id)->get();
        // }

        return $query->first();
    }

}
