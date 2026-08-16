<?php

namespace App\Classes\Packs;

use App\Helpers\Common;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use App\Models\Ware;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * @property Collection $wares
 * @property Collection $packs
 * */
class AllowPacks
{
    private $packIds;

    private $user;

    private $packs;

    private $wares;

    private $data;

    private $userOVipLevel;

    private $vipPrices;

    public function __construct(User $user, array $data)
    {
        $this->user = $user;
        $this->data = $data;
        $this->userOVipLevel = $this->getUserVip();

        if (gettype(array_key_first($data)) === 'string') {
            $this->packIds = array_values($data);
        } else {
            $this->packIds = $data;
        }

        $this->initialize();
    }

    public function getUserVip(): int
    {
        $oVip = Common::ovip_center($this->user);

        if (gettype($oVip) !== 'array') {
            return 0;
        }

        return array_key_exists('level', $oVip) ? @$oVip['level'] : 0;
    }

    public function isPackUsedAndExist(int $id): bool
    {
        if (!@$this->user?->UserVip) return false;
        return $this->packs
            ->where('vip_user_id', @$this->user?->UserVip->id)
            ->where('type', $id)
            ->where('is_used', 1)
            ->isNotEmpty();
    }

    public function getWare(int $id)
    {
        return $this->wares->where('type', $id)->first();
    }

    public function initialize(): void
    {
        $userVipLevel = $this->user->UserVip->level ?? 0;

        // Load valid packs for the user
        $this->packs = $this->user->packs()
            ->whereHas('ware', fn($q) => $q->where('level', $userVipLevel))
            ->where(fn($q) => $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp))
            ->whereIn('type', $this->packIds)
            ->with('ware:id,level,type')
            ->get();

        // Aggregate ware info by type
        $this->wares = Ware::query()
            ->selectRaw('type, MIN(level) as min_level, MAX(level) as max_level, MIN(id) as min_id, MAX(id) as max_id')
            ->whereIn('type', $this->packIds)
            ->where('is_active_for_vip', true)
            ->groupBy('type')
            ->get();

        // Load VIP prices
        $this->vipPrices = $this->getVipPrices();
    }


    public function getVipPrices(): Collection
    {
        $cacheKey = 'ovips_prices';

        // Specify the number of seconds you want the data to be cached
        $seconds = 3600 * 24; // e.g., 3600 seconds = 1 hour

        // Retrieve the data from the cache or execute the query if it's not cached
        $ovips = Cache::remember($cacheKey, $seconds, function () {
            return OVip::query()->select(['id', 'price'])->get();
        });

        return $ovips;
    }

    public function getData(): array
    {
        $data = [];

        foreach ($this->data as $key => $value) {
            $ware = $this->getWare($value);

            $isAllow = $this->isAllowToUser($ware) ?? false;
            $minLevel = @$ware->min_level;

            $data[] = [
                'key' => $key,
                'title' => __('api.' . $key . '_title', [], 'ar'),
                'title_en' => __('api.' . $key . '_title', [], 'en'),
                'description' => $this->getDescription($key, $isAllow, $minLevel, @$ware->max_level, 'ar'),
                'description_en' => $this->getDescription($key, $isAllow, $minLevel, @$ware->max_level, 'en'),
                'is_active' => $this->isPackUsedAndExist($value),
                'is_allow_to_user' => $isAllow,
                'min' => $minLevel,
                'max' => @$ware->max_level,
                'min_price' => @$this->vipPrices->where('id', $minLevel)?->first()?->price,
            ];
        }

        return $data;
    }

    public function isAllowToUser($ware)
    {
        if (! $ware) {
            return null;
        }
        $userlevel = $this->userOVipLevel;

        $packs = $this->packs;

        // return $userlevel >= $ware->min_level && $userlevel <= $ware->max_level && $packs->where('target_id', $ware->max_id)->isNotEmpty();
        return $userlevel >= $ware->min_level && $userlevel <= $ware->max_level;
    }

    private function getDescription(string $key, $isAllow, $minLevel, $maxLevel, $lang = 'en')
    {
        if ($minLevel === null) {
            return __('api.pack_not_allow_yet', [], $lang);
        }

        if ($isAllow) {
            return __('api.' . $key . '_description_allow', [], $lang);
        }

        return __('api.' . $key . '_description', ['minLevel' => $minLevel, 'maxLevel' => $maxLevel], $lang);
    }
}
