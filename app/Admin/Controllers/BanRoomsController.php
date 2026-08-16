<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\BanRoomAction;
use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\BanRoom;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;


class BanRoomsController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'close-room';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Close room'))
            ->body($this->grid()));
    }



    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BanRoom());
        $countryID = Common::filterCountryIds();
        $grid->disableRowSelector();
        $grid->model()
            ->with([
                'room',
                'room.owner',
                'room.owner.agency',
                'room.owner.profile:user_id,avatar',
                'room.owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'room.owner.country',
                'room.owner.senderLevel',
                'room.owner.receiverLevel',
                'staff'
            ])
            ->whereHas('room')
            ->when($countryID, fn($q) => $q->whereHas('room', fn($q) => $q->whereHas('owner', fn($q) => $q->whereIn('country_id', $countryID))))
            ->whereRaw("DATE_ADD(created_at, INTERVAL duration HOUR) > ?", [now()])
            ->orderByDesc('created_at');
        $grid->column('id', __('Id'));

        $grid->column('room_id', __('Room'))->display(function () {
            $room = $this->room;

            if (!$room) return '-';
            $name = $room->room_name;
            $room_id = $room->id;
            $defaultImage = asset("images/businessman-icon.jpg");
            $avatarPath = @$room->room_cover;
            $avatar = getImagePath($avatarPath) ?? $defaultImage;

            if (!isImageExists($avatar)) {
                $avatar = $defaultImage;
            }

            $userUrl = admin_url('rooms/' . $room->id);

            return "<div style='display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; background: var(--bg-color);'>
                        <img src='$avatar' alt='User Avatar' style='width: 40px; height: 40px; border-radius: 50%;'>
                        <div>
                            <a href='$userUrl' style='color: var(--primary-color); font-weight: bold; text-decoration: none;'>$name</a><br>
                            <span style='color: var(--uuid-color); font-size: smaller;'>Room Id: $room_id</span><br>

                        </div>
                    </div>";
        });

        $grid->column('room.type', __('room type'));

        $grid->column('name', __('owner'))->display(function () {
            return app(UserService::class)->adminUserCard($this->room->owner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('staff_id', __('staff'))->display(function () {
            if (!$this->staff) return '-';

            $name = $this->staff->name ?? '-';
            $email = $this->staff->email ?? '-';
            $defaultImage = asset("images/admin-icon.png");
            $avatarPath = $this->staff->avatar ?? null;
            $avatar = $avatarPath ? asset($avatarPath) : $defaultImage;

            $adminUrl = admin_url('admin/auth/users/' . $this->staff->id); // تعديل الرابط حسب صفحة الأدمن لديك

            return "<div style='display: flex; align-items: center; gap: 10px;'>
                        <img src='$avatar' alt='Admin Avatar' style='width: 40px; height: 40px; border-radius: 50%;'>
                        <div>
                            <a href='$adminUrl' style='color: #3498db; font-weight: bold; text-decoration: none;'>$name</a><br>
                            <span style='color: #aaa; font-size: smaller;'>$email</span>
                        </div>
                    </div>";
        });

        $grid->duration(__('Duration'));
        $grid->column('created_at', __('expire'))->display(function () {
            $timezone = getTimezone();

            // Get raw UTC datetime
            $createdAt = \Carbon\Carbon::parse($this->getAttributes()['created_at'], 'UTC');

            // Add ban duration and convert to user's timezone
            $banExpiration = $createdAt->addHours($this->duration)->setTimezone($timezone);

            $now = now($timezone);

            // Get total remaining minutes
            $diffInMinutes = $now->diffInMinutes($banExpiration, false);

            if ($diffInMinutes <= 0) {
                return 'منتهي'; // Expired
            }

            $hours = floor($diffInMinutes / 60);
            $minutes = $diffInMinutes % 60;

            if ($hours >= 1) {
                return "{$hours}h:{$minutes}m";
            } else {
                return "{$minutes}" . ' ' . __('minute');
            }
        });
        if (Admin::user()->can('delete-' . $this->permission_name) || Admin::user()->can('*')) {
            $grid->column('return', __('Delete'))->display(function () {
                return (new \App\Admin\Actions\DeleteBansRoom($this->id))->render();
            });
        }


        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->disableCreateButton();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('room_id', __('Room Id'));
            });
            $filter->column(1 / 2, function ($filter) {

                $filter->equal('room.type', __('room type'))->select([
                    'audio' => trans('audio'),
                    'live' => trans('live'),
                ]);
            });
        });

        $permission = $this->permission_name;
        $grid->tools(function (Grid\Tools $tools) use ($permission) {
            if (Admin::user()->can('create-' . $permission) || Admin::user()->can('*')) {
                $tools->append((new BanRoomAction())->render());
            }
        });

        return $grid;
    }
}
