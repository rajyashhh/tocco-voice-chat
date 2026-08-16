<?php

namespace Modules\Country\Http\Controllers\Admin;

use App\Models\HomeCarouselDisplay;
use Carbon\Carbon;
use Modules\Country\Entities\SuperadminBannerRequest;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Modules\Country\Enums\SuperAdminNotificationLink;
use Modules\Country\Enums\SuperAdminNotificationType;
use Modules\Country\Helper\SuperAdminHelper;
use Modules\Country\Helper\SuperAdminNotificationHelper;
use Modules\Country\Services\SuperAdminService;


class SuperadminBannerRequestController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'SuperadminBannerRequest';
    public $permission_name = 'superadmin-banners';

    public function __construct(protected SuperAdminService $userService)
    {
    }

    public function index(Content $content)
    {
        return parent::index(
            $content
                ->title(__('SuperadminBannerRequest'))
            // ->body($this->grid())
        );
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $itemNotification = request('itemNotification');
        $grid = new Grid(new SuperadminBannerRequest());
        $grid->model()->when($itemNotification, function ($query, $itemNotification) {
            $query->where('id', $itemNotification);
        });
        $grid->model()->with(['superAdmin', 'homeCarousel:home_carousel_id.img'])->latest();
        $countryID = session('filter_country_id');
        $grid->model()
            ->when($countryID, fn($q) => $q->whereHas('superAdmin', fn($q) => $q->where('country_id', $countryID)))
            ->with(['superAdmin', 'homeCarousel:home_carousel_id.img'])->latest();
        $grid->column('id', __('ID'));

        $userService = $this->userService;

        // // Super Admin
        // $grid->column('user_id', __('Super Admin'))->display(function () use ($userService) {
        //     return $userService->adminUserAvatar($this->superAdmin ?? null, withoutLevels: true);
        // });

        $grid->column('username', __('Super Admin'))->display(function ($name) {
            if (!$this->superAdmin) {
                return __('Unknown');
            }
            if (request()->filled('_export_')) {
                return $name;
            }

            $id = @$this->superAdmin->id ?? '-';
            $name = @$this->superAdmin->username ?? 'غير معروف';
            $path = @$this->superAdmin->avatar ?? '';
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->superAdmin->id, $url, 40, 40);
            $showUrl = url("admin/superadmin-users/{$this->superAdmin->id}");

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='font-size: smaller;'>ID: $id</span>
                    </div>
                </div>
            ";
        });

        // Banner Image
        $grid->column('homeCarousel.img', __('img'))->image('', 235, 77);


        $grid->column('coins_deducted', __('Coins Deducted'));
        $grid->column('status', __('Status'))->display(function ($status) {
            switch ($status) {
                case 'pending':
                    return '<span class="text-warning">' . __('Pending') . '</span>';
                case 'approved':
                    return '<span class="text-success">' . __('Approved') . '</span>';
                case 'rejected':
                    return '<span class="text-danger">' . __('Rejected') . '</span>';
                default:
                    return $status;
            }
        });

        $grid->column('notes', __('Type'))->display(function ($value) {
            if ($value === 'display_discover') {
                return __('Display Discover');
            } elseif ($value === 'display_home_top') {
                return __('Display Home Top');
            } elseif ($value === 'display_home_middle') {
                return __('Display Home Middle');
            } elseif ($value === 'display_live') {
                return __('Display Live');
            } elseif ($value === 'display_room') {
                return __('Display Room');
            } else {
                return $value;
            }
        });
        $grid->column('hours', __('hours'));
        $grid->column('created_at', __('Created At'))
            ->display(function ($createdAt) {
                return Carbon::parse($createdAt)->format('d/m/Y H:i');
            });

        // Actions
        if (Admin::user()->can('reject-switch-' . $this->permission_name) || Admin::user()->can('approve-switch-' . $this->permission_name) || Admin::user()->can('*')) {
            $grid->column('actions', __('Actions'))->display(function () {
                $approveUrl = route('admin.superadmin-banner.approve', $this->id);
                $rejectUrl  = route('admin.superadmin-banner.reject', $this->id);

                if ($this->status === 'rejected') {
                    return '<span class="text-danger">' . __('Rejected') . '</span>';
                }

                if ($this->status === 'approved') {
                    return '<span class="text-success">' . __('Approved') . '</span>';
                }
                $approveText = __('Approved');
                $rejectText  = __('Reject');

                return <<<HTML
            <button class="btn btn-success btn-sm approve-btn" data-url="{$approveUrl}">{$approveText}</button>
            <button class="btn btn-danger btn-sm reject-btn" data-url="{$rejectUrl}">✖ {$rejectText}</button>




           HTML;
            });
        }

        Admin::script(<<<JS
            function initBannerRequestActions() {

                function sendRequest(url) {
                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': LA.token,
                            'Accept': 'application/json',
                        },
                    }).then(res => res.json());
                }

                function handleAction(button, actionType) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();

                        var messages = {
                            approve: {
                                title: 'هل أنت متأكد من الموافقة على هذا الطلب؟',
                                confirm: 'نعم',
                                cancel: 'إلغاء',
                                color: '#28a745'
                            },
                            reject: {
                                title: 'هل أنت متأكد من الرفض على هذا الطلب؟',
                                confirm: 'نعم',
                                cancel: 'إلغاء',
                                color: '#dc3545'
                            },
                            success: {
                                en: 'Action completed successfully!',
                                ar: 'تمت العملية بنجاح!',
                                hi: 'क्रिया सफलतापूर्वक पूरी हुई!',
                                tr: 'İşlem başarıyla tamamlandı!'
                            },
                            error: {
                                en: 'An error occurred!',
                                ar: 'حدث خطأ أثناء العملية',
                                hi: 'एक त्रुटि हुई!',
                                tr: 'İşlem sırasında hata oluştu!'
                            }
                        };

                        var locale = document.documentElement.lang || 'ar';

                        Swal.fire({
                            title: messages[actionType].title,
                            type: 'question',
                            showCancelButton: true,
                            confirmButtonText: messages[actionType].confirm,
                            cancelButtonText: messages[actionType].cancel,
                            confirmButtonColor: messages[actionType].color,
                            cancelButtonColor: '#6c757d',
                        }).then(function(result) {
                            if (result.value) {
                                var url = button.dataset.url;

                                Swal.fire({
                                    title: 'جاري التنفيذ...',
                                    allowOutsideClick: false,
                                    onOpen: function() { Swal.showLoading(); }
                                });

                                sendRequest(url)
                                    .then(function(res) {
                                        Swal.close();

                                        if (res.success) {
                                            Swal.fire({
                                                title: res.message || messages.success[locale],
                                                type: 'success',
                                                timer: 2000,
                                                showConfirmButton: false
                                            });

                                            $.pjax.reload('#pjax-container');
                                        } else {
                                            Swal.fire('خطأ', res.message || messages.error[locale], 'error');
                                        }
                                    })
                                    .catch(function(err) {
                                        console.error('Fetch error:', err);
                                        Swal.fire('خطأ', messages.error[locale], 'error');
                                    });
                            }
                        });
                    });
                }

                document.querySelectorAll('.approve-btn').forEach(function(btn) { handleAction(btn, 'approve'); });
                document.querySelectorAll('.reject-btn').forEach(function(btn) { handleAction(btn, 'reject'); });
            }

            initBannerRequestActions();

            $(document).off('pjax:end.bannerRequest').on('pjax:end.bannerRequest', function() {
                initBannerRequestActions();
            });
JS
        );
        $grid->disableActions();
        $grid->disableCreation();
        return $grid;
    }


    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(SuperadminBannerRequest::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('user_id', __('User id'));
        $show->field('home_carousel_id', __('Home carousel id'));
        $show->field('coins_deducted', __('Coins deducted'));
        $show->field('status', __('Status'));
        $show->field('notes', __('Notes'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new SuperadminBannerRequest());

        $form->number('user_id', __('User id'));
        $form->number('home_carousel_id', __('Home carousel id'));
        $form->number('coins_deducted', __('Coins deducted'))->default(10);
        $form->text('status', __('Status'))->default('pending');
        $form->text('notes', __('Notes'));

        return $form;
    }

    public function approve($id)
    {
        Permission::check('edit-' . $this->permission_name);

        $request = SuperadminBannerRequest::findOrFail($id);
        $homeCarousel = $request->homeCarousel;
        $user = $request->user;
        $displayType = preg_replace('/^display_/', '', (string) $request->notes);

        $hours = (int) ($request->hours ?? 1);

        $now = now();

        $homeCarousel->update([
            'enable' => 1,
        ]);

        $display = HomeCarouselDisplay::where('home_carousel_id', $homeCarousel->id)
            ->where('display_type', $displayType)
            ->first();

        if ($display) {
            if ($display->end_at && Carbon::parse($display->end_at)->isFuture()) {
                $existingHours = $this->convertToHours($display->duration, $display->duration_unit);

                $totalHours = $existingHours + $hours;

                $newEndAt = Carbon::parse($display->end_at)->copy()->addHours($hours);

                $display->update([
                    'duration'      => $totalHours,
                    'duration_unit' => 'hours',
                    'end_at'        => $newEndAt,
                ]);
            } else {
                $newEndAt = $now->copy()->addHours($hours);

                $display->update([
                    'duration'      => $hours,
                    'duration_unit' => 'hours',
                    'created_at'    => $now,
                    'end_at'        => $newEndAt,
                ]);
            }
        } else {
            $newEndAt = $now->copy()->addHours($hours);

            HomeCarouselDisplay::create([
                'home_carousel_id' => $homeCarousel->id,
                'display_type'     => $displayType,
                'duration'         => $hours,
                'duration_unit'    => 'hours',
                'created_at'       => $now,
                'end_at'           => $newEndAt,
            ]);
        }

        SuperAdminNotificationHelper::notify(
            type: SuperAdminNotificationType::NEW_ORDER,
            title: 'banner_approved_title',
            message: 'banner_approved_message',
            model: $homeCarousel,
            data: [
                'requested_by' => auth()->user()->name,
                'requested_by_id' => auth()->user()->id,
                'item_id' => $homeCarousel->id,
                'coins_deducted' => $request->coins_deducted,
                'hours' => $hours,
                'preview_url' => SuperAdminNotificationLink::BANNER_APPROVED,

                'translation_params' => [
                    'name' => auth()->user()->name,
                    'id' => auth()->user()->id,
                    'coins' => $request->coins_deducted,
                ]

            ],
            superAdminId: $request->user_id

        );

        $request->update(['status' => 'approved']);

        return response()->json([
            'success' => true,
            'message' => __('Banner approved successfully'),
        ]);
    }


    protected function convertToHours(int $value, string $unit): int
    {
        return match ($unit) {
            'hours'  => $value,
            'days'   => $value * 24,
            'months' => $value * 30 * 24,
            default  => $value,
        };
    }

    public function reject($id)
    {
        Permission::check('edit-' . $this->permission_name);

        $request = SuperadminBannerRequest::findOrFail($id);
        $homeCarousel = $request->homeCarousel;
        SuperAdminHelper::addCoins($request->user_id, $request->coins_deducted);

        $request->status = 'rejected';
        $request->save();
        $hours = (int) ($request->hours ?? 1);
        SuperAdminNotificationHelper::notify(
            type: SuperAdminNotificationType::REGECTED_BANNER_ORDER,
            title: 'banner_rejected_title',
            message: 'banner_rejected_message',
            model: $homeCarousel,
            data: [
                'requested_by' => auth()->user()->name,
                'requested_by_id' => auth()->user()->id,
                'item_id' => $homeCarousel->id,
                'coins_deducted' => $request->coins_deducted,
                'hours' => $hours,
                'preview_url' => SuperAdminNotificationLink::BANNER_REGECTED,

                'translation_params' => [
                    'name' => auth()->user()->name,
                    'id' => auth()->user()->id,
                    'coins' => $request->coins_deducted,
                ]

            ],
            superAdminId: $request->user_id

        );

        return response()->json(['success' => true, 'message' => __('Banner rejected successfully')]);
    }
}
