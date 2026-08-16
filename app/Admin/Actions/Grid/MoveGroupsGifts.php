<?php

namespace App\Admin\Actions\Grid;

use App\Models\GiftCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class MoveGroupsGifts extends BatchAction
{
    public $name;

    public function __construct()
    {
        parent::__construct();
        $this->name = __('move');
    }

    public function handle(Collection $collection, Request $request)
    {
        $newCategory = $request->get('category_id');
        foreach ($collection as $model) {
            $model->update([
                'gift_category_id' => $newCategory,
            ]);
        }

        Cache::tags(['gifts'])->flush();

        return $this->response()->success(__('Gift moved successfully'))->refresh();
    }



    public function form()
    {
        $locale = app()->getLocale();

        // Category select
        $this->select('category_id', __('Select Category'))
            ->options(function () use ($locale) {

                $categories = [];
                foreach (GiftCategory::whereNotIn('type', ['lucky_gift', 'vip'])->get() as $category) {
                    $title = $category->title[$locale]
                        ?? $category->title['en']
                        ?? reset($category->title);

                    $categories[$category->id] = $title;
                }

                return $categories;
            })
            ->required();
    }
}
