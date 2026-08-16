<?php

namespace App\Admin\Actions;

use App\Facades\CustomNotification;
use App\Models\Ban;
use App\Models\BanRoom;
use App\Models\Room;
use App\Models\User;
use Encore\Admin\Actions\Action;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;


class DeleteBansRoom extends Action
{
    public $name = 'حذف الحظر';
    public $id;

    protected $selector = '.delete-ban';

    public function __construct( $id = 0  )
    {
        $this->id = $id;


        parent::__construct();

    }

    public function handle( \Illuminate\Http\Request $request)
    {

        $ban=BanRoom::find($request->id);
        $room = Room::find($ban->room_id);
        if (!$room) {
            return $this->response()->error(__('room not found'))->refresh();
        }
        $ban->delete();
        $room->update(['room_status' => 1]);
        return $this->response()->success('success')->refresh();
    }


    public function form()
    {
        $this->hidden('id', __('id'))->default($this->id);
        // $this->hidden('ban_type_id', __('id'))->default($this->ban_type_id);

        $this->confirm(__('messages.confirm_delete'), __('messages.are_you_sure'), [
            'icon' => 'warning',
            'showCancelButton' => true,
            'confirmButtonText' => __('messages.yes_delete'),
            'cancelButtonText' => __('messages.cancel'),
        ]);    }


    public function html()
    {
        return '<a href="#" onclick="pu(\'' . $this->id .'\', \')" class="btn btn-sm btn-success delete-ban">'.__('admin.delete').'</a>
        <script>
            function pu(val, type, ban_type_id) {
                console.log(val, type, ban_type_id)
                $("#uid").val(val);
                $("#type").val(type);
                $("#deleteModal").modal("hide");
                $("#ban_type_id").val(ban_type_id);

            }
        </script>';
    }

}
