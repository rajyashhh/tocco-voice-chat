<?php

namespace App\Tik\Services;

use App\Helpers\Common;
use App\Tik\Repositories\WareRepository;


class WareService
{
    public function __construct(
        private readonly WareRepository $wareRepository,
    ) {}

    public function index($page, $perPage)
    {
        return $this->wareRepository->allWares($page, $perPage);
    }

    public function create($request)
    {
        if ($request->hasFile('img2')) {
            $image = Common::upload('images', $request->file('img2'));
        } else {
            $image =  httpImage($request->img2);
        }
        if ($request->hasFile('show_img')) {
            $showImg = Common::upload('images', $request->file('show_img'));
        } else {
            $showImg =  httpImage($request->show_img);
        }
        
        $data = [
            'name'         => $request->name,
            'name_en'         => $request->name_en,
            'type'         => $request->type,
            'level'         => $request->level,
            'price'         => $request->price,
            'img2'          => $image ?? '',
            'show_img'          => $showImg ?? '',
            'image_type'         => $request->image_type,
            'color'         => $request->color,
            'is_active_for_vip'         => $request->is_active_for_vip,
            'enable'         => $request->enable,
            'get_type'         => $request->get_type,
            'title'         => $request->title,
            'title_en'         => $request->title_en,
            'exp'         => $request->exp,
            'expire'  => $request->expire,
            'num'  => $request->num,
        ];
        $this->wareRepository->create($data);
        return true;
    }

    public function show($wareId)
    {
        return $this->wareRepository->findById($wareId);
    }

    public function update($request)
    {
        $data = [
            'name'         => $request->name,
            'name_en'         => $request->name_en,
            'type'         => $request->type,
            'level'         => $request->level,
            'price'         => $request->price,
            'image_type'         => $request->image_type,
            'color'         => $request->color,
            'is_active_for_vip'         => $request->is_active_for_vip,
            'enable'         => $request->enable,
            'get_type'         => $request->get_type,
            'title'         => $request->title,
            'title_en'         => $request->title_en,
            'exp'         => $request->exp,
            'expire'  => $request->expire,
            'num'  => $request->num,
        ];
        if ($request->hasFile('img2')) {
            $data['img2'] = Common::upload('images', $request->file('img2'));
        }

        if ($request->hasFile('show_img')) {
            $data['show_img'] = Common::upload('images', $request->file('show_img'));
        }

        $this->wareRepository->update($data, $request->ware_id);
        return true;
    }

    public function updateSwitch($enable, $ware_id, $type)
    {
        $data[$type] = $enable;
        $this->wareRepository->update($data, $ware_id);
        return true;
    }

    public function profile_frame_wares($page, $perPage)
    {
        return $this->wareRepository->profile_frame_wares($page, $perPage);
    }
}
