<?php

namespace App\Admin\Actions;

use App\Models\BanRoom;
use Exception;
use App\Models\Ban;
use App\Models\Room;
use App\Models\User;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\Charge;
use Encore\Admin\Form;
use App\Helpers\Common;
use App\Models\BanType;
use App\Models\CoinLog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Auth;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin as AdminAuth;

class BanRoomAction extends Action
{
    public $name;
    public $permission_name = 'ban-rooms';

    protected $selector = '.ban_user_action';

    public function handle(Request $request)
    {

        if (!AdminAuth::user()->can('*')) {
            Permission::check('create-' . $this->permission_name);
        }
        $user = User::query()->searchByUuid($request->uuid)->first();
        if (!$user) return $this->response()->error('user not found')->refresh();
        $room = Room::where('uid', $user->id)->where('type', $request->type)->first();
        if (!$room) {
            return $this->response()->error(__('room not found'))->refresh();
        }

        $room_id  = $room->id;
        $now = now();
        $messages = [];
        $newBan = false;


        $haveBan = BanRoom::query()->where('room_id', $room_id)->whereRaw("created_at + INTERVAL duration HOUR > '$now'")
            ->exists();
        if ($haveBan) {
            $messages[] = __('already have normal ban');
        } else {
            $newBan = true;

            BanRoom::query()->create(
                [
                    'room_id' => $room_id,
                    'duration' => $request->duration,
                    'staff_id' => Auth::id(),
                ]
            );


        }




        if ($room && $newBan) {
            $d = [
                "messageContent" => [
                    "message" => "banRoom",
                    "roomId" => $room->id
                ]
            ];
            $json = json_encode($d);

            Common::sendToStream('SendCustomCommand', $room->id, $room->uid, $json);
        }
        if ($newBan) {
            // CustomNotification::banUser($user, $request->duration);
        }

        if (count($messages) > 0) {
            return $this->response()->error(implode("<br>", $messages))->refresh();
        }
        return $this->response()->success('success')->refresh();
    }

    public function form()
    {
        $this->text('uuid', __('uuid'));
        $this->integer('duration', __('duration(hours)'))->rules('required|max:6');
        $this->select('type', __('type'))->options(['audio' => __('audio'), 'live' => __('live')])->default('audio');
    }

    public function html()
    {
        $banText = __('close room'); // Laravel translation
        return <<<HTML
    <a href="javascript:void(0);" class="ban_user_action btn btn-sm  text-white" 
       style="background-color: #28a745; border-color: #28a745; color: white;">
        {$banText}
    </a>
    HTML;
    }
}
