<?php

namespace Modules\Region\Http\Controllers;

use App\Admin\Actions\ChangeUsersAgencyAction;
use App\Admin\Controllers\MainController;
use App\AreaManager\Actions\DeleteAgencyAction;
use App\Helpers\Common;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\AgencySallary;
use App\Models\Bd;
use App\Models\Charge;
use App\Models\GiftLog;
use App\Models\ShippingAgency;
use App\Models\Target;
use App\Models\User;
use App\Models\UserSallary;
use App\Models\UsersJoinedAgency;
use App\Services\AppFeatureService;
use Carbon\Carbon;
use Encore\Admin\Actions\Response;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request as req;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\MessageBag;

class AgencyController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'agency';

    public $hiddenColumns = [];

    protected $title = 'Agency';

    public function __construct()
    {
        (new AppFeatureService)->validateStatusEnable("agencies");
    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Agencies'))
            ->description(__('List of Agencies'))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    protected function authorizeAgency($id): void
    {
        // Tenant isolation: an area manager may only act on agencies whose
        // country falls within the countries scoped to that manager.
        $inScope = Agency::where('id', $id)
            ->whereIn('country_id', Common::areaCountries())
            ->exists();

        if (!$inScope) {
            abort(404, __('Agency not found'));
        }
    }

    public function edit($id, Content $content)
    {
        $this->authorizeAgency($id);

        return parent::edit($id, $content
            ->title(__(@$this->title ?? ''))
            ->body($this->form()->edit($id)));
    }

    public function update($id)
    {
        $this->authorizeAgency($id);

        return parent::update($id);
    }

    public function destroy($id)
    {
        $this->authorizeAgency($id);

        return parent::destroy($id);
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(__($this->title))
            ->body($this->form()));
    }

    public function show($id, Content $content)
    {
        return $this->profile($id, request(), $content);
    }
    public function profile($id, req $request, Content $content)
    {
        if (! Admin::user()->can('*')) {
            Permission::check('show-' . $this->permission_name);
        }

        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;
        $tab = request('tab', 'members');
        $countries = Common::areaCountries();

        $agency = Agency::query()
            ->whereIn('country_id', $countries)
            ->with(['admins', 'owner:id,name,uuid', 'bd','owner.profile'])
            ->select(['id', 'name', 'app_owner_id', 'phone', 'coins','bd_id', 'img', 'type'])
            ->find($id);

        if (!$agency) {
            $agency = ShippingAgency::query()
                ->whereIn('country_id', $countries)
                ->with(['admins', 'owner:id,name,uuid', 'owner.profile'])
                ->select(['id', 'name', 'app_owner_id', 'phone', 'coins', 'img', 'type'])
                ->find($id);
        }

        if (!$agency) {
            $error = new MessageBag([
                'title' => __('error_title_div'),
                'message' => __('Agency not found'),
            ]);

            session()->flash('error', $error);
            throw new \Exception(__('Agency not found'));
        }
        if ($agency->type == 2) {
            return self::shippingProfile($agency, $request, $content);
        }

        $path = $agency?->img;
        $defaultImage = asset("images/icon-agency.jpg");
        $imageUrl = getImagePath($path);
        if (!isImageExists($imageUrl)) {
            $imageUrl = $defaultImage;
        }

        $agencyId = $agency->id ?? $id;

        $members = $charges = $salaries = $agencyJoinRequests = $giftLog = $memberTargets = $agencyTarget = $rate = $stars = $heroes = null;

        switch ($tab) {
            case 'members':
                $members =
                    $agency->mempers()
                    ->select('id', 'name', 'uuid', 'total_days', 'agency_id', 'country_id')
                    ->with('country', 'agencyUserJob')
                    ->paginate(10, ['*'], 'members_page');
                break;

            case 'charges':
                $charges =
                    $agency->senderCharges()
                    ->with(Common::chargerRelationsQuery())
                    ->latest()
                    ->paginate(10, ['*'], 'charges_page');
                break;

            case 'salary':
                $salaries =
                    AgencySallary::query()
                    ->where('agency_id', $id)
                    ->select(['id', 'sallary', 'cut_amount', 'month', 'year', 'created_at'])
                    ->orderByDesc('id')
                    ->paginate(10, ['*'], 'salary_page');
                break;

            case 'requests':
                $agencyJoinRequests =
                    AgencyJoinRequest::query()
                    ->where(['agency_id' => $id, 'status' => 0])
                    ->with('user')
                    ->whereHas('user')
                    ->orderByDesc('id')
                    ->paginate(10, ['*'], 'join_page');
                break;

            case 'targets':

                $memberTargets =
                    $agency
                    ->mempers()
                    ->whereHas('targets', function ($query) use ($agencyId, $month, $year) {
                        $query
                            ->where('agency_id', $agencyId)
                            ->where('add_month', $month)
                            ->where('add_year', $year);
                    })
                    ->with(['targets' => function ($query) use ($agencyId, $month, $year) {
                        $query
                            ->where('agency_id', $agencyId)
                            ->where('add_month', $month)
                            ->where('add_year', $year);
                    }])
                    ->paginate(10, ['*'], 'target_page');


                [$agencyTarget, $rate] = Cache::remember(
                    "agency_{$id}_rate_{$month}_{$year}",
                    600,
                    fn() => $this->rateAgency($agencyId, $month, $year),
                );
                $stars = Cache::remember(
                    "agency_{$id}_stars_{$month}_{$year}",
                    600,
                    fn() => $this->giftLogByAgency('receiver', $month, $year, $agencyId, 'receiver_id'),
                );
                $heroes = Cache::remember(
                    "agency_{$id}_heroes_{$month}_{$year}",
                    600,
                    fn() => $this->giftLogByAgency('sender', $month, $year, $agencyId, 'sender_id'),
                );

                break;
        }

        $giftLog =
            GiftLog::where('agency_id', $id)
            ->selectRaw("SUM(giftPrice) as exp, receiver_id")
            ->with('receiver')
            ->groupBy('receiver_id')
            ->whereHas('receiver')
            ->orderByDesc('exp')
            ->get();

        $sumTargets = GiftLog::where('agency_id', $agencyId)
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->sum('giftPrice');

        $prefix = dashboardName();
        return $content
            ->title(__('agency profile'))
            ->view('bd_agency_profile', compact(
                'agency',
                'prefix',
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
                'tab',
                'sumTargets',
                'imageUrl'
            ));
    }

    public static function shippingProfile(ShippingAgency $agency, req $request, Content $content)
    {
        $tab = $request->input('tab', 'charges');

        $agencyId = $agency->id;
        $charges = null;
        $resiveds = null;

        switch ($tab) {
            case 'charges':
                $charges = Charge::where('charger_type', 'agency')
                    ->where('charger_id', $agencyId);

                $relations = [];

                if ($request->has('filter_by') && $request->filter_by !== null && $request->filter_by !== '') {
                    $charges->where('user_type', $request->filter_by);
                    $relations[] = $request->filter_by === 'user' ? 'receiverUser' : 'receiverAgency';
                }
                if ($request->has('filter_id') && $request->filter_id !== null && $request->filter_id !== '') {
                    $charges->where('user_id', $request->filter_id);
                }

                if (!empty($relations)) {
                    $charges->with($relations);
                }

                $charges = $charges->latest()->paginate(10, ['*'], 'charges_page');
                break;

            case 'resived':
                $resiveds = Charge::where('user_id', $agencyId)
                    ->where('user_type', 'agency');

                if ($request->has('sender_type') && $request->sender_type !== null && $request->sender_type !== '') {
                    $resiveds->where('charger_type', $request->sender_type);
                }
                if ($request->has('sender_id') && $request->sender_id !== null && $request->sender_id !== '') {
                    $resiveds->where('charger_id', $request->sender_id);
                }

                $resiveds = $resiveds->with(['sender'])
                    ->latest()
                    ->paginate(10, ['*'], 'resived_page');
                break;
        }

        $totalReceive = Charge::where('user_id', $agencyId)->where('user_type', 'agency')->sum('amount');
        $totalSend = Charge::where('charger_type', 'agency')->where('charger_id', $agencyId)->sum('amount');

        return $content->title(__('agency profile'))
            ->view('bd_shipping_agency_profile', compact(
                'agency',
                'resiveds',
                'charges',
                'tab',
                'totalReceive',
                'totalSend'
            ));
    }

    public function giftLogByAgency($rel, $month, $year, $agencyId, $keywords)
    {
        return GiftLog::query()
            ->whereHas($rel)
            ->with($rel)
            ->where('agency_id', $agencyId)
            ->whereBetween('created_at', [
                Carbon::create($year, $month, 1)->startOfMonth(),
                Carbon::create($year, $month, 1)->endOfMonth(),
            ])
            ->selectRaw("SUM(giftPrice) as exp, $keywords")
            ->groupBy($keywords)
            ->havingRaw("exp > 0")
            ->orderByRaw("exp DESC")
            ->get();
    }

    public function rateAgency($agencyId, $month, $year)
    {
        $agencyTarget = UserSallary::where('user_agency_id', $agencyId)
            ->whereBetween('created_at', [
                Carbon::create($year, $month, 1)->startOfMonth(),
                Carbon::create($year, $month, 1)->endOfMonth(),
            ])
            ->sum('agency_sallary');
        $minValue = Target::where('usd', '<', $agencyTarget)->orderBy('usd', 'desc')->first();
        $rate = (@$minValue->agency_share / 100) * @$agencyTarget;
        return [$agencyTarget, $rate];
    }

    protected function grid()
    {
        $grid = new Grid(new Agency);
        $countries = Common::areaCountries();

        $cacheKey = "agencies_grid_" . md5(json_encode(request()->all()));
        $grid->model()
            ->select(['id', 'name', 'app_owner_id', 'phone_code', 'phone', 'coins', 'img', 'is_frozen','created_by'])
            ->with(['owner:id,name,uuid', 'owner.packs', 'owner.profile', 'agencySalaries','creator'])
            ->where(function ($query) {
                $query
                    ->whereDoesntHave('additionalInfo')
                    ->orWhereHas('additionalInfo', fn($query) => $query->where('status', 1));
            })
            ->with(['owner' => function ($query) {
                $query->select('id', 'name', 'uuid');
            }])
            ->whereIn('country_id', $countries)
            ->orderByDesc('id');

        if (request("active") == true) {
            $grid->model()->whereHas("agencySalaries", function ($q) {
                $q->where('month', now()->month)
                    ->where('year', now()->year);
            });
        }

        if (request()->created == 'today') {
            $grid->model()->whereDate('created_at', today());
        }

        if (request()->created == 'month') {
            $grid->model()->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ]);
        }

        if (request()->pending == 1) {
            $grid->model()->whereHas('joinRequests', fn($q) => $q->where('status', 1));
        }

        $grid->column('name', __('Agency'))
            ->display(function ($name) {
                $cacheKey = "agency_image_{$this->id}";
                $image = Cache::remember($cacheKey, 3600, function () {
                    $path = @$this->img;
                    $defaultImage = asset("images/icon-agency.jpg");
                    $url = getImagePath($path) ?? $defaultImage;

                    if (!isImageExists($url)) {
                        $url = $defaultImage;
                    }

                    return handleShowImageWithTypes($this->id, $url, 40, 40);
                });

                $profileUrl = url("areaManager/profile-agency/{$this->id}");
                //  route('areaManager.agency.profile', ['id' => $this->id]);

                return "
                    <a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            {$image}
                            <div style='display: flex; flex-direction: column;'>
                                <span style='text-decoration: underline; cursor: pointer;'>{$name}</span>
                                <span style='font-size: smaller;'>ID: {$this->id}</span>
                            </div>
                        </div>
                    </a>
                ";
            });

        $grid->column('owner.name', trans('owner'))->display(function ($name) {
            $uid = @$this->owner->uuid;
            $path = @$this->owner->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = $this->owner ? areaManager_url("users/profile/{$this->owner->id}") : 0;
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                       <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                         <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='font-size: smaller;'>UUID: $uid</span>
                    </div>
                </div>
            ";
        });

        $grid->column('phone', trans('phone'))->display(function ($number) {
            if (!$number) return '-';

            $iconUrl = asset('images/phone.jpg');
            $phoneCode = $this->phone_code;
            $locale = app()->getLocale();

            $direction = ($locale === 'ar') ? 'row-reverse' : 'row';
            $margin = ($locale === 'ar') ? 'margin-left:5px;' : 'margin-right:5px;';

            return "
                <div style='display: ; align-items: center; flex-direction: {$direction};'>
                    <img src='{$iconUrl}' alt='flag' width='20' height='20' style='{$margin} filter: invert(1);'>
                    <span style='direction:ltr; unicode-bidi:bidi-override;'>{$phoneCode}{$number}</span>
                </div>
            ";
        });

        $grid->column('salary', __('Agency wallet'))->display(function ($coin) {
            $coin = truncateAndTrim($this->salary ?? 0);
            $icon = asset('images/dollar.jpg'); // تأكد من وجود الصورة في هذا المسار
            return "<div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . $coin . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>
                </div>";
        });
        $grid->column('created_by', __('Creator'))->display(function ($creatorId) {
            return app(\App\Admin\Services\CreatorService::class)->show($creatorId);
        });
        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            $model = $actions->row;
            $actions->disableView();
            $actions->disableDelete();
            $actions->add(new DeleteAgencyAction());
            $actions->add(new ChangeUsersAgencyAction($model->id));
        });

        $grid->disableExport();
        $grid->disableRowSelector();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->disableIdFilter();
            $filter->equal('id', __('agency id'));

            $filter->where(function ($query) {
                $query->whereHas('owner', function ($subQuery) {
                    $subQuery->where('uuid', 'like', "%{$this->input}%");
                });
            }, __('UUID'))->placeholder(__('search for host by UUID'));
        });

        Admin::style("
            .box-footer {
                flex-direction: row-reverse;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
            }

            .pagination-info {
                margin: 5px 0;
                white-space: nowrap;
                text-align: right;
                width: auto;
                order: 2;
            }

            .box-footer .pull-right {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 5px;
                margin: 5px 0;
                order: 1;
            }

            .box-footer .pull-right .dropdown {
                margin-left: 5px;
            }

            .pagination > li > a,
            .pagination > li > span {
                min-width: 35px;
                height: 35px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 5px;
            }

            .pagination {
                margin: 0;
                padding: 0;
                display: flex;
            }

            @media (max-width: 576px) {
                .box-footer {
                    flex-direction: column;
                    align-items: center;
                }

                .pagination-info,
                .box-footer .pull-right {
                    width: 100%;
                    display: flex;
                    justify-content: center;
                    text-align: center;
                }

                .pagination-info {
                    order: 1;
                    margin-bottom: 10px;
                }

                .box-footer .pull-right {
                    order: 2;
                }
            }
        ");

        $this->extendGrid($grid);

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Agency());

        $form->display('ID');

        $this->addMainFields($form);
        $this->addPhoneFields($form);
        $this->addSavingLogic($form);
        $this->addSavedLogic($form);
        $this->addFooter($form);

        return $form;
    }

    protected function addMainFields(Form $form)
    {

        if ($form->isEditing()) {
            $form->tools(function (Form\Tools $tools) {
                $tools->disableDelete();
            });
        }

        $form->row(function ($row) {
            $row->width(12)->select('bd_id', __('bd id'))->options($this->bdOptions())->ajax('/api/search/users-bd-by-countries?area_manager_id=' . auth()->id(), 'id', 'name');
            $row->width(12)->select('app_owner_id', __('app owner id'))->options($this->ownerOptions())->ajax('/api/search/users-by-countries?area_manager_id=' . auth()->id(), 'id', 'name')->rules('required');
            $row->width(12)->hidden('agency_manger_id', __('app manger id'));
            $row->width(12)->text('name', __('agency name'))->rules('required');
            $row->width(12)->hidden('status', __('status'))->default(1);
            //  $row->width(12)->hidden('country_id')->default(Auth::user()->country_id);
            $row->width(12)->hidden('admin_id')->default(Auth::user()->id);
        });
    }

    protected function bdOptions($editing = false)
    {
        return function ($value) use ($editing) {
            $ops = [];
            foreach (Bd::where('id', $value)->get() as $user) {
                $ops[$user->id] = $user->uuid ?? $user->id . '_' . $user->username;
            }
            return $ops;
        };
    }

    protected function ownerOptions()
    {
        return function ($value) {
            $ops = [];
            foreach (User::where('id', $value)->get() as $user) {
                $ops[$user->id] = $user->uuid . '_' . $user->name;
            }
            return $ops;
        };
    }

    protected function addPhoneFields(Form $form)
    {
        $form->row(function ($row) {
            $row->width(9)->text('phone', __('agency whatsApp number'))
                ->attribute('id', 'phone-input')
                ->attribute('maxlength', 13)
                ->default(function ($form) {
                    if ($form->model()->phone && $form->model()->phone_code) {
                        return $form->model()->phone;
                    }
                    return null;
                });

            $row->hidden('phone_code')->default(function ($form) {
                return $form->model()->phone_code ?? '';
            });
        });

        if (Session::has('show_alert')) {
            $form->html('<script>alert("الرجاء اختيار نوع الوكالة اولا");</script>');
        }

        Admin::script($this->phoneJs());
    }


    protected function phoneJs()
    {
        return <<<JS
            function initPhoneInputById(inputId, hiddenId) {
                const input = document.querySelector(inputId);
                const hidden = document.querySelector(hiddenId);
                if (!input || input.classList.contains('iti-initialized')) return;

                const iti = window.intlTelInput(input, {separateDialCode: true, preferredCountries: ["eg"], utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"});
                input.classList.add('iti-initialized');

                if (input.value && hidden && hidden.value) iti.setNumber(hidden.value + input.value);

                input.addEventListener("countrychange", function () { if(hidden) hidden.value = "+" + iti.getSelectedCountryData().dialCode; });
                const form = input.closest('form');
                if(form && !form.classList.contains('phone-init')){
                    form.addEventListener('submit', function(){
                        // if(hidden) hidden.value = "+" + iti.getSelectedCountryData().dialCode;
                        // input.value = iti.getNumber(intlTelInputUtils.numberFormat.NATIONAL);
                       hidden.value = "+" + iti.getSelectedCountryData().dialCode;

                    });
                    form.classList.add('phone-init');
        }
    }

    function initAllPhones() { initPhoneInputById("#phone-input", "input[name='phone_code']"); }
    initAllPhones();
    $(document).on('pjax:complete', function () { setTimeout(initAllPhones, 100); });
    JS;
    }
    protected function addSavingLogic(Form $form)
    {
        $form->saving(function (Form $form) {
            if (!$form->bd_id && !$form->model()->bd_id) {

                $defaultBd = Bd::where('default', 1)->where('country_id', 0)->first();

                if ($defaultBd) {
                    $form->bd_id = $defaultBd->id;
                } else {
                    throw new \Exception(__('dashboard.no_default_bd_to_transfer_agencies'));
                }
            }
            $bd = Bd::select(['id', 'country_id'])->find($form->bd_id);
            $form->model()->country_id = $bd->country_id;

            $appOwnerId = $form->input('app_owner_id');
            $originalOwnerId = $form->model()->getOriginal('app_owner_id');
            $newOwnerId = request()->app_owner_id;
            $form->model()->type = 1;

            if ($form->model()->exists && $newOwnerId !== null && $newOwnerId != $originalOwnerId) {
                $user = User::find($originalOwnerId);
                $agencyId = $form->model()->id;
                Common::userJoinAgency($originalOwnerId, $newOwnerId, $agencyId);

                $user->update([
                    'type_user' => 0,
                    'agency_id' => 0,
                    'is_host' => 0,
                ]);
                uploadMonthlyDiamondReceive($originalOwnerId, 0);
            }

            User::where('id', intval($appOwnerId))->update([
                'type_user' => 2,
                'is_host' => 1,
                'agency_id' => $form->model()->id,
            ]);
        });
    }

    protected function addSavedLogic(Form $form)
    {
        $form->saved(function (Form $form) {
            $appOwnerId = intval($form->model()->app_owner_id);

            User::where('id', $appOwnerId)->update([
                'type_user' => 2,
                'is_host' => 1,
                'agency_id' => $form->model()->id,
            ]);


            $exists = UsersJoinedAgency::where([
                'user_id' => $appOwnerId,
                'agency_id' => $form->model()->id,
                'type' => 1,
            ])->whereNull('leave_date')->exists();

            if (!$exists) {
                UsersJoinedAgency::create([
                    'user_id' => $appOwnerId,
                    'agency_id' => $form->model()->id,
                    'type' => 1,
                    'join_date' => now(),
                    'status' => 'Joined',
                ]);
            }
        });
    }

    protected function addFooter(Form $form)
    {
        $form->footer(function ($footer) {
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
    }
    public function response()
    {
        if (is_null($this->response)) {
            $this->response = new Response();
        }

        if (method_exists($this, 'dialog')) {
            $this->response->swal();
        } else {
            $this->response->toastr();
        }

        return $this->response;
    }
}
