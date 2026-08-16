<?php

namespace Modules\RoomBoom\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\Setting;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Http\Request;
use Modules\RoomBoom\Entities\BoomPercentage;

class PercentageBoomController extends MainController
{
    public $permission_name = 'room-boom-settings';

    public function index(Content $content)
    {
        $percentages = BoomPercentage::get();
        return parent::index($content
            ->title(__('settings'))
            ->body(view('percentageBoom', compact(['percentages',]))));
    }


    public function save(Request $request)
    {
        Permission::check('edit-' . $this->permission_name);

        $files = $request->file('files', []);
        $types = $request->input('types', []);

        $percentageIds = collect($files)
            ->keys()
            ->merge(array_keys($types))
            ->unique();

        foreach ($percentageIds as $id) {

            $percentage = BoomPercentage::find($id);
            if (!$percentage) {
                continue;
            }

            // Save Image Type if exists
            if (isset($types[$id])) {
                $percentage->image_type = $types[$id];
            }

            // Save File if uploaded
            if (isset($files[$id]) && $files[$id]->isValid()) {

                // Optional: delete old file
                // Common::delete($percentage->image);

                $path = Common::upload('images', $files[$id]);
                $percentage->image = $path;
            }

            $percentage->save();
        }

        admin_toastr(__('Saved successfully'), 'success');
        return redirect()->route('admin.room-boom-settings');
    }
}
