<?php

namespace Modules\Public\Transformers;


use App\Models\Ware;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardLevelIntervalResource extends JsonResource
{
    public function toArray($request)
    {
        switch ($this->type) {
            case "ware":

                $type = "ware";
                $image = $this->ware->show_img;
                break;

            case "vip":

                $type = "vip";

                $image = $this->vip->img;
                break;

            case "achievement":
                $expire = $this->expire . ' days';
                $type = "achievement";
                $image = @$this->target[0] == '/' ? substr(@$this->target, 1) : @$this->target??'';
                break;
            default:
                $expire = $this->target;
                $type = "coins";
                $image = "custom_image/gold_coin_icon.png";
                break;
        }

        return [
            'type'  => "{$type}",
            'image' => $image,
        ];
    }
}
