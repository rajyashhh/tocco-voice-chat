<?php

namespace App\Tik\Repositories;

use App\Models\Gift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;



class GiftRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new Gift());
    }

    public function all($type)
    {
        $user = Auth::user();


        if ($type == 11 && $user) {

            return $user->myGifts()
                ->with('category')
                ->withPivot('quantity')
                ->orderByRaw('ISNULL(`sort`), `sort` ASC')
                ->orderBy('use_count', 'desc')
                ->orderBy('price')
                ->get();
        }

        $key = 'gifts:list:type:' . ($type === null ? 'all' : (int) $type);

        $resolver = function () use ($type) {
            $query = $this->model->newQuery()->where('enable', 1);

            if ($type) {
                $query->where('type', $type);
            }

            return $query->with('category')
                ->orderByRaw('ISNULL(`sort`), `sort` ASC')
                ->orderBy('use_count', 'desc')
                ->orderBy('price')
                ->get();
        };

        try {
            return Cache::tags(['gifts'])->remember($key, now()->addSeconds(1800 + rand(0, 300)), $resolver);
        } catch (\Exception $e) {
            // Non-tag-capable cache driver (e.g. file): degrade to an uncached query.
            return $resolver();
        }
    }

    public function getByCategory(?int $categoryId = null, ?int $type = null, int $perPage = 10)
    {
        $user = Auth::user();

        if ($type === -1 && $user) {
            return $user->myGifts()->withPivot('quantity')
                ->with('category')
                ->orderByRaw('ISNULL(`sort`), `sort` ASC')
                ->orderBy('use_count', 'desc')
                ->orderBy('price')
                ->get();
        }

        $key = 'gifts:category:' . ($categoryId === null ? 'all' : (int) $categoryId)
            . ':type:' . ($type === null ? 'all' : (int) $type);

        $resolver = function () use ($categoryId, $type) {
            $query = $this->model->newQuery()->where('enable', 1);

            if ($categoryId && $type !== -1) {
                $query->where('gift_category_id', $categoryId);
            }

            return $query->with('category')
                ->orderByRaw('ISNULL(`sort`), `sort` ASC')
                ->orderBy('use_count', 'desc')
                ->orderBy('price')
                ->get();
        };

        try {
            return Cache::tags(['gifts'])->remember($key, now()->addSeconds(1800 + rand(0, 300)), $resolver);
        } catch (\Exception $e) {
            // Non-tag-capable cache driver (e.g. file): degrade to an uncached query.
            return $resolver();
        }
    }



    public function get_images()
    {
        $resolver = function () {
            return $this->model->query()
                ->where('enable', 1)
                ->pluck('img');
        };

        try {
            return Cache::tags(['gifts'])->remember('gifts:images', now()->addSeconds(1800 + rand(0, 300)), $resolver);
        } catch (\Exception $e) {
            // Non-tag-capable cache driver (e.g. file): degrade to an uncached query.
            return $resolver();
        }
    }

    public function allGifts($page, $perPage)
    {
        $gifts = $this->model->query()
            ->with('category')
            ->where('type', '!=', 8)
            ->orderByRaw('ISNULL(`sort`), `sort` ASC')
            ->orderBy('use_count', 'desc');

        return $gifts->orderBy('price')->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById($giftId)
    {
        return $this->model->query()->select([
            'id',
            'name',
            'type',
            'price',
            'vip_level',
            'is_play',
            'img',
            'show_img',
            'show_img2',
            'image_type',
            'gift_category_id'
        ])->with('category')->where('id', $giftId)->where('enable', 1)->first();
    }

    public function findByGiftId($giftId)
    {
        return $this->model->query()->find($giftId);
    }

    public function giftUpdate($giftId, $type, $requestType)
    {
        // Eventless mass-update: bypasses the model observer, so invalidate explicitly.
        $this->model->where('id', $giftId)->update([$type => $requestType]);

        try {
            Cache::tags(['gifts'])->flush();
            // Also bust the per-gift price/availability cache used in the send-gift charge path (GiftLogService).
            Cache::forget("gift_{$giftId}");
        } catch (\Exception $e) {
            \Log::warning('Failed to flush gifts cache in GiftRepository@giftUpdate: ' . $e->getMessage());
        }

        return true;
    }

    public function allAchievementGift()
    {
        return $this->model->where('type', 5)->select('id', 'name')->where('enable', true)->get();
    }
}
