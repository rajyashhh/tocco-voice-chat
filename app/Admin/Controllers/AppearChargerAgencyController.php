<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\DeleteShippingAgencyAction;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\AgencyJoinRequest;
use App\Models\AgencySallary;
use App\Models\Charge;
use App\Models\CoinLog;
use App\Models\GiftLog;
use App\Models\ShippingAgency;
use App\Models\User;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class AppearChargerAgencyController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    public $permission_name = 'appear-charger-agency';

    public function index(Content $content)
    {
        $content = $content->title(trans('appear-charger-agency'));


        if (Admin::user()->can('actions-switch' . $this->permission_name) || Admin::user()->can('*')) {

            $content = $content->row(function (Row $row) {
                $row->column(12, $this->grid2());
            });
        }


        // Add the second row unconditionally
        $content = $content->row(function ($row) {
            $row->column(12, $this->grid());
        });
        return parent::index($content);
    }

    protected function grid2()
    {
        $transfer_salary = settings()->get('transfer_salary_reliable_shipping_agency');

        return (new Box(
            title: __('admin.Actions'),
            content: view('admin.grid.users.reliable_shipping_agency', compact(['transfer_salary'])),
        ));
    }

    public function transferSalary(Request $request)
    {
        if (!Admin::user()->can('*')) Permission::check('actions-switch' . $this->permission_name);
        if ($request->transfer_salary_reliable_shipping_agency === "true") {
            settings()->set("transfer_salary_reliable_shipping_agency", "1");
        } else {
            settings()->set("transfer_salary_reliable_shipping_agency", "0");
        }
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
            ->title(trans('appear-charger-agency'))
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
            ->title(trans('appear-charger-agency'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('appear-charger-agency'))
            ->body($this->form()));
    }

    public function profile($id, Request $request, Content $content)
    {
        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;
        $tab = request('tab') ?? 'members';


        $agency = Cache::remember("agency_{$id}", 600, function () use ($id) {
            return ShippingAgency::with(['admins', 'owner:id,name,uuid'])
                ->select('id', 'name', 'app_owner_id', 'phone', 'salary', 'coins', 'img')
                ->findOrFail($id);
        });


        $path = $agency->img;
        $defaultImage = asset("images/icon-agency.jpg");
        $imageUrl = getImagePath($path) ?? $defaultImage;
        if (!isImageExists($imageUrl)) {
            $imageUrl = $defaultImage;
        }
        $agency->display_image = $imageUrl;

        $agencyId = $agency->id;

        $members = $charges = $salaries = $agencyJoinRequests = $giftLog = $memberTargets = $agencyTarget = $rate = $stars = $heroes = null;

        switch ($tab) {
            case 'members':
                $members = Cache::remember("agency_{$id}_members_page_" . request('members_page', 1), 600, function () use ($agency) {
                    return $agency->mempers()
                        ->select('id', 'name', 'uuid', 'total_days', 'agency_id', 'country_id')
                        ->with('country', 'agencyUserJob')
                        ->paginate(10, ['*'], 'members_page');
                });
                break;

            case 'charges':
                $charges = Cache::remember("agency_{$id}_charges_page_" . request('charges_page', 1), 600, function () use ($agency) {
                    return $agency->charges()
                        ->select('id', 'amount', 'created_at')
                        ->latest()
                        ->paginate(10, ['*'], 'charges_page');
                });
                break;

            case 'salary':
                $salaries = Cache::remember("agency_{$id}_salaries_page_" . request('salary_page', 1), 600, function () use ($id) {
                    return AgencySallary::where('agency_id', $id)
                        ->select('id', 'sallary', 'cut_amount', 'month', 'year', 'created_at')
                        ->orderByDesc('id')
                        ->paginate(10, ['*'], 'salary_page');
                });
                break;

            case 'requests':
                $agencyJoinRequests = Cache::remember("agency_{$id}_requests_page_" . request('join_page', 1), 600, function () use ($id) {
                    return AgencyJoinRequest::where(['agency_id' => $id, 'status' => 0])
                        ->with('user')
                        ->whereHas('user')
                        ->orderByDesc('id')
                        ->paginate(10, ['*'], 'join_page');
                });
                break;

            case 'targets':
                $memberTargets = Cache::remember("agency_{$id}_targets_{$month}_{$year}_page_" . request('target_page', 1), 600, function () use ($agency, $agencyId, $month, $year) {
                    return $agency->mempers()->with(['targets' => function ($query) use ($agencyId, $month, $year) {
                        $query->where('agency_id', $agencyId)
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', $year);
                    }])->paginate(10, ['*'], 'target_page');
                });

                [$agencyTarget, $rate] = Cache::remember("agency_{$id}_rate_{$month}_{$year}", 600, fn() => $this->rateAgency($agencyId, $month, $year));
                $stars = Cache::remember("agency_{$id}_stars_{$month}_{$year}", 600, fn() => $this->giftLogByAgency('receiver', $month, $year, $agencyId, 'receiver_id'));
                $heroes = Cache::remember("agency_{$id}_heroes_{$month}_{$year}", 600, fn() => $this->giftLogByAgency('sender', $month, $year, $agencyId, 'sender_id'));

                break;
        }

        $giftLog = Cache::remember("agency_{$id}_giftlog", 600, function () use ($id) {
            return GiftLog::where('agency_id', $id)
                ->selectRaw("SUM(giftPrice) as exp, receiver_id")
                ->with('receiver')
                ->groupBy('receiver_id')
                ->whereHas('receiver')
                ->orderByDesc('exp')
                ->get();
        });

        $data = compact(
            'agency',
            'members',
            'charges',
            'salaries',
            'agencyJoinRequests',
            'giftLog',
            'memberTargets',
            'agencyTarget',
            'rate',
            'stars',
            'heroes',
            'tab'
        );

        return $content->title(__('agency profile'))
            ->view('agency_profile', $data);
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        $grid = new Grid(new ShippingAgency());
        $countryID = Common::filterCountryIds();


        $grid->model()->with([
            'owner.profile',
            'owner.country',
            'owner.senderLevel',
            'owner.receiverLevel',
            'owner.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
            'creator',
            'country'
        ])
            ->withCount('chargeAgency as charge_agency_exists')
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->orderByDesc('id');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->like('owner.uuid', __('UUID'))->placeholder(__('Search by UUID'));
        });


        $grid->column('id', __('Id'))->sortable();

        $grid->column('name', __('Agency'))
            ->display(function ($name) {
                $path = @$this->img;
                $defaultImage = asset("images/icon-agency.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                $image = "<img src='{$url}' onerror=\"this.onerror=null;this.src='{$defaultImage}'\" style='height:40px !important; width:40px !important; border-radius:50%; object-fit:cover;' alt='' />";

                $flagHtml = '';
                if (!empty($this->country?->flag)) {
                    $flagPath = getImagePath($this->country->flag);
                    $flagTitle = app()->getLocale() === 'ar'
                        ? e($this->country->name)
                        : e($this->country->e_name);

                    $flagHtml = "<img src='{$flagPath}'
                         class='flag-image'
                         alt='flag Image'
                         title='{$flagTitle}'
                         style='width:20px;height:auto;vertical-align:middle;margin-left:5px;'>";
                }

                $profileUrl = route('admin.shipping.agency.profile', ['id' => $this->id]);

                return "
                    <a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            {$image}
                            <div style='display: flex; flex-direction: column;'>
                                <span style='text-decoration: underline; cursor: pointer;'>{$name}</span>{$flagHtml}<br>
                                <span style='font-size: smaller;'>ID: {$this->id}</span>
                            </div>
                        </div>
                    </a>
                ";
            })->sortable();

        $grid->column('ownerName', __('Owner'))->display(function () {
            return app(UserService::class)->adminUserCard($this->owner);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        $grid->column('charge_agency', __("Charge-agency"))
            ->display(function () {
                return $this->charge_agency_exists > 0 ? 1 : 0;
            })
            ->switch(Common::getSwitchStates())->sortable();

        $grid->column('appear_charger_agency', __("Appear charger agency"))
            ->display(function () {
                return $this->owner && $this->owner->appear_charger_agency ? 1 : 0;
            })
            ->switch(Common::getSwitchStates())->sortable();

        $grid->column('is_frozen', __("frozen"))
            ->display(function () {
                return $this->is_frozen ? 1 : 0;
            })
            ->switch(Common::getSwitchStates())->sortable();
        $permission = $this->permission_name;
        $grid->column('created_by', __('Creator'))->display(function ($creatorId) {
            if ($this->creator) {
                return app(\App\Admin\Services\CreatorService::class)->show($this->creator);
            }
            return app(\App\Admin\Services\CreatorService::class)->show($creatorId);
        });
        $grid->actions(function ($actions) use ($permission) {
            $actions->disableView();
            if (Admin::user()->can('delete-switch-' . $permission) || Admin::user()->can('*')) {

                $actions->add(new DeleteShippingAgencyAction());
            }
            $actions->disableDelete();
        });
        $grid->disableExport();
        $grid->tools(function (Grid\Tools $tools) {
            $query = request()->query(); // يحصل على كل الفلاتر المفعّلة في الصفحة

            $exportUrl = route('charge-agency-export-report') . '?' . http_build_query($query);

            $tools->append('<a href="' . $exportUrl . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i> ' . __('admin.exportExcel') . '</a>');
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
        $show = new Show(User::findOrFail($id));

        // $show->field('id', __('Id'));
        // $show->field('name', __('Name'));
        // $show->field('phone', __('Phone'));
        // $show->field('uuid', __('Uuid'));
        // $show->field('appear_charger_agency', __('Appear charger agency'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new ShippingAgency());
        $this->disableFormTools($form);

        // --- الحقول المشتركة ---
        $form->display('ID');

        $form->select('app_owner_id', __('app owner id'))
            ->options(function ($value) {
                if (!$value) return [];
                $user = User::find($value);
                return $user ? [$user->id => $user->uuid . '_' . $user->name] : [];
            })
            ->ajax('/api/search/users5', 'id', 'name')->rules('required');

        $form->hidden('agency_manger_id', __('app manger id'));

        $form->text('name', __('name'))->rules('required');
        $form->switch('status', __('status'));

        $form->text('phone', __('agency whatsApp number'))->attribute('id', 'phone-input');
        $form->url('url', __('url'));
        $form->hidden('is_frozen', __('is_frozen'))->default(0);
        $form->hidden('type', __('type'))->default(2);



        // --- عرض تنبيه لو موجود في السيشن ---
        if (Session::has('show_alert')) {
            $form->html('<script>
                $(document).ready(function () {
                    alert("الرجاء اختيار نوع الوكالة اولا");
                });
            </script>');
        }
        // $form->hidden('Shipping_agency')->default(1);

        Admin::script(<<<'JS'
        function initPhoneInput() {
            const input = document.querySelector("#phone-input");
            if (!input || input.classList.contains('iti-initialized')) return;

            // Wait for intlTelInput to be available
            if (typeof window.intlTelInput !== 'function') {
                setTimeout(initPhoneInput, 150);
                return;
            }

            const parentDiv = input.parentElement;
            parentDiv.style.position = 'relative';

            const iti = window.intlTelInput(input, {
                separateDialCode: true,
                preferredCountries: ["eg"],
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
            });

            document.head.insertAdjacentHTML('beforeend', `
                <style>
                    .iti { width: 100%;  }
                    .iti__flag-container { z-index: 99; }
                    #phone-input {
                        padding-left: 90px !important;
                        width: 50%;
                    }
                    .fields-group .form-group { overflow: visible; }
                </style>
            `);

            input.classList.add('iti-initialized');

            const form = input.closest('form');
            if (form && !form.classList.contains('phone-init')) {
                form.addEventListener('submit', function () {
                    if (iti) {
                        const dialCode = iti.getSelectedCountryData().dialCode;
                        const nationalNumber = input.value.replace(/\s/g, '');

                        const hiddenInput = document.createElement('input');
                        hiddenInput.name = 'phone_code';
                        hiddenInput.value = `+${dialCode}`;
                        form.appendChild(hiddenInput);

                        input.value = nationalNumber;
                    }
                });
                form.classList.add('phone-init');
            }
        }

        // Run after select2 and other scripts have initialized
        setTimeout(initPhoneInput, 200);
    JS);
        // $form->hidden('Shipping_agency')->default(1);

        // --- الأحداث عند الحفظ ---
        $form->saving(function (Form $form) {

            $form->phone_code = request('phone_code');
            $appOwnerId = $form->input('app_owner_id');
            $originalOwnerId = $form->model()->getOriginal('app_owner_id');
            $newOwnerId = $form->model()->app_owner_id;

            if (!$form->model()->exists) {
                //  Common::createUserAdmin($appOwnerId);
            }

            if ($form->model()->exists && $newOwnerId != $originalOwnerId) {
                //  Common::createUserAdmin($appOwnerId);

                $user = User::find($originalOwnerId);
                $agencyId = $form->model()->id;


                Admin::where('username', $user->uuid)->delete();
            }
        });

        $form->saved(function (Form $form) {

            $appOwnerId = intval($form->model()->app_owner_id);
        });

        return $form;
    }

    public function shippingProfile($id, Request $request, Content $content)
    {
        $tab = $request->input('tab', 'charges');

        $agency = Cache::remember("agency_{$id}", 600, function () use ($id) {
            return ShippingAgency::with(['admins', 'owner:id,name,uuid'])
                ->select('id', 'name', 'app_owner_id', 'phone', 'coins', 'img')
                ->findOrFail($id);
        });

        $agencyId = $agency->id;

        // Load charges (sent transactions)
        $chargesQuery = Charge::where('charger_type', 'agency')
            ->where('charger_id', $agencyId);

        $relations = [];
        if ($request->has('filter_by') && $request->filter_by !== null && $request->filter_by !== '') {
            $chargesQuery->where('user_type', $request->filter_by);
            $relations[] = $request->filter_by === 'user' ? 'receiverUser' : 'receiverAgency';
        }
        if ($request->has('filter_id') && $request->filter_id !== null && $request->filter_id !== '') {
            $chargesQuery->where('user_id', $request->filter_id);
        }
        if (!empty($relations)) {
            $chargesQuery->with($relations);
        }
        $charges = $chargesQuery->latest()->paginate(10, ['*'], 'charges_page');

        // Load received transactions
        $resivedsQuery = Charge::where('user_id', $agencyId)
            ->where('user_type', 'agency');
        if ($request->has('sender_type') && $request->sender_type !== null && $request->sender_type !== '') {
            $resivedsQuery->where('charger_type', $request->sender_type);
        }
        if ($request->has('sender_id') && $request->sender_id !== null && $request->sender_id !== '') {
            $resivedsQuery->where('charger_id', $request->sender_id);
        }
        $resiveds = $resivedsQuery->with(['sender'])->latest()->paginate(10, ['*'], 'resived_page');

        // Load coin logs
        $coinLogs = CoinLog::where('user_id', $agencyId)
            ->where('user_type', 'shipping_agency')
            ->latest()
            ->paginate(10, ['*'], 'coins_page');

        $totalReceive = Charge::where('user_id', $agencyId)->where('user_type', 'agency')->sum('amount');
        $totalSend = Charge::where('charger_type', 'agency')->where('charger_id', $agencyId)->sum('amount');

        return $content->title(__('agency profile'))
            ->view('shippingAgencyProfile', compact(
                'agency',
                'resiveds',
                'coinLogs',
                'charges',
                'tab',
                'totalReceive',
                'totalSend'
            ));
    }
}
