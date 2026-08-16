<?php

namespace App\Admin\Actions\Grid;;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use App\Admin\Actions\MoveEmojiCategoryAction;

class MoveGroupEmoji extends BatchAction
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
                'emoji_category_id' => $newCategory,
            ]);
        }

        Cache::tags(['emojis'])->flush();

        return $this->response()->success(__('emoji moved successfully'))->refresh();
    }



    public function form()
    {
        // Reuse cached categories from MoveEmojiCategoryAction (no extra query)
        $this->select('category_id', __('Select Category'))
            ->options(MoveEmojiCategoryAction::getCategoryOptions())
            ->required();
    }
}
