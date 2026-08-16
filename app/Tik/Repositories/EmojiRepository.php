<?php

namespace App\Tik\Repositories;

use App\Models\Country;
use App\Models\Emoji;
use Illuminate\Support\Facades\Cache;

class EmojiRepository extends AbstractRepository
{


    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new Emoji());
    }

    public function all($request)
    {
        $resolver = function () use ($request) {
            $query = $this->model->query()->where('enable', 1);
            if ($request->pid) {
                $query->where('pid', $request->pid);
            }
            return $query->select('id', 'pid', 'name', 'emoji', 't_length', 'sort', 'name_en')->orderBy('sort')->get();
        };

        try {
            return Cache::tags(['emojis'])->remember('emojis:list:pid:' . ($request->pid ?: 'all'), now()->addSeconds(600 + rand(0, 120)), $resolver);
        } catch (\Exception $e) {
            // Non-tag-capable cache driver (e.g. file): degrade to an uncached query.
            return $resolver();
        }
    }

    public function index($request)
    {
        $resolver = function () use ($request) {
            $query = $this->model->query()->where('enable', 1);
            if ($request->emoji_category_id) {
                $query->where('emoji_category_id', $request->emoji_category_id);
            }
            return $query->select('id', 'pid', 'name', 'emoji', 'image_type','t_length', 'sort', 'name_en')->orderBy('sort')->get();
        };

        try {
            return Cache::tags(['emojis'])->remember('emojis:all:cat:' . ($request->emoji_category_id ?: 'all'), now()->addSeconds(600 + rand(0, 120)), $resolver);
        } catch (\Exception $e) {
            // Non-tag-capable cache driver (e.g. file): degrade to an uncached query.
            return $resolver();
        }
    }

    public function findById($id)
    {
        return $this->model->query()->select('id', 'pid', 'name', 'emoji', 't_length', 'sort')->find($id);
    }
}
