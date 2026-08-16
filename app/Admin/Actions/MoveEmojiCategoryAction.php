<?php

namespace App\Admin\Actions;

use App\Models\EmojiCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class MoveEmojiCategoryAction extends RowAction
{
    /**
     * Cache categories across all row instances to avoid N+1 queries.
     */
    protected static ?array $cachedCategories = null;

    public function name()
    {
        return  __('move');
    }

    public function handle(Model $model, Request $request)
    {
        $gift = $this->row;
        $newCategory = $request->get('category_id');

        if (!$newCategory) {
            return $this->response()->error(__('Please select category'))->refresh();
        }

        $gift->emoji_category_id = $newCategory;
        $gift->save();

        Cache::tags(['emojis'])->flush();

        return $this->response()->success(__('emoji moved successfully'))->refresh();
    }

    // Popup form
    public function form()
    {
        $options = static::getCategoryOptions();

        $this->select('category_id', __('Select Category'))
            ->options($options)
            ->required();
    }

    /**
     * Get category options (cached per request to avoid N+1).
     */
    public static function getCategoryOptions(): array
    {
        if (static::$cachedCategories !== null) {
            return static::$cachedCategories;
        }

        $locale = app()->getLocale();
        $categories = [];

        foreach (EmojiCategory::get() as $category) {
            $title = $category->title[$locale]
                ?? $category->title['en']
                ?? reset($category->title);

            $categories[$category->id] = $title;
        }

        static::$cachedCategories = $categories;

        return $categories;
    }
}
