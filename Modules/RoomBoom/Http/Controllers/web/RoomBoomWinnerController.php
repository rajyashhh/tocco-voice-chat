<?php

namespace Modules\RoomBoom\Http\Controllers\web;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Modules\RoomBoom\Entities\RoomBoomLevel;
use Modules\RoomBoom\Entities\RoomBoomWinner;

class RoomBoomWinnerController extends MainController
{
    public $permission_name = 'room-boom-winners';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Room Boom Winners'))
            ->body($this->grid()));
    }

    protected function  grid()
    {
        $grid = new Grid(new RoomBoomWinner());
        $grid->model()->with([
            'reward',
            'reward.gift',
            'reward.ware',
            "reward.customAchievement",
            'reward.customAchievement.images',
            'boom.roomBoomLevel',
            'boom.totalRoomGift.room',
            'boom.totalRoomGift.room.owner.country',
            'user',
            'user.profile',
            'user.country',
            'user.senderLevel',
            'user.receiverLevel',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),

        ]);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('nameUser', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        $grid->column('room_info', __('Room'))->display(function () {
            $room   = @$this->boom->totalRoomGift->room;
            $name   = $room->room_name ?? 'Unknown Room';
            $id     = $room->id ?? '-';
            $path   = $room->cover ?? null;
            $defaultImage = asset("images/room.jpg");
            $url = $path ? getImagePath($path) : $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $roomUrl = url("admin/rooms/{$id}");

            return "
                <a href='$roomUrl' style='text-decoration: none; color: inherit;'>
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        <img src='$url' alt='Room Image'
                            style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;'>
                        <div>
                            <span style='font-weight: 600;'>$name</span><br>
                            <span style='color: #666;'>ID: $id</span>
                        </div>
                    </div>
                </a>
            ";
        });

        $grid->column('level', __('Level'))->display(function () {
            return $this->boom?->roomBoomLevel?->level ?? '-';
        });

        $grid->column('reward.target_type', __('Type'));

        $grid->column('reward.target', __('target'))->display(function () {
            $reward = $this->reward;

            if (!$reward) return 'N/A';

            switch ($reward->target_type) {
                case 'ware':
                    return $reward->ware?->name ?? 'Unknown Ware';
                case 'gift':
                    return $reward->gift?->name ?? 'Unknown Gift';
                case 'achievement':
                    return $reward->customAchievement?->name ?? 'Unknown Achievement';
                case 'coin':
                    return $reward->target . ' Coins';
                default:
                    return 'N/A';
            }
        });
        if (!request()->filled('_export_')) {
            $grid->column('image', __('Image'))->display(function () {
                $reward = $this->reward;
                if (!$reward) return '';
                $path = null;
                if ($reward->target_type === 'ware') {
                    $path = $reward->ware?->img2 ?? $reward->ware?->show_img;
                } elseif ($reward->target_type === 'gift') {
                    $path = $reward->gift?->show_img ?? $reward->gift?->img;
                } elseif ($reward->target_type === 'achievement') {
                    $path = $reward->customAchievement?->images?->firstWhere('language', app()->getLocale())?->image ??  '';
                } elseif ($reward->target_type === 'coin') {
                    $path = 'coin.png';
                }

                if (!$path) {
                    return '';
                }
                $url = getImagePath($path);
                return handleShowImageWithTypes($this->id, $url, 50, 50);
            });
        }

        $grid->column('created_at', __('Created At'))->display(function ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        });

        $grid->filter(function ($filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->whereHas('boom.totalRoomGift.room.owner', function ($q) use ($input) {
                        $q->where('name', 'like', "%$input%")
                            ->orWhere('special_id', 'like', "%$input%")
                            ->orWhere('uuid', 'like', "%$input%");
                    });
                }, __('Room Owner'))->placeholder(__('Search by owner name, uuid or special_id'));

                $filter->where(function ($query) {
                    $input = $this->input;
                    $query->whereHas('user', function ($q) use ($input) {
                        $q->where('name', 'like', "%$input%")
                            ->orWhere('special_id', 'like', "%$input%")
                            ->orWhere('uuid', 'like', "%$input%");
                    });
                }, __('User'))->placeholder(__('Search by user name, uuid or special_id'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($from = request('from_date')) {
                        $start = Carbon::parse(convertArabicToEnglishNumbers($from))->startOfDay();
                        $query->whereDate('created_at', '>=', $start);
                    }
                }, __('From Date'), 'from_date')->date();

                $filter->where(function ($query) {
                    if ($to = request('to_date')) {
                        $end = Carbon::parse(convertArabicToEnglishNumbers($to))->endOfDay();
                        $query->whereDate('created_at', '<=', $end);
                    }
                }, __('To Date'), 'to_date')->date();
            });
        });

        if (method_exists($this, 'extendGrid')) {
            $this->extendGrid($grid);
        }

        $grid->disableActions();

        Admin::script("
        if (window.innerWidth >= 1024) { // Example threshold for desktop screens
            $('.table-responsive').removeClass('table-responsive');
            }
        ");

        return $grid;
    }
}
