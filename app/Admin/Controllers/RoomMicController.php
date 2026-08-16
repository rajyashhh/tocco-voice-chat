<?php

namespace App\Admin\Controllers;

use Modules\Vip\Entities\OVip;
use App\Models\Room;
use App\Models\User;
use App\Models\Ware;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Helpers\Common;
use Illuminate\Support\Str;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Session;
use Encore\Admin\Controllers\HasResourceActions;
use Modules\Public\Http\Services\UserCounterServices;

class RoomMicController extends MainController
{

    use HasResourceActions;
   // public $permission_name = 'ovip-gift';

    public function index(Content $content)
    {
        $url = url('/admin/rooms'); // Define your button URL
        $back = __('back');
        $buttonHTML = <<<HTML
    <a href="{$url}" class="btn btn-sm btn-success" style="margin-bottom: 20px;">
        <i class="fa fa-arrow-left"></i> {$back}
    </a>
    HTML;
     
            $room = Room::find(request('room_id'));
            abort_unless($room, 404);
            $ids = array_filter(explode(',', (string) $room->microphone));
      


        // Add the back button


        // Dynamically add rows for each level
       
            $content = $content
                ->header(trans('admin.index'))

                ->row($buttonHTML);
            $content->row($this->gridDynamic($ids));
        

        return $content;
    }
  

    protected function gridDynamic($ids)
    {
        $grid = new Grid(new User);
        $grid->model()->whereIn('id', $ids ?: [0]);

        $grid->id(__('ID'));
        $grid->column('name', __('name'));

        $grid->column('uuid', __('uuid'));

        $grid->column('profile.avatar', __('image'))->display(function ($path) {
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            return handleShowImageWithTypes($this->id, $url, 50, 50);
        });
       

       

        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
            $actions->disableEdit();
        });
        $grid->disableCreateButton();
       
       

    
        return $grid;
    }


    
}
