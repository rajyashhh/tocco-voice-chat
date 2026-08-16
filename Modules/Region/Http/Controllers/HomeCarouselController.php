<?php

namespace Modules\Region\Http\Controllers;

use App\Enums\AdminNotificationLink;
use App\Enums\AdminNotificationType;
use App\helper\SuperAdminHelper;
use App\Helpers\AdminNotificationHelper;
use App\Models\SuperadminBannerRequest;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Helpers\Common;
use App\Models\HomeCarousel;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\MainController;
use Encore\Admin\Grid\Tools;
class HomeCarouselController extends MainController
{
    use HasResourceActions;

    public function index(Content $content)
    {
        return $content
        ->header(trans('Banner'))
        ->row(function ($row) {
            $row->column(12, $this->grid());
        });

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
        return $content
            ->title(trans('HomeCarousel'))
            ->body($this->detail($id));
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
        return $content
        ->title(trans('HomeCarousel'))
        ->body($this->form()->edit($id));
    }

    public function create(Content $content)
    {
        return $content
        ->title(trans('HomeCarousel'))
            ->body($this->form());
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $itemNotification = request('itemNotification');

        $grid = new Grid(new HomeCarousel);
        $grid->model()->when($itemNotification, function ($query, $itemNotification) {
            $query->where('id', $itemNotification);
        });
        $grid->model()->whereHas('countries', function ($q) {
            $q->where('countries.id', auth()->user()->country_id);
        });

        $grid->id(__('ID'));
        $grid->column('img', __('Image'))->image('', 235, 77);

        $grid->column('actions', __('Actions'))->display(function () {

            // 'display_room' removed: the app renders no such placement, so
            // selling it charged managers for a banner that never appears.
            $types = [
                'display_discover' => __('Display Discover'),
                'display_home_top' => __('Display Home Top'),
                'display_home_middle' => __('Display Home Middle'),
                'display_live' => __('Display Live'),
            ];

            $buttons = '';
            foreach ($types as $type => $label) {
                $buttons .= '<button class="btn btn-sm btn-primary request-banner me-1 mb-1"
                                data-id="' . $this->id . '"
                                data-type="' . $type . '">
                                <i class="fa fa-bullhorn"></i> ' . $label . '
                             </button>';
            }
            return $buttons;
        });

        $grid->disableExport();
        $grid->disableActions();

        $display_prices = [
            'display_discover' => SuperAdminHelper::getHourlyBannerPrice('display_discover'),
            'display_home_top' => SuperAdminHelper::getHourlyBannerPrice('display_home_top'),
            'display_home_middle' => SuperAdminHelper::getHourlyBannerPrice('display_home_middle'),
            'display_live' => SuperAdminHelper::getHourlyBannerPrice('display_live'),
        ];

        $translations = [
            'confirm_deduction' => __('Confirm Deduction'),
            'deduct_text' => __('coins will be deducted to send the display request: '),
            'yes_deduct' => __('Yes, deduct and send request'),
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
            ],
        ];
        $translationsJson = json_encode($translations);
        $displayPricesJson = json_encode($display_prices);
        Admin::script("
        const translations = $translationsJson;
        const display_prices = $displayPricesJson;

        function bindBannerRequestButtons() {
            $(document).off('click', '.request-banner').on('click', '.request-banner', function() {
                var bannerId = $(this).data('id');
                var displayType = $(this).data('type');
                var displayLabel = translations.display_types[displayType] || displayType;
                var deductAmount = display_prices[displayType] || 0;

                if (typeof Swal === 'undefined') {
                    alert(translations.sweetalert_missing);
                    return;
                }
                Swal.fire({
                    title: translations.confirm_deduction,
                    html: '<p id=\"deduct_message\">' + translations.deduct_text + ' ' + deductAmount + ' coins per hour</p>' +
                        '<label>' + displayLabel + ' Hours:</label>' +
                        '<input type=\"number\" id=\"banner_hours\" class=\"swal2-input\" min=\"1\" value=\"1\">',
                    type: 'warning', // في الإصدارات الحديثة استعمل icon بدل type
                    showCancelButton: true,
                    confirmButtonText: translations.yes_deduct,
                    cancelButtonText: translations.cancel,
                    onOpen: function() { // بدل didOpen
                        var hoursInput = document.getElementById('banner_hours');
                        var message = document.getElementById('deduct_message');

                        hoursInput.addEventListener('input', function() {
                            var hours = parseFloat(hoursInput.value);
                            if (isNaN(hours) || hours < 1) hours = 1;
                            var total = hours * deductAmount;
                            message.textContent = translations.deduct_text + ' ' + total + ' coins';
                        });
                    },
                    preConfirm: function() {
                        var hours = parseInt(document.getElementById('banner_hours').value);
                        if (isNaN(hours) || hours < 1) {
                            Swal.showValidationMessage('Please enter a valid number of hours');
                            return false;
                        }
                        return hours;
                    }


                }).then((result) => {
                    if (result.value) {
                        const hours = result.value;
                        const totalDeduct = hours * deductAmount;
                        $.ajax({
                            url: '/superadmin/banner-request/' + bannerId,
                            type: 'POST',
                            data: {
                                _token: LA.token,
                                field: displayType,
                                hours: hours,
                                total: totalDeduct
                            },
                            success: function(response) {
                                Swal.fire({
                                    title: translations.done,
                                    text: response.message,
                                    type: 'success'
                                }).then(() => { $.pjax.reload('#pjax-container'); });
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

        $(function() { bindBannerRequestButtons(); });
        $(document).on('pjax:complete', function() { bindBannerRequestButtons(); });
    ");



        $grid->tools(function (Tools $tools) {
            $tools->append('<a href="' . superadmin_url('home-carousel/history') . '"  class="btn btn-sm btn-success">' . __('admin.history') . '</a>');
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
        $show = new Show(HomeCarousel::findOrFail($id));



        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */

     protected function form()
     {
         $form = new Form(new HomeCarousel);

         $this->disableFormTools($form);

         $this->addBasicFields($form);
         $this->addTimeSettings($form);
         $this->addContentType($form);
         $this->addCountryDisplay($form);

         $this->syncCountryDisplayBeforeSave($form);
         $this->syncCountryRelationsAfterSave($form);

         return $form;
     }


     protected function addBasicFields(Form $form)
     {
         $form->display(__('admin.ID'));
         $form->number('sort', __('Sort'))->default(1);
         $form->image('img', __('Image'))->uniqueName()->required();
         $form->switch('enable', __('Enable'))->states(Common::getSwitchStates())->default(true);
     }


     protected function addTimeSettings(Form $form)
     {
         $form->select('form', __('Time View Type'))->options([
             0 => __(''),
             1 => __('Hours'),
             2 => __('Days'),
             3 => __('Months')
         ])->when('1', fn(Form $form) => $form->number('input', __('Input'))->min(1))
           ->when('2', fn(Form $form) => $form->number('input', __('Input'))->min(1))
           ->when('3', fn(Form $form) => $form->number('input', __('Input'))->min(1));
     }


     protected function addContentType(Form $form)
     {
         $form->select('type', __('Type'))->options([
             'room'   => __('Room'),
             'normal' => __('Normal'),
             'link'   => __('URL'),
             'event'  => __('Events'),
         ])->when('room', function (Form $form) {
             $form->select('owner_id', __('Owner'))
                 ->options('/api/search/users2')
                 ->ajax('/api/search/users2', 'id', 'name');
         })->when('link', function (Form $form) {
             $form->url('url', __('URL'))->rules('required|url');
         })->when('event', function (Form $form) {
             $form->select('event_type', __('Events'))->options([
                 'event'        => __('events'),
                 'pk_event'     => __('pk_event'),
                 'weekly_star'  => __('weekly_star'),
                 'charge_event' => __('charge_event'),
                 'event_period' => __('event_period'),
                 'weekly_cp'    => __('weekly_cp'),
             ]);
         });
     }

     protected function addCountryDisplay(Form $form)
     {
         $form->hidden('display_at')->default(json_encode(['country']));

        //  $form->belongsToMany('countries', Countries::class, __('Country'))->required();
     }


     protected function syncCountryDisplayBeforeSave(Form $form)
     {
         $form->saving(function (Form $form) {
             $form->model()->display_at = json_encode(['country']);
             $form->display_at = json_encode(['country']);
         });
     }


     protected function syncCountryRelationsAfterSave(Form $form)
     {
         $form->saved(function (Form $form) {
             $model = $form->model();

             $formInput = request('input') ?? $model->input ?? 0;
             $formForm  = request('form') ?? $model->form ?? 1;

             $unit = match ($formForm) {
                 1 => 'hours',
                 2 => 'days',
                 3 => 'months',
                 default => 'hours',
             };

             $start = now();
             $endAt = match ($unit) {
                 'hours'  => $start->copy()->addHours($formInput),
                 'days'   => $start->copy()->addDays($formInput),
                 'months' => $start->copy()->addMonths($formInput),
                 default  => $start->copy()->addHours($formInput),
             };

             $display = $model->displays()->firstOrNew(['display_type' => 'country']);
             $display->fill([
                 'duration'      => $formInput,
                 'duration_unit' => $unit,
                 'created_at'    => $start,
                 'end_at'        => $endAt,
             ]);
             $display->save();

             $countries = Auth::user()->country_id;
             $model->countries()->sync($countries);
         });
     }



     public function storeBannerRequest($bannerId, Request $request)
     {
         $banner = HomeCarousel::findOrFail($bannerId);
         $user   = auth()->user();
         $field  = $request->field;
         $hours  = $request->hours;


         if ($this->hasActiveDisplay($banner, $field)) {
             return $this->errorResponse(__('A display of this type is already active for this banner.'));
         }

         if ($this->hasPendingRequest($user, $banner, $field)) {
             return $this->errorResponse(__('You already have a pending request for this banner and display type.'));
         }

         if ($this->hasViewRequest($user, $banner, $field)) {
            return $this->errorResponse(__('This banner is already displayed in the selected section.'));
        }

         $totalDeduct = $this->calculateDeduction($request, $field);

         if ($user->di < $totalDeduct) {
             return $this->errorResponse(__('insufficient_balance'));
         }

         $this->processBannerRequest($user, $banner, $field, $totalDeduct ,$hours);

         return response()->json(['message' => __('request_sent_success')]);
     }

     protected function hasActiveDisplay($banner, $field)
     {
         return $banner->displays()
             ->where('display_type', $field)
             ->where(function ($q) {
                 $q->where('end_at', '>', now())
                   ->orWhereNull('end_at');
             })
             ->exists();
     }

     protected function hasPendingRequest($user, $banner, $field)
     {
         return SuperadminBannerRequest::where('user_id', $user->id)
             ->where('home_carousel_id', $banner->id)
             ->where('notes', $field)
             ->where('status', 'pending')
             ->exists();
     }

     protected function hasViewRequest($user, $banner, $field)
     {
        $field = str_replace('display_', '', $field);

         return HomeCarousel::query()
             ->where('id', $banner->id)
             ->whereHas('displays', function ($q) use ($field) {
                 $q->where('display_type', $field);
             })
             ->exists();
     }

     protected function calculateDeduction(Request $request, $field)
     {
         $hours       = (int) $request->input('hours', 1);
         $hourlyPrice = SuperAdminHelper::getHourlyBannerPrice($field);
         return $hours * $hourlyPrice;
     }

     protected function processBannerRequest($user, $banner, $field, $totalDeduct ,$hours)
     {
         \DB::transaction(function () use ($user, $banner, $field, $totalDeduct,$hours) {
             $user->decrement('di', $totalDeduct);

            $req = SuperadminBannerRequest::create([
                 'user_id'          => $user->id,
                 'home_carousel_id' => $banner->id,
                 'coins_deducted'   => $totalDeduct,
                 'status'           => 'pending',
                 'notes'            => $field,
                 'hours'            => $hours,
             ]);
             AdminNotificationHelper::notify(
                type: AdminNotificationType::NEW_ORDER,
                title: 'banner_request_title',
                message: __(
                    'banner_request_message',
                    [
                        'name' => auth()->user()->name,
                        'id' => auth()->user()->id,
                        'coins' => $totalDeduct,
                    ]
                ),
                model: $banner,
                data: [
                    'requested_by' => auth()->user()->name,
                    'requested_by_id' =>auth()->user()->id,
                    'item_id' => $req->id,
                    'coins_deducted' => $totalDeduct,
                    'hours' => $hours,
                    'notes' => $field,
                    'preview_url' => AdminNotificationLink::BANNER_SHOW,
                ]
            );
         });
     }

     protected function errorResponse($message)
     {
         return response()->json(['message' => $message], 422);
     }




     public function resendBannerRequest($bannerId, Request $request)
    {
        $bannerRequest = $this->getLatestBannerRequest($bannerId);
        $user = auth()->user();

        if (!$bannerRequest) {
            return $this->errorResponse(__('No previous banner request found.'));
        }

        $field = $bannerRequest->notes;
        $hours = (int) ($bannerRequest->hours ?? 1);
        $totalDeduct = $this->calculateDeductionForResend($field, $hours);

        if ($user->di < $totalDeduct) {
            return $this->errorResponse(__('insufficient_balance'));
        }

        $this->processResendRequest($user, $bannerRequest, $totalDeduct);

        return response()->json(['message' => __('Banner request resent successfully.')]);
    }

    protected function getLatestBannerRequest($bannerId)
    {
        return SuperadminBannerRequest::where('home_carousel_id', $bannerId)
            ->where('user_id', auth()->id())
            ->latest()
            ->first();
    }

    protected function calculateDeductionForResend($field, $hours)
    {
        $hourlyPrice = SuperAdminHelper::getHourlyBannerPrice($field);
        return $hours * $hourlyPrice;
    }

    protected function processResendRequest($user, $bannerRequest, $totalDeduct)
    {
        \DB::transaction(function () use ($user, $bannerRequest, $totalDeduct) {
            $user->decrement('di', $totalDeduct);

            $bannerRequest->update([
                'status'         => 'pending',
                'coins_deducted' => $totalDeduct,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });
    }




}

