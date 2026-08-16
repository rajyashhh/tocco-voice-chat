<?php

namespace Modules\Region\Http\Controllers;

use Carbon\Carbon;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Tools;
use Encore\Admin\Facades\Admin;
use App\helper\SuperAdminHelper;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Auth;
use App\Models\SuperadminBannerRequest;
use App\Admin\Controllers\MainController;
use App\Admin\Services\UserSuperAdminService;



class SuperadminBannerHistoryController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'SuperadminBannerRequest';


    public function __construct(UserSuperAdminService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Content $content)
    {
        return $content
            ->title(__('Banner Request History'))
            ->body($this->grid());
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SuperadminBannerRequest());
        $grid->model()->with(['homeCarousel:home_carousel_id.img'])->latest();
        $grid->model()->where('user_id', Auth::user()->id);
        $grid->filter(function (Grid\Filter $filter) {
            $filter->disableIdFilter();
            $filter->expand();


            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($from = request('from_date')) {
                        $start = Carbon::parse(convertArabicToEnglishNumbers($from))->startOfDay();
                        $query->whereDate('created_at',  $start);
                    }
                }, __('created_at'), 'from_date')->date();
            });
        });
        $grid->column('id', __('ID'));


        $grid->column('homeCarousel.img', __('img'))->image('', 235, 77);


        $grid->column('coins_deducted', __('Coins Deducted'))->display(function ($coins) {

            $image = asset('images/coin.png'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$coins}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });

        $grid->column('status', __('Status'))->display(function ($status) {
            switch ($status) {
                case 'approved':
                    return '<span class="text-success">✅ ' . __('Approved') . '</span>';
                case 'pending':
                    return '<span class="text-warning">⏳ ' . __('Pending') . '</span>';
                case 'rejected':
                    return '<span class="text-danger">❌ ' . __('Rejected') . '</span>';
                case 'canceled':
                    return '<span class="text-secondary">🚫 ' . __('Canceled') . '</span>';
                default:
                    return e($status);
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

        $grid->column('created_at', __('Created At'))->display(function ($createdAt) {
            return \Carbon\Carbon::parse($createdAt)->format('d/m/Y H:i');
        });


        $grid->column('actions', __('Actions'))->display(function () {
            if ($this->status === 'rejected') {
                return  '<button class="btn btn-sm btn-primary resend-btn"
                            data-id="' . $this->home_carousel_id . '"
                            data-notes="' . e($this->notes) . '"
                            data-hours="' . e($this->hours) . '">
                         <i class="fa fa-refresh"></i>
                    </button>';
            }


        });

        // === الأسعار لكل نوع عرض ===
        $display_prices = [
            'display_discover' => SuperAdminHelper::getHourlyBannerPrice('display_discover'),
            'display_home_top' => SuperAdminHelper::getHourlyBannerPrice('display_home_top'),
            'display_home_middle' => SuperAdminHelper::getHourlyBannerPrice('display_home_middle'),
            'display_live' => SuperAdminHelper::getHourlyBannerPrice('display_live'),
        ];

        // === الترجمات ===
        $translations = [
            'confirm_resend' => __('confirm_resend'),
            'deduct_text' => __('deduct_text'),
            'yes_resend' => __('Yes, deduct and send request'),
            'cancel' => __('Cancel'),
            'done' => __('Done!'),
            'error_text' => __('An error occurred while processing'),
            'error' => __('Error'),
            'sweetalert_missing' => __('SweetAlert2 is not loaded!'),
            'display_types' => [
                'display_discover' => __('Display Discover'),
                'display_home_top' => __('Display Home Top'),
                'display_home_middle' => __('Display Home Middle'),
                'display_live' => __('Display Live'),
                'display_room' => __('Display Room'),

            ],
            'total' => __('Total'),
            'hours' => __('Hours'),
        ];

        $translationsJson = json_encode($translations);
        $displayPricesJson = json_encode($display_prices);

        $grid->disableActions();
        $grid->disableCreateButton();

        Admin::script(<<<JS
        console.log('✅ Resend banner script loaded');
        const translations = $translationsJson;
        const display_prices = $displayPricesJson;

        function bindResendButtons() {
            $(document).off('click', '.resend-btn').on('click', '.resend-btn', function() {
                var bannerId = $(this).data('id');
                var displayType = $(this).data('notes');
                var displayLabel = translations.display_types[displayType] || displayType;
                var hours = parseInt($(this).data('hours')) || 1;
                var pricePerHour = display_prices[displayType] || 0;
                var total = pricePerHour * hours;

                if (typeof Swal === 'undefined') {
                    alert(translations.sweetalert_missing);
                    return;
                }

                Swal.fire({
                    title: translations.confirm_resend,
                    html: `
                        <p><strong>\${displayLabel}</strong></p>
                        <p>\${translations.hours}: <strong>\${hours}</strong></p>
                        <p>\${translations.total}: <strong>\${total}</strong> coins</p>
                        <p>\${translations.deduct_text}</p>
                    `,
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonText: translations.yes_resend,
                    cancelButtonText: translations.cancel,
                }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            url: 'resend-banner-request/' + bannerId,
                            type: 'POST',
                            data: {
                                _token: LA.token,
                                field: displayType,
                                id: bannerId
                            },
                            success: function(res) {
                                Swal.fire({
                                    title: translations.done,
                                    text: res.message,
                                    type: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    $.pjax.reload('#pjax-container');
                                });
                            },
                            error: function(xhr) {
                                let msg = xhr.responseJSON?.message || translations.error_text;
                                Swal.fire(translations.error, msg, 'error');
                            }
                        });
                    }
                });
            });
        }

        $(function() {
            bindResendButtons();
            console.log('✅ Bound resend buttons');
        });

        $(document).on('pjax:complete', function() {
            bindResendButtons();
            console.log('🔁 Rebound resend buttons after PJAX');
        });
        JS);




        $grid->tools(function (Tools $tools) {
            $tools->append('<a href="' . superadmin_url('home-carousel') . '" class="btn btn-sm btn-default">
            <i class="fa fa-arrow-left"></i> ' . __('Back') . '</a>');
        });

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
}
