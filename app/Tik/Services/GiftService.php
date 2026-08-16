<?php

namespace App\Tik\Services;


use Illuminate\Support\Facades\Http;
use App\Helpers\Common;
use App\Tik\Repositories\GiftRepository;
use Illuminate\Support\Facades\Storage;

class GiftService
{

    public function __construct(
        private readonly GiftRepository $giftRepository,
    ) {}

    public function index($type)
    {
        return $this->giftRepository->all($type);
    }

    public function getByCategory($categoryId ,$typ)
    {
        return $this->giftRepository->getByCategory($categoryId ,$typ);
    }
    
    public function get_images()
    {
        return $this->giftRepository->get_images();
    }
    
    public function allGift($page, $perPage)
    {
        return $this->giftRepository->allGifts($page, $perPage);
    }



    public function show($giftId)
    {
        return $this->giftRepository->findByGiftId($giftId);
    }

   
    public function create( $request)
    {
        if ($request->hasFile('img')) {
            $image = Common::upload('images', $request->file('img'));
        } elseif($request->has('img')) {
          //  dd($request->img);
            $image =    httpImage($request->img);
        }
       
        if ($request->hasFile('show_img')) {
            $showImg = Common::upload('images', $request->file('show_img'));
        } elseif($request->has('show_img')) {
            $showImg =   httpImage($request->show_img);
        }

        $data = [
            'name'         => $request->name,
            'e_name'         => $request->e_name,
            'type'         => $request->type,
            'vip_level'         => $request->vip_level,
            'price'         => $request->price,
            'img'          =>  $image ?? "",
            'show_img'          => $showImg ?? "",
            'image_type'         => $request->image_type,
            'show_img2'          =>  '',
            'sort'         => $request->sort,
            'enable'         => $request->enable,
            'music_gift'         => $request->music_gift,
        ];

        $this->giftRepository->create($data);
        return true;
    }

    public function update($request)
    {
        $data = [
            'name' => $request->name,
            'e_name' => $request->e_name,
            'type' => $request->type,
            'vip_level' => $request->vip_level,
            'price' => $request->price,
            'image_type' => $request->image_type,
            'sort' => $request->sort,
            'enable' => $request->enable,
            'music_gift' => $request->music_gift,
        ];

        if ($request->hasFile('img')) {
            $data['img'] = Common::upload('images', $request->file('img'));
        }

        if ($request->hasFile('show_img')) {
            $data['show_img'] = Common::upload('images', $request->file('show_img'));
        }

        if ($request->hasFile('show_img2')) {
            $data['show_img2'] = Common::upload('images', $request->file('show_img2'));
        }


        $this->giftRepository->update($data, $request->gift_id);
        return true;
    }

    public function updateSwitch($requestSwitch, $giftId, $type)
    {
        $gift = $this->giftRepository->giftUpdate($giftId, $type, $requestSwitch);
        return true;
    }
}
