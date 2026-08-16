<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\BanUser;
use App\Admin\Actions\RemoveBanUser;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\Ban;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class BanController extends MainController
{
    use HasResourceActions;
    public $permission_name = 'bans';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return  parent::index($content
            ->title(trans('bans'))
            ->body($this->grid()));
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('bans'))
            ->body($this->detail($id)));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(trans('bans'))
            ->body($this->form()->edit($id)));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('bans'))
            ->body($this->form()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $now = now();
        $grid = new Grid(new Ban);
        $countryID = Common::filterCountryIds();
        $grid->disableRowSelector();

        $reason = app()->getLocale() == 'ar' ? 'description_ar' : 'description_en';

        $grid->model()->whereHas('user')
            ->with([
                'banType:id,name_ar,name_en',
                'staff:id,name,avatar',
                'user:id,name,uuid,phone,country_id',
                'staff.agency',
                'user.profile:user_id,avatar',
                'user.country',
                'user.senderLevel',
                'user.receiverLevel',
                'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            ])
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->whereRaw("DATE_ADD(created_at, INTERVAL duration HOUR) > '$now'")
            ->select($reason, 'user_id', 'uid', 'duration', 'type', 'img', 'device_number', 'staff_id',   DB::raw('(SELECT created_at FROM bans AS b WHERE b.uid = bans.uid AND b.type = bans.type ORDER BY b.id DESC LIMIT 1) AS created_at'), 'ban_type_id')
            ->groupBy([$reason, 'user_id', 'uid', 'type', 'duration', 'device_number',  'staff_id',  'ban_type_id', 'img'])->orderByDesc('created_at');


        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->duration(__('duration'));
        $grid->column('type', __('Type'))->display(function ($type) {
            $types = [
                'normal' => __('normal'),
                //   'ip' => __('ip'),
                'device' => __('device'),
            ];

            return $types[$type] ?? '-';
        });

        $grid->column($reason, __('reason'))->display(function ($description) {
            $limitedDescription = mb_substr($description, 0, 40) . (mb_strlen($description) > 40 ? '...' : '');
            return "<a href='#' class='view-description' data-description=\"" . htmlentities($description) . "\">$limitedDescription</a>";
        });

        // إضافة سكريبت JavaScript لمعالجة النوافذ المنبثقة
        Admin::script("
            $(document).ready(function () {
                $('.view-description').click(function (e) {
                    e.preventDefault();

                    var description = $(this).data('description');

                    $('#modalDescriptionTitle').text('" . __('Full Description') . "');
                    $('#modalDescriptionContent').text(description);

                    $('#descriptionModal').modal('show');
                });

                $('.view-image').click(function (e) {
                    e.preventDefault();
                    var imgSrc = $(this).data('img');
                    $('#modalImageContent').attr('src', imgSrc);
                    $('#imageModal').modal('show');
                });
            });
        ");

        $grid->column('ban_type_id', __('ban_type'))->display(function ($row) {
            $banType = $this->banType;
            if (!$banType) return '-';
            $name_ar = $banType->name_ar ?? $banType->name_en;
            $name_en = $banType->name_en ?? $banType->name_ar;

            return app()->getLocale() === 'ar' ? $name_ar : $name_en;
        });

        $grid->column('img', trans('image'))->image('', 30);

        $grid->device_number(__('device_number'));

        $grid->column('staff_id', __('staff'))->display(function () {
            if (!$this->staff) return '-';

            $name = $this->staff->name ?? '-';
            $email = $this->staff->email ?? '-';
            $defaultImage = asset("images/admin-icon.png");
            $avatarPath = $this->staff->avatar ?? null;
            $avatar = $avatarPath ? asset($avatarPath) : $defaultImage;

            $adminUrl = admin_url('auth/users/' . $this->staff->id); // تعديل الرابط حسب صفحة الأدمن لديك

            return "<div style='display: flex; align-items: center; gap: 10px;'>
                        <img src='$avatar' alt='Admin Avatar' style='width: 40px; height: 40px; border-radius: 50%;'>
                        <div>
                            <a href='$adminUrl' style='color: #3498db; font-weight: bold; text-decoration: none;'>$name</a><br>
                            <span style='color: #aaa; font-size: smaller;'>$email</span>
                        </div>
                    </div>";
        });


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
            $grid->column('delete', __('Delete'))->display(function () {
                $deleteLabel = __('Delete');
                return "<button class='btn btn-danger btn-sm delete-ban'
            data-uid='{$this->uid}'
            data-type='{$this->type}'
            data-ban-type-id='{$this->ban_type_id}'>
            {$deleteLabel}
        </button>";
            });
        }

        // Move this OUTSIDE the `if` block
        $confirmMessage = json_encode(app()->getLocale() === 'ar' ? 'هل أنت متأكد من حذف هذا الحظر؟' : 'Are you sure you want to delete this ban?');

        Admin::script(<<<JS
    $('.delete-ban').off('click').on('click', function () {
        const btn = $(this);
        const uid = btn.data('uid');
        const type = btn.data('type');
        const ban_type_id = btn.data('ban-type-id');

        if (!confirm({$confirmMessage})) return;

        $.ajax({
            method: 'POST',
            url: '/admin/custom-delete-ban',
            data: {
                _token: LA.token,
                uid: uid,
                type: type,
                ban_type_id: ban_type_id
            },
            success: function (response) {
                if (response.status === true) {
                    toastr.success('Deleted successfully');
                    $.pjax.reload('#pjax-container');
                } else {
                    toastr.error(response.message || 'Failed to delete');
                }
            },
            error: function () {
                toastr.error('Error occurred during deletion.');
            }
        });
    });
JS);


        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->disableCreateButton();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('uid', __('uuid'));
            });
        });

        // $grid->disableTools(); // Disable default tools
        $grid->tools(function (Grid\Tools $tools) {
            $buttons = '<span style="display: inline-flex; gap: 10px;">';

            if (Admin::user()->can('create-' . $this->permission_name) || Admin::user()->can('*')) {
                $buttons .= (new BanUser())->render();
            }

            if (Admin::user()->can('delete-' . $this->permission_name) || Admin::user()->can('*')) {
                $buttons .= (new RemoveBanUser())->render();
            }

            $buttons .= '</span>';

            $tools->append($buttons);
        });
        $grid->disableExport();
        return $grid;
    }


    public function deleteBan(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('delete-' . $this->permission_name);
        }

        $data = $request->validate([
            'uid' => 'required',
            'type' => 'required|string',
            'ban_type_id' => 'required',
        ]);

        $deleted = Ban::where('uid', $data['uid'])
            ->where('type', $data['type'])
            ->where('ban_type_id', $data['ban_type_id'])
            ->delete();

        if ($deleted) {
            return response()->json(['status' => true]);
        }

        return response()->json(['status' => false, 'message' => 'No matching ban found']);
    }
}
