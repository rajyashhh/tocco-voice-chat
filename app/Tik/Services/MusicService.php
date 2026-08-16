<?php

namespace App\Tik\Services;

use Exception;
use App\Helpers\Common;
use App\Tik\Repositories\MusicRepository;



class MusicService
{
    public function __construct(private readonly MusicRepository $musicRepository,) {}


    public function all()
    {
        return $this->musicRepository->all();
    }

    public function userMusic($userId)
    {
        return $this->musicRepository->getByUser($userId);
    }

    public function destroyUserMusic($userId, $musicId)
    {
        return $this->musicRepository->deleteByUser($userId, $musicId);
    }
    public function create($userId, $url, $image, $name = null)
    {
        $data = [
            'user_id' => $userId,
            'url' => $url,
            'image' => $image,
            'name' => $name,
        ];

        if($image){
            $image_name = Common::upload('images', $image);
            $data['image'] = $image_name;
        }
        $this->musicRepository->create($data);
        return true;
    }
}
