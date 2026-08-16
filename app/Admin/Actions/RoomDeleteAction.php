<?php

namespace App\Admin\Actions;

use App\Helpers\Common;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;


class RoomDeleteAction extends RowAction
{
    public $name = '';

    public function __construct()
    {
        Parent::__construct();
        $this->name = __('Delete');
    }

    public function handle(Model $room)
    {
        $d = [
            "messageContent" => [
                "message" => "deletedRoom",
                "roomId" => $room->id
            ]
        ];
        $json = json_encode($d);

        Common::sendToStream('SendCustomCommand', $room->id, $room->uid, $json);

        $room->delete();

        return $this->response()->success(__('Room deleted!'))->refresh();
    }

    public function dialog()
    {
        $this->confirm(__('Are you sure you want to delete this room?'));
    }

    public function icon()
    {
        return 'fa fa-trash';
    }
}
