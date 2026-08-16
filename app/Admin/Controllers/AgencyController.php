<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\ChangeUsersAgencyAction;
use App\Admin\Actions\DeleteAgencyAction;
use App\Admin\Services\UserService;
use App\Facades\CustomNotification;
use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\AgencySallary;
use App\Models\AgencyUserJob;
use App\Models\Bd;
use App\Models\GiftLog;
use App\Models\ShippingAgency;
use App\Models\Target;
use App\Models\User;
use App\Models\UserSallary;
use App\Models\UsersJoinedAgency;
use App\Models\UserTarget;
use App\Observers\AgencyObserver;
use Carbon\Carbon;
use Encore\Admin\Actions\Response;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Table;
use Illuminate\Http\Request as req;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Modules\Milestones\Helpers\MilestoneHelper;


class AgencyController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'agencies';
    public $hiddenColumns = [];

    //    public function __construct()
    //    {
    //        (new AppFeatureService)->validateStatusEnable("agencies");
    //    }

    public function index(Content $content)
    {
        return parent::index($content
            ->title(__('Agencies'))
            ->description(__('List of Agencies'))
            ->row(function ($row) {
                $row->column(12, $this->grid());
            }));
    }

    public function edit($id, Content $content)
    {
        return parent::edit($id, $content
            ->title(__($this->title))
            ->body($this->form()->edit($id)));
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
        $timezone = getTimezone();
        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;
        $uuid = request('uuid');
        $tab = request('tab', 'members');
        $giftType = request()->get('gift_type', 'receiver');
        $start = request('start_at');
        $end = request('end_at');
        $uuid = $request->uuid;

        $agency = Cache::remember("agency_{$id}", 600, function () use ($id) {
            return Agency::query()
                ->with(['admins', 'bd', 'owner:id,name,uuid', 'owner.profile'])
                ->select('id', 'name', 'app_owner_id', 'phone', 'created_at', 'created_by', 'coins', 'bd_id', 'img')
                ->find($id);
        });

        if (!$agency) {
            $agency = Cache::remember("agency_{$id}", 600, function () use ($id) {
                return ShippingAgency::query()
                    ->with(['admins', 'owner:id,name,uuid', 'owner.profile'])
                    ->select('id', 'name', 'app_owner_id', 'created_by', 'phone', 'coins', 'img')
                    ->find($id);
            });
        }

        if (!$agency) {
            abort(404, 'Agency not found.');
        }

        $adminUser = DB::table('admin_users')->where('id', $agency->created_by)->first() ?? $agency->owner;
        $pathAdmin = $adminUser?->avatar;
        $defaultImageAdmin = asset('images/businessman-icon.jpg');
        $imageUrlAdmin = getImagePath($pathAdmin);
        if (!isImageExists($imageUrlAdmin)) {
            $imageUrlAdmin = $defaultImageAdmin;
        }

        $path = $agency?->img;
        $defaultImage = asset("images/icon-agency.jpg");
        $imageUrl = getImagePath($path);
        if (!isImageExists($imageUrl)) {
            $imageUrl = $defaultImage;
        }



        $agencyId = $agency->id ?? $id;

        $members = $charges = $salaries = $agencyJoinRequests = $giftLog = $memberTargets = $agencyTarget = $rate = $stars = $heroes = null;

        // Load ALL tab data at once for client-side tab switching (no page reload)
        $members = $agency->mempers()->when(isset($uuid), function ($query) use ($uuid) {
            $query->where('uuid', $uuid);
        })
            ->select('id', 'name', 'uuid', 'total_days', 'agency_id', 'country_id')
            ->with('country', 'agencyUserJob')
            ->withSum(['monthlyDiamondReceive as monthly_diamond_received' => function ($query) {
                $query->where('month', now()->month)
                    ->where('year', now()->year);
            }], 'monthly_diamond_received')
            ->paginate(10, ['*'], 'members_page');

        $charges = $agency->senderCharges()
            ->with(Common::chargerRelationsQuery())
            ->latest()
            ->paginate(10, ['*'], 'charges_page');

        $salaries = AgencySallary::query()
            ->where('agency_id', $id)
            ->select('id', 'sallary', 'cut_amount', 'month', 'year', 'created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'salary_page');

        $agencyJoinRequests = AgencyJoinRequest::query()
            ->where(['agency_id' => $id, 'status' => 0])
            ->with('user')
            ->whereHas('user')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'join_page');

        $memberTargets = $agency
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

        $giftLog =
            // Cache::remember("agency_{$id}_giftlog", 600, function () use ($id) {
            //     return
            GiftLog::where('agency_id', $id)
            ->selectRaw("SUM(giftPrice) as exp, receiver_id")
            ->with('receiver')
            ->groupBy('receiver_id')
            ->whereHas('receiver')
            ->orderByDesc('exp')
            ->get();
        // });


        $sumTargets = GiftLog::where('agency_id', $agencyId)
            ->whereBetween('created_at', [
                Carbon::now($timezone)->startOfMonth()->copy()->setTimezone('UTC'),
                Carbon::now($timezone)->endOfMonth()->copy()->setTimezone('UTC'),
            ])
            ->sum('giftPrice');

        // Cache::remember("agency_{$id}_targets_sum_{$month}_{$year}", 600, function () use ($agencyId, $month, $year) {
        //     return
        //  UserSallary::where('user_agency_id', $agencyId)
        //     ->where('month', now()->month)
        //     ->where('year', now()->year)
        //     ->sum('target_diamonds');

        // });

        $giftSLogs = GiftLog::where('agency_id', $id)->with('receiver', 'sender', 'gift', 'room')->when(isset($start) && isset($end), function ($query) use ($start, $end) {
            $query->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        })->when(isset($uuid), function ($query) use ($uuid) {
            $query->where(function ($q) use ($uuid) {
                $q->whereHas('sender', fn($q) => $q->where('uuid', $uuid))
                    ->orWhereHas('receiver', fn($q) => $q->where('uuid', $uuid));
            });
        })->orderByDesc('id')->paginate(10, ['*'], 'gift_page');
        $diamonds = GiftLog::where('agency_id', $id)->when(isset($start) && isset($end), function ($query) use ($start, $end) {
            $query->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        })->when(isset($uuid), function ($query) use ($uuid) {
            $query->where(function ($q) use ($uuid) {
                $q->whereHas('sender', fn($q) => $q->where('uuid', $uuid))
                    ->orWhereHas('receiver', fn($q) => $q->where('uuid', $uuid));
            });
        })->selectRaw('SUM(giftPrice) AS total')->value('total');
        $diamondsHosts = UserSallary::where('user_agency_id', $id)->sum('achieved_diamond');
        $prefix = dashboardName();
        return $content
            ->title(__('agency profile'))
            ->view('agency_profile', compact(
                'adminUser',
                'imageUrlAdmin',
                'agency',
                'prefix',
                'members',
                'charges',
                'salaries',
                'diamondsHosts',
                'agencyJoinRequests',
                'giftLog',
                'giftSLogs',
                'diamonds',
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

    public function update($id)
    {
        // Ownership changes are handled inside the form's saving()/saved()
        // callbacks (old-owner full detach + new-owner assignment + join
        // records). Run the whole save in ONE transaction and lock the agency
        // row so a concurrent edit cannot interleave a second transfer on the
        // same agency. Earnings are never moved here: gift_logs carry their own
        // agency_id, so accumulated earnings stay with the agency, not the person.
        // This controller owns the full transfer (detach + assign + join rows +
        // milestone), so suppress AgencyObserver::updated()'s duplicate transfer
        // branch for this request only; always reset it in finally.
        AgencyObserver::$skipOwnershipTransfer = true;
        try {
            return DB::transaction(function () use ($id) {
                Agency::where('id', $id)->lockForUpdate()->first();

                return parent::update($id);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // A concurrent transfer that lost the race on the UNIQUE index
            // (agencies.app_owner_id) surfaces as MySQL error 1062. The
            // exception propagates out of the transaction closure, so all
            // writes in this attempt (new-owner assignment, old-owner detach)
            // are rolled back before we turn it into a readable form error.
            return $this->handleDuplicateOwner($e);
        } finally {
            AgencyObserver::$skipOwnershipTransfer = false;
        }
    }

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        // Creating an agency also assigns app_owner_id, so the same UNIQUE
        // constraint can fire on INSERT. Wrap the create in a transaction so
        // the new-owner detach in saving() and the agency insert are atomic,
        // and convert a duplicate-owner 1062 into a form error.
        // Note: on create the observer's created() event grants the milestone
        // and is NOT suppressed; the flag only guards updated()'s transfer
        // branch, which never fires on insert.
        AgencyObserver::$skipOwnershipTransfer = true;
        try {
            return DB::transaction(function () {
                return parent::store();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->handleDuplicateOwner($e);
        } finally {
            AgencyObserver::$skipOwnershipTransfer = false;
        }
    }

    protected function handleDuplicateOwner(\Illuminate\Database\QueryException $e)
    {
        // Only the app_owner_id UNIQUE index maps to this friendly message.
        // Other 1062s in the same transaction (e.g. uniq_user_rewards from the
        // milestone grant fired by AgencyObserver) must NOT be masked as an
        // ownership conflict, so match the index name explicitly and rethrow
        // anything else.
        if (($e->errorInfo[1] ?? null) === 1062 && str_contains($e->getMessage(), 'uq_agencies_app_owner_id')) {
            $error = new \Illuminate\Support\MessageBag([
                'app_owner_id' => [__('This user is already an owner of another agency.')],
            ]);

            return back()->withInput()->withErrors($error);
        }

        throw $e;
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */


    protected function grid()
    {
        $countryID = Common::filterCountryIds();

        $grid = new Grid(new Agency);
        $grid->model()
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->selectRaw('agencies.*, COALESCE(SUM(agency_salaries.sallary - agency_salaries.cut_amount), 0) as salary')
            ->select(['agencies.id', 'agencies.name', 'agencies.app_owner_id', 'agencies.phone_code', 'agencies.phone', 'agencies.coins', 'agencies.country_id', 'agencies.img', 'agencies.is_frozen', 'agencies.created_by'])
            ->with([
                'owner:id,name,uuid,country_id,sender_level,received_level,special_id',
                'owner.country',
                'owner.packs',
                'owner.profile',
                'owner.senderLevel',
                'owner.receiverLevel',
                'agencySalaries',
                'creator',
                'country'
            ])
            ->where(function ($query) {
                $query
                    ->whereDoesntHave('additionalInfo')
                    ->orWhereHas('additionalInfo', fn($query) => $query->where('status', 1));
            })
            ->orderByDesc('agencies.id');

        if (request("active") == true) {
            $grid->model()->whereHas("agencySalaries", function ($q) {
                $q->where('month', now()->month)
                    ->where('year', now()->year);
            });
        }

        if (request()->created == 'today') {
            $grid->model()->whereDate('agencies.created_at', today());
        }

        if (request()->created == 'month') {
            $grid->model()->whereMonth('agencies.created_at', now()->month)
                ->whereYear('agencies.created_at', now()->year);
        }

        if (request()->pending == 1) {
            $grid->model()->whereHas('joinRequests', fn($q) => $q->where('status', 1));
        }

        // --- Agency name column ---
        $grid->column('name', __('Agency'))->display(function ($name) {
            $path = $this->img;
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
            $profileUrl = route('admin.agency.profile', ['id' => $this->id]);

            return "<a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        {$image}
                        <div style='display: flex; flex-direction: column;'>
                            <span style='text-decoration: underline; cursor: pointer;'>{$name}</span>{$flagHtml}<br>
                            <span style='font-size: smaller;'>ID: {$this->id}</span>
                        </div>
                    </div>
                </a>";
        })->sortable();


        $grid->column('owner.name', __('owner'))->display(function () {
            return app(UserService::class)->adminUserCard($this->owner);
        })->sortable(['users.name' => 'asc']);;
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        // --- Phone column ---
        $grid->column('phone', trans('phone'))->display(function ($number) {
            if (!$number) return '-';

            $iconUrl = asset('images/phone.jpg');
            $phoneCode = $this->phone_code;
            $locale = app()->getLocale();

            $direction = ($locale === 'ar') ? 'row-reverse' : 'row';
            $margin = ($locale === 'ar') ? 'margin-left:5px;' : 'margin-right:5px;';

            return "
            <div style='display: flex; align-items: center; flex-direction: {$direction};'>
                <img src='{$iconUrl}' alt='flag' width='20' height='20' style='{$margin} filter: invert(1);'>
                <span style='direction:ltr; unicode-bidi:bidi-override;'>{$phoneCode}{$number}</span>
            </div>
        ";
        })->sortable();

        // --- Salary column (using withSum preload) ---
        $grid->column('salary', __('Agency wallet'))->display(function () {
            $coin = truncateAndTrim($this->current_salary ?? 0);
            $icon = asset('images/dollar.jpg');
            return "<div style='display: flex; align-items: center; gap: 5px;'>
                <span>{$coin}</span>
                <img src='{$icon}' alt='Coin' width='20' height='20'>
            </div>";
        });

        // --- Frozen column ---
        $grid->column('is_frozen', __("frozen"))
            ->display(fn() => $this->is_frozen ? 1 : 0)
            ->switch(Common::getSwitchStates())->sortable();

        $grid->column('created_by', __('Creator'))->display(function ($creatorId) {
            $creator = $this->creator;
            return app(\App\Admin\Services\CreatorService::class)->showV2($creator);
        });
        // --- Actions ---
        $permission = $this->permission_name;
        $grid->actions(function ($actions) use ($permission) {
            $model = $actions->row;
            $actions->disableDelete();

            if (Admin::user()->can('delete-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new DeleteAgencyAction());
            }

            if (Admin::user()->can('change-users-agency-switch-' . $permission) || Admin::user()->can('*')) {
                $actions->add(new ChangeUsersAgencyAction($model->id));
            }
        });

        // --- Misc ---
        $grid->disableExport();
        $grid->disableRowSelector();
        $this->extendGrid($grid);

        // --- Filters ---
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->equal('id', __('ID'));

            $filter->where(function ($query) {
                $query->whereHas('owner', function ($subQuery) {
                    $subQuery->where('uuid', 'like', "%{$this->input}%");
                });
            }, __('UUID'))->placeholder(__('search for host by UUID'));
        });

        // --- Styles ---
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

        return $grid;
    }


    protected function balance_details($id)
    {
        $grid = new Grid(new Agency);

        $grid->model()->where('id', $id)->orderByDesc('id');
        $grid->id('ID');
        $grid->column('name', trans('name'));
        $grid->column('salary', trans('salary'));
        $grid->column('img', trans('img'))->image('', 30);
        $grid->disableActions();
        $grid->disableCreateButton();
        $this->extendGrid($grid);

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
        $show = new Show(Agency::findOrFail($id));

        $show->id('ID');
        $show->field('owner_id', __('owner_id'));
        $show->field('name', __('name'));
        $show->field('notice', __('notice'));
        $show->field('status', __('status'));
        $show->field('phone', __('phone'));
        $show->field('url', __('url'));
        $show->field('img', __('image'))->image('', 200);
        $show->field('contents', __('contents'));
        $this->extendShow($show);
        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Agency());

        $this->disableFormTools($form);

        $this->addHiddenFields($form);
        $this->addMainFields($form);
        $this->addPhoneFields($form);
        $this->addSavingLogic($form);
        $this->addSavedLogic($form);
        $this->addFooter($form);

        return $form;
    }

    protected function addHiddenFields(Form $form)
    {
        $form->hidden('type', __('type'))->default(1);
        $form->hidden('is_frozen', __('is_frozen'))->default(0);
    }

    protected function addMainFields(Form $form)
    {
        $form->display('ID');

        if (!$form->isEditing()) {
            $form->row(function ($row) {
                $row->width(12)->select('bd_id', __('bd id'))->options($this->bdOptions())->ajax('/api/search/users-bd2', 'id', 'name')->rules('required');
                $row->width(12)->select('app_owner_id', __('app owner id'))->options($this->ownerOptions())->ajax('/api/search/users3', 'id', 'name')->rules('required');
                $row->width(12)->hidden('agency_manger_id', __('app manger id'));
                $row->width(12)->text('name', __('agency name'))->rules('required');
                $row->width(12)->switch('status', __('status'));
            });
        } else {
            $form->row(function ($row) {
                $row->width(12)->select('bd_id', __('bd id'))->options($this->bdOptions(true))->ajax('/api/search/users-bd2', 'id', 'name');
                $row->width(12)->select('app_owner_id', __('app owner id'))->options($this->ownerOptions(true))->ajax('/api/search/users3', 'id', 'name')->rules('required');
                $row->width(12)->text('name', __('agency name'))->rules('required');
                $row->width(12)->switch('status', __('status'));
            });
        }
    }

    protected function bdOptions($editing = false)
    {
        return function ($value) {
            if (!$value) return [];
            $user = Bd::find($value);
            return $user ? [$user->id => $user->uuid ?? $user->id . '_' . $user->username] : [];
        };
    }

    protected function ownerOptions($editing = false)
    {
        return function ($value) {
            if (!$value) return [];
            $user = User::find($value);
            return $user ? [$user->id => $user->uuid ?? $user->id . '_' . $user->name] : [];
        };
    }

    protected function addPhoneFields(Form $form)
    {
        $form->row(function ($row) {
            $row->width(9)->text('phone', __('agency whatsApp number'))
                ->rules('required')
                ->attribute('id', 'phone-input')
                ->attribute('maxlength', 12)
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

                // Wait for intlTelInput to be available
                if (typeof window.intlTelInput !== 'function') {
                    setTimeout(function() { initPhoneInputById(inputId, hiddenId); }, 150);
                    return;
                }

                const iti = window.intlTelInput(input, {
                    separateDialCode: true,
                    preferredCountries: ["eg"],
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
                });
                input.classList.add('iti-initialized');

                if (input.value && hidden && hidden.value) iti.setNumber(hidden.value + input.value);

                input.addEventListener("countrychange", function () {
                    if (hidden) hidden.value = "+" + iti.getSelectedCountryData().dialCode;
                });

                const form = input.closest('form');
                if (form && !form.classList.contains('phone-init')) {
                    form.addEventListener('submit', function() {
                        if (hidden) hidden.value = "+" + iti.getSelectedCountryData().dialCode;
                    });
                    form.classList.add('phone-init');
                }
            }

            function initAllPhones() {
                initPhoneInputById("#phone-input", "input[name='phone_code']");
            }

            // Run after select2 and other scripts have initialized
            setTimeout(initAllPhones, 200);
    JS;
    }

    protected function addSavingLogic(Form $form)
    {

        $form->saving(function (Form $form) {
            $originalOwnerId = $form->model()->getOriginal('app_owner_id');
            $isEditing = $form->isEditing();
            $appOwnerId = $form->input('app_owner_id');

            $currentAgencyId = $form->model()->id ?? null;

            if ($appOwnerId) {
                $existingAgency = Agency::where('app_owner_id', $appOwnerId)
                    ->when($currentAgencyId, function ($query) use ($currentAgencyId) {
                        $query->where('id', '!=', $currentAgencyId);
                    })
                    ->first();

                if ($existingAgency) {
                    $error = new \Illuminate\Support\MessageBag([
                        'app_owner_id' => [__('This user is already an owner of agency: ') . $existingAgency->name],
                    ]);
                    return back()->withInput()->withErrors($error);
                }
            }

            if (!$form->bd_id && !$form->model()->bd_id) {
                $defaultBd = Bd::where('country_id', Auth::user()->country_id)->where('default', 1)->first();

                if ($defaultBd) {
                    $form->bd_id = $defaultBd->id;
                } else {
                    throw new \Exception(__('dashboard.no_default_bd_to_transfer_agencies'));
                }
            }
            $bd = Bd::select(['id', 'country_id'])->find($form->bd_id);
            if ($bd) {
                if ($bd->country_id == 0 || empty($bd->country_id)) {
                    $owner = User::select(['id', 'country_id'])->find($form->input('app_owner_id'));
                    $form->country_id = $owner?->country_id ?: null;
                } else {
                    $form->country_id = $bd->country_id;
                }
            }

            $newOwnerId = request()->app_owner_id;
            $form->model()->type = 1;

            if ($form->model()->exists && $newOwnerId !== null && $newOwnerId != $originalOwnerId) {

                $oldOwner = User::find($originalOwnerId);
                $agencyId = $form->model()->id;

                if ($oldOwner) {
                    // Fully detach the previous owner: reset agency membership +
                    // host flags and close their salary/log records.
                    UserHandling::kickUserFromAgency($oldOwner, 0, $agencyId);

                    // kickUserFromAgency only closes type=2 (member) join rows;
                    // the owner's own row is type=1, so close it explicitly to
                    // avoid a dangling open ownership record after the transfer.
                    UsersJoinedAgency::where('user_id', $oldOwner->id)
                        ->where('agency_id', $agencyId)
                        ->where('type', 1)
                        ->whereNull('leave_date')
                        ->update([
                            'leave_date' => now(),
                            'status' => 'owner changed by admin',
                            'kicked_by_admin' => Auth::id(),
                        ]);

                    // Formerly done by AgencyObserver::updated() (now suppressed
                    // for admin transfers): drop the outgoing owner's
                    // host-agency-owner milestone.
                    MilestoneHelper::removeReward($oldOwner, 'host-agency-owner');
                }

                // Grant the milestone to the incoming owner, mirroring the
                // observer branch this controller now supersedes. Idempotent:
                // grantMilestoneToUser no-ops if the reward already exists.
                if ($newOwnerId) {
                    MilestoneHelper::grantMilestoneToUser(intval($newOwnerId), 'host-agency-owner');
                }
            }

            // The incoming owner may already belong to a different agency (as
            // that agency's owner or as a member). Detach that previous
            // membership fully before we point users.agency_id here, otherwise
            // we leave an orphan open UsersJoinedAgency row in the old agency
            // and silently overwrite their agency_id. Same detach pattern used
            // for the outgoing owner above; runs inside the update()/store()
            // transaction so it commits atomically with the transfer.
            if ($appOwnerId) {
                $newOwner = User::find(intval($appOwnerId));
                $targetAgencyId = $form->model()->id;

                if ($newOwner && $newOwner->agency_id && $newOwner->agency_id != $targetAgencyId) {
                    $previousAgencyId = $newOwner->agency_id;

                    UserHandling::kickUserFromAgency($newOwner, 0, $previousAgencyId);

                    UsersJoinedAgency::where('user_id', $newOwner->id)
                        ->where('agency_id', $previousAgencyId)
                        ->whereIn('type', [1, 2])
                        ->whereNull('leave_date')
                        ->update([
                            'leave_date' => now(),
                            'status' => 'owner changed by admin',
                            'kicked_by_admin' => Auth::id(),
                        ]);
                }
            }

            User::where('id', intval($appOwnerId))->update([
                'type_user' => 2,
                'is_host' => 1,
                'agency_id' => $form->model()->id,
            ]);


            if (!request('bd_id') && $isEditing) {
                $admin =  Bd::where('default', 1)->first();
                $form->bd_id = $admin->id;
            }
            $bd = Bd::find($form->bd_id);
            if ($bd) {
                if ($bd->country_id == 0 || empty($bd->country_id)) {
                    $owner = User::select(['id', 'country_id'])->find($form->input('app_owner_id') ?? $form->model()->app_owner_id);
                    $form->model()->country_id = $owner?->country_id ?: null;
                } else {
                    $form->model()->country_id = $bd->country_id;
                }
            }
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

            $user = User::find($appOwnerId);



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

            Cache::forget('agency_' . $form->model()->id);
        });
    }

    protected function addFooter(Form $form)
    {
        $form->footer(function ($footer) {
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });
    }

    public function usersGrid($id)
    {
        $grid = new Grid(new User());
        $grid->model()->where('agency_id', $id);
        $grid->quickSearch();
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('uuid', __('uuid'));
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('family_id', __('Family'))->select(Common::by_family_filter());
            });
        });
        $grid->column('id', __('Id'));

        $grid->column('uuid', __('uuid'));

        $grid->column('name', __('Name'));

        $grid->column('salary', __('salary'));

        $grid->column('coins', __('diamonds'));

        $grid->column('target', __('target'))->expand(function ($model) {

            $targets = $model->targets()->orderBy('created_at', 'desc')->get()->map(function ($target) {
                return $target->only([
                    'id',
                    'add_month',
                    'add_year',
                    'target_usd',
                    'target_hours',
                    'target_days',
                    'target_agency_share',
                    'user_diamonds',
                    'user_hours',
                    'user_days',
                    'user_obtain',
                    'agency_obtain',
                    'updated_at'
                ]);
            });

            return new Table(
                [
                    'ID',
                    __('month'),
                    __('year'),
                    __('usd') . ' ' . __('deserved'),
                    __('target hours'),
                    __('target days'),
                    __('agency share') . '(%)',
                    __('user diamonds'),
                    __('user hours'),
                    __('user days'),
                    __('user obtain'),
                    __('agency obtain'),
                    __('at time'),

                ],
                $targets->toArray()
            );
        });

        $grid->disableActions();
        $grid->disableCreateButton();

        return $grid;
    }

    public function targetGrid($id)
    {
        $grid = new Grid(new UserTarget);
        $grid->model()->where('agency_obtain', '>', 0);
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('add_month', __('month'));
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('add_year', __('year'));
            });
        });
        $grid->model()->where('agency_id', $id)
            ->selectRaw('agency_id,add_month as m,add_year as y,ROUND(SUM(agency_obtain), 4) as tot')
            ->groupByRaw('agency_id,m,y');
        $grid->column('agency_id', __('agency id'))->modal('agency info', function ($model) {
            return Common::getAgencyShow($model->agency_id);
        });
        $grid->column('m', __('month'));
        $grid->column('y', __('year'));
        $grid->column('tot', __('agency obtain'));
        $grid->disableActions();
        $grid->disableCreateButton();

        return $grid;
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




    public function acceptJoin($id)
    {
        if (! Admin::user()->can('*')) {
            Permission::check('change-users-agency-switch-' . $this->permission_name);
        }

        $agencyJoinRequest = AgencyJoinRequest::where('id', $id)->with('user', 'agency')->first();
        if (!$agencyJoinRequest) response()->json([
            'status' => false,
            'message' => __('Not found')
        ], 404);
        $user = $agencyJoinRequest->user;

        if (!$user) return response()->json([
            'status' => false,
            'message' => __(' user Not found')
        ], 404);
        $agency = $agencyJoinRequest->agency;
        if (!$agency) return response()->json([
            'status' => false,
            'message' => __(' agency Not found')
        ], 404);
        if ($user->agency_id) return response()->json([
            'status' => false,
            'message' => __('user joined agency before')
        ], 404);
        // dd($agency,$user->agency_id);
        $agencyJoinRequest->status = 1;
        $agencyJoinRequest->save();

        $user->agency_id = $agencyJoinRequest->agency_id;
        $user->type_user = 1;
        $user->save();
        $checkAgencyUser = UsersJoinedAgency::where('user_id', $user->id)->where('agency_id', $agency->id)->where('leave_date', null)->exists();
        if (!$checkAgencyUser) {
            $joinAgencyData = [
                'user_id' =>  $user->id,
                'agency_id' => $agency->id,
                'type' => 2,
                'join_date' => now(),
                'status' => 'Joined',
            ];
            UsersJoinedAgency::create($joinAgencyData);
        }
        // add vip to user
        // UserCommon::userVip($user,'acceptJoin');
        CustomNotification::acceptAgencyApp($agency, $user);

        return  response()->json([
            'status' => true,
            'message' => __('Joined successfully')
        ]);
    }

    public function adminAgency($id)
    {
        if (! Admin::user()->can('*')) {
            Permission::check('change-users-agency-switch-' . $this->permission_name);
        }
        $user = User::Find($id);
        $admin =  AgencyUserJob::where('user_id', $user->id)->where('agency_id', $user->agency_id)->where('type', 'requestManger')->first();
        if ($admin) {
            $admin->delete();
            Cache::forget('agency_' . $user->agency_id);
            return response()->json([
                'status' => true,
                'message' => __('Admin role removed from this agency')
            ]);
        }
        $data = [
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
            'type' => "requestManger",
        ];
        AgencyUserJob::create($data);
        Cache::forget('agency_' . $user->agency_id);
        return response()->json([
            'status' => true,
            'message' => __('done')
        ]);
    }

    public function kickFromAgency($id)
    {
        if (! Admin::user()->can('*')) {
            Permission::check('change-users-agency-switch-' . $this->permission_name);
        }
        $user = User::findOrFail($id);
        clearAgencyCache($user->agency_id);

        $isOwner = Agency::where('id', $user->agency_id)
            ->where('app_owner_id', $user->id)
            ->exists();

        if ($isOwner) {
            return response()->json([
                'status' => false,
                'message' => __('Cannot remove the owner of the agency'),
            ], 403);
        }
        UserHandling::kickUserFromAgency($user, 0);
        $user->agency_id = 0;
        $user->type_user = 0;
        $user->save();

        MilestoneHelper::removeReward($user, 'host');
        Cache::forget('agency_' . $user->agency_id);
        return response()->json([
            'status' => true,
            'message' => __('done')
        ]);
    }

    public function rejectJoin($id)
    {
        if (! Admin::user()->can('*')) {
            Permission::check('change-users-agency-switch-' . $this->permission_name);
        }
        $agencyJoinRequest = AgencyJoinRequest::where('id', $id)->with('user')->first();
        if (!$agencyJoinRequest) return response()->json([
            'status' => false,
            'message' => __('Not found')
        ], 404);

        $agencyJoinRequest->status = 2;
        $agencyJoinRequest->save();

        return response()->json([
            'status' => true,
            'message' => __('Rejected successfully')
        ]);
    }

    public function members($agencyId)
    {
        $grid = new Grid(new User());

        $grid->model()->where('agency_id', $agencyId)->where('type_user', 1)->whereDoesntHave('agencyUserJob')
            ->with(['profile', 'country']);
        $joinRequests = AgencyJoinRequest::where('agency_id', $agencyId)->get()->keyBy('user_id');
        $grid->column('name', __('User'))
            ->display(function ($name) {
                $uid = @$this->uuid;
                $path = @$this->profile?->avatar;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }
                $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                $image
                <div>
                    <strong>$name</strong><br>
                    <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                </div>
            </div>
        ";
            });

        $grid->column('whatsapp', __('whatsapp'))->display(function ($number) use ($joinRequests) {
            $joinRequest = $joinRequests->get($this->id);
            if (!$joinRequest) return '-';
            $number = $joinRequest->whatsapp;
            if (!$number) return '-';
            $iconUrl = asset('images/whatsapp.png'); // Adjust the path based on your actual file location

            // Return an image with a WhatsApp link
            return "<div style='display: flex; align-items: center; '>

            <span>{$number} </span>

              <img src='{$iconUrl}' alt='USD' width='20' height='20' style='margin-left:3px; filter: invert(1);'>
        </div>";
        });

        $grid->column('country.name', __('country'))->display(function ($name) {
            if (!$name) return '-';

            $name = app()->getLocale() == 'ar' ? $name ?? @$this->country?->e_name : @$this->country?->e_name ?? $name;
            $path =    @$this->user?->country?->flag ?? '';

            $url = getImagePath($path);

            // Check if the image exists

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);

            // Return an image with a WhatsApp link
            return "
            <div style='display: flex; flex-direction: column; align-items: start;'>
                <span>{$name}</span>
                <img src='{$image}' alt='USD' width='20' height='20' style='margin-top: 3px; filter: invert(1);'>
            </div>
        ";
        });

        $grid->column('return', __('action'))->display(function () {
            return (new \App\Admin\Actions\AgencyAdmin($this->id))->render();
        });

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableActions();
        return $grid;
    }


    public function usersAgency()
    {
        $users = User::where('agency_id', '!=', 0)->whereNotNull('agency_id')
            ->where('type_user', 0)
            ->get();

        return response()->json([
            'count_users'    => $users->count(),        // how many users
            'user_ids' => $users->pluck('id'),    // list of user IDs
        ]);
    }

    public function UpdateTypeUserAgency()
    {
        $query = User::whereIn('id', Agency::select('app_owner_id'))
            ->where('type_user', 0);

        $userIds = $query->pluck('id');
        $count   = $userIds->count();

        $query->update(['type_user' => 2]);

        $query = User::where('agency_id', '!=', 0)->whereNotNull('agency_id')->where('type_user', 0)
            ->whereNotIn('id', $userIds->toArray())->update(['type_user' => 1]);

        return response()->json([
            'count'    => $count,
            'user_ids' => $userIds,
        ]);
    }
}
