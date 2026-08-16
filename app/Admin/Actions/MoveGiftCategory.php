<?php

namespace App\Admin\Actions;

use App\Models\GiftCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Form;

class MoveGiftCategory extends RowAction
{
    public function name()
    {
        // الاسم ديناميكي بحسب الحالة الحالية
        return  __('move');
    }

    public function handle(Model $model, Request $request)
    {
        $gift = $this->row;
        $newCategory = $request->get('category_id');

        if (!$newCategory) {
            return $this->response()->error('Please select category')->refresh();
        }

        $gift->gift_category_id = $newCategory;
        $gift->enable = 0;
        $gift->save();

        Cache::tags(['gifts'])->flush();

        $editUrl = admin_url('gifts/' . $gift->id . '/edit');

        return $this->response()->success(__("Gift moved successfully"))->redirect($editUrl);
    }

    // Popup form
    public function form()
    {
        $locale = app()->getLocale();

        // Category select
        $this->select('category_id', __('Select Category'))
            ->options(function () use ($locale) {

                $categories = [];
                foreach (GiftCategory::get() as $category) {
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
