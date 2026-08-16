<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Modules\Vip\Entities\UserVip;
use Illuminate\Http\Resources\Json\JsonResource;

class PackUserResource extends JsonResource
{

    public function toArray($request)
    {
        $getTypeLabels = [
            1 => __('vip level automatic acquisition'),
            2 => __('activities'),
            3 => __('treasure box'),
            4 => __('purchase'),
            5 => __('background addition'),
        ];

        $typeLabels = [
            1  => __('Gemstone'),
            3  => __('Card Scroll'),
            4  => __('Avatar Frame'),
            5  => __('Bubble Frame'),
            6  => __('Entering Special Effects'),
            7  => __('Microphone Aperture'),
            8  => __('Badge'),
            9  => __('NoKick'),
            10 => __('Icon'),
            11 => __('intro animation'),
            12 => __('wapel'),
            13 => __('hide country'),
            14 => __('vip gifts'),
            15 => __('no pan'),
            16 => __('hidden room'),
            17 => __('anonymous man'),
            18 => __('colored name'),
            19 => __('profile visitors hide in'),
            20 => __('hide last active'),
            21 => __('sound effect'),
            22 => __('upload GIF image'),
        ];


        return [
          'id' => $this->id,
            'get_type' => $getTypeLabels[$this->get_type] ?? null,
            'type' => $typeLabels[$this->type] ?? null,
            'image' => optional($this->ware)->show_img ?? '',
            'expire' => $this->expire,
        ];
    }
}
