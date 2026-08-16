<?php

namespace Modules\DailyPrize\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyStarGift extends JsonResource
{
    public function toArray($request)
    {
        $expire = '';
        $type = '';
        $image = '';

        switch ($this->gift_type) {
            case 'ware':
                $expire = ($this->expire ?? $this->ware?->expire) . ' days';
                $type = match ($this->ware?->type) {
                    6 => trans('Intro Frame'),
                    5 => trans('Bubble Frame'),
                    default => trans('Avatar Frame'),
                };
                $image = $this->ware?->show_img;
                break;

            case 'vip':
                $expire = ($this->expire ?? $this->vip?->expire) . ' days';
                $type = $this->vip?->name ?? 'VIP';
                $image = $this->vip?->img;
                break;
                
            case "badge":
                $expire = $this->expire . ' days';
                $type = $this->badge?->name ?? '';
                $image = $this->badge?->image ?? '';
                break;

            case 'achievement':
                $expire = ($this->expire ?? 0) . ' days';
                $type = 'achievement';
                $image = $this->resolveAchievementImage();
                break;

            default:
                $expire = $this->target ?? '0';
                $type = 'coins';
                $image = 'coin.png';
        }

        return [
            'name'  => "{$expire} /{$type}",
            'image' => $image,
        ];
    }

    protected function resolveAchievementImage(): string
    {
        if (is_string($this->target)) {
            return str_starts_with($this->target, '/') ? substr($this->target, 1) : $this->target;
        }

        return 'achievement.png';
    }
}
