<?php

namespace Modules\RoomCup\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Models\Room;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\RoomCup\Entities\RoomCupReward;
use Modules\RoomCup\Entities\TotalRoomGift;

class RoomCupReportsController extends MainController
{
    protected $title = '';
    public $permission_name = 'room-cup-report';

    public function index(Content $content)
    {
        return parent::index($content
            ->header(__('Room Cup Daily Rewards'))
            ->description(__('Room Cup Daily Rewards'))
            ->body($this->grid()));
    }
    protected function grid()
    {
        $grid = new Grid(new RoomCupReward());
        $grid->model()
            ->with([
                'gift:id,current_total,room_id',
                'gift.room:id,id,room_name,room_cover,uid',
                'user:id,id,name,uuid,special_id,country_id,sender_level,receiver_level',
                'user.packs:id,user_id,type,is_used,target_id,expire',
                'user.packs.ware:id',
                'user.profile:id,user_id,avatar',
                'user.country',
                'user.senderLevel',
                'user.receiverLevel',
                'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'gift.room.owner:id,id,name,uuid,special_id',
                'gift.room.owner.packs:id,user_id,type,is_used,target_id,expire',
                'gift.room.owner.packs.ware:id',
                'gift.room.owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'gift.room.owner.profile:id,user_id,avatar',
            ])
            ->orderBy('created_at', 'desc');

         $grid->column('user_id', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('room_id', __('room'))->display(function ($name) {
            $path = @$this->gift->room->room_cover;
            $id = @$this->gift->room->id;
            $defaultImage = asset("images/room.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            if (strlen($name) > 50) {
                $name = substr($name, 0, 50) . ' ...';
            }
            $showUrl = $this ? url("admin/rooms/{$id}") : 0;
            return "<div style='display: flex; align-items: center; gap: 10px;'>
                   <img src='$url' alt='Room Image' style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;'>
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>ID: $id</span>
                    </div>
                </div>";
        });

        $grid->column('amount', __('Profit Coins'));
        $grid->column('type', __('type'));

        // 🔹 Date of gain
        $grid->column('created_at', __('Date'))->display(function ($date) {
            return \Carbon\Carbon::parse($date)->toDateString();
        });

        // 🔹 Filters
        $grid->filter(function ($filter) {
            $filter->expand();
            $filter->like('gift.room.owner.name',  __('Owner Name'));
            $filter->equal('gift.room.id',  __('Room ID'));
            $filter->between('created_at', __('Date'))->date();
            $filter->where(function ($query) {
                $query->where('gain_value', '>', $this->input);
            }, __('Gain >'));
        });

        $grid->disableCreateButton();
        $grid->disableActions();

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(RoomCupReward::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('room_id', 'Room ID');
        $show->field('user_id', 'User ID');
        $show->field('amount', 'Amount');
        $show->field('type', 'Type');
        $show->field('created_at', 'Created At');
        $show->field('updated_at', 'Updated At');

        return $show;
    }
}
