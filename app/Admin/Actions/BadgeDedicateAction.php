<?php

namespace App\Admin\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use App\Helpers\Common;
use Modules\Badge\Entities\Badge;
use Modules\Badge\Entities\UserBadge;

class BadgeDedicateAction extends Action
{
    public $name;
    protected $selector = '.salary_action';
    public $id;


    public function __construct($id = 0)
    {
        $this->name = __('dedicate');
        $this->id = $id;
        parent::__construct();
    }
    public function handle(Request $request)
    {
        $user = User::query()->searchByUuid($request->user_uuid)->first();
        if (!$user) {
            return $this->response()->error(__('dashboard.userNotFound'))->refresh();
        }

        $badge = Badge::find($request->id);
        $badgeUser = UserBadge::where('user_id', $user->id)->where('badge_id', $badge->id)->active()->first();
        if ($badgeUser) return $this->response()->error(__('dashboard.haveBadges'))->refresh();
        try {

            $this->userBadge($user->id, $badge->id, $request->days, 'dedicate');
         
                $images =   $badge->images;
                
       
            CustomNotification::dedicateBadges($user, $request->days, $badge->name, $images);
            return $this->response()->success(__('dashboard.successful'));
        } catch (\Exception $exception) {

            return $this->response()->error('خطا غير متوقع');
        }
    }

    public static function userBadge($userId, $badgeId, $days, $type)
    {
        $badgeUser = UserBadge::where('user_id', $userId)->where('badge_id', $badgeId)->active()->first();
        if ($badgeUser && $badgeUser->expire != 0) {
            $badgeUser->expire += (($days) * 86400);
            $badgeUser->receive_type = $type;
            $badgeUser->auth_id = auth()->id();
            $badgeUser->save();
        } elseif (!$badgeUser) {
            $data = [
                'user_id' => $userId,
                'badge_id' => $badgeId,
                'expire' => time() + (($days) * 86400),
                'receive_type' => $type,
                'auth_id' => auth()->id(),
            ];

            UserBadge::query()->create($data);
        }
    }

    public function form()
    {
        $this->hidden('id', __('id'))->attribute('id', 'vid');
        $this->integer('days', __('days'));
        $this->text('user_uuid', __('user uuid'));
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')" class="btn btn-sm btn-info salary_action ">' . __('dedicate') . '</a>
<script>
function pu(val) {

  $("#vid").val(val)
}
</script>
';
    }
}
