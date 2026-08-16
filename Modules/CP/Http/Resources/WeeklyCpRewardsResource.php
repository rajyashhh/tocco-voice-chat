<?php

namespace Modules\CP\Http\Resources;

use App\Models\Ware;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyCpRewardsResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        switch ($this->type) {
            case "ware":
                $expire = $this->expire . ' days';
                $type = match ($this->ware->type) {
                    6 => trans('Intro Frame'),
                    5 => trans('Bubble Frame'),
                    default => trans('Avatar Frame'),
                };
                $image = $this->ware->show_img;
                break;

            case "vip":
                $expire = $this->expire . ' days';
                $type = $this->vip->name;
                $vipIcon = Ware::where('level', $this->vip->level)->where('type', 10)->where('get_type', 1)->first();
                $image = $this->vip->img;
                break;

            case "badge":
                $expire = $this->expire . ' days';
                $type = $this->badge?->name ?? '';
                $badgeImage = $this->badge?->images?->firstWhere('language', app()->getLocale())?->image
                    ?? $this->badge?->images?->first()?->image
                    ?? $this->badge?->image;
                $image = $badgeImage ?? '';
                break;

            case "achievement":
                $expire = $this->expire . ' days';
                $type = "achievement";
                $achievementImage = $this->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image
                    ?? $this->customAchievement?->images?->first()?->image;
                $image = $achievementImage ?? '';
                break;
            default:
                $expire = $this->target;
                $type = "coins";
                $image = "custom_image/gold_coin_icon.png";
                break;
        }

        return [
            'name'  => "{$expire} /{$type}",
            'image' => $image,
            'gender' => $this->gender,
        ];
    }
}
