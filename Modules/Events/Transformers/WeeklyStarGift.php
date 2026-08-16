<?php

namespace Modules\Events\Transformers;


use App\Models\Ware;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyStarGift extends JsonResource
{
    public function toArray($request)
    {
        switch ($this->type) {
            case "ware":
                $expire = $this->expire . ' days';
                $type = match ($this->ware?->type) {
                    6 => trans('Intro Frame'),
                    5 => trans('Bubble Frame'),
                    default => trans('Avatar Frame'),
                };
                $image = $this->ware?->show_img;
                break;

            case "vip":
                $expire = $this->expire . ' days';
                $type = $this->vip?->name;
                $image = $this->vip?->img;
                break;
            case "badge":
                $expire = $this->expire . ' days';
                $type = $this->badge?->name ?? '';
                $image = $this->badge?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                break;

            case "achievement":
                $expire = $this->expire . ' days';
                $type = $this->customAchievement?->name ?? '';
                $image = $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ?? '';
                break;
            default:
                $expire = $this->target;
                $type = "coins";
                $image = "coin.png";
                break;
        }

        $image = $image ?: '';
        return [
            'name'  => "{$expire} /{$type}",
            'image' => $image,
        ];
    }
}
