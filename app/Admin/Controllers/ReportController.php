<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\MainController;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\AdminUser;
use App\Models\Agency;
use App\Models\AgencyMangerPullingOut;
use App\Models\Bd;
use App\Models\BdSalary;
use App\Models\Config;
use App\Models\User;
use App\Models\UserSallary;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends MainController
{
    public $permission_name = 'reports';

    public function index(Content $content)
    {
        $name = request('name', 'users');

        $title = match ($name) {
            'users'     => __('Host reports'),
            'agencies'  => __('agencies report'),
            'bd'        => __('BD report'),
            default     => __('Host reports'),
        };

        $total_sallary = BdSalary::sum(DB::raw('salary - cut_amount'));

        // Start building content
        $content = $content
            ->title($title)
            ->description(__(request('desc', 'users')));

        // BD total salary card
        if ($name === 'bd') {
            $formattedSalary = number_format($total_sallary, 2);
            $label = __('total_sallary');

            Admin::style('
                .bd-stat-wrapper {
                    display: flex;
                    justify-content: center;
                    width: 100%;
                    margin-bottom: 20px;
                }
                .bd-stat-card {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 24px;
                    background: linear-gradient(135deg, #1e2632 0%, #161d27 100%);
                    border-radius: 16px;
                    padding: 24px 48px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                    border: 1px solid rgba(60, 141, 188, 0.3);
                    min-width: 320px;
                }
                .bd-stat-icon {
                    width: 56px;
                    height: 56px;
                    background: rgba(60, 141, 188, 0.15);
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 28px;
                    color: #3c8dbc;
                }
                .bd-stat-info { display: flex; flex-direction: column; align-items: center; }
                .bd-stat-label { font-size: 14px; color: #a8b5c4; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }
                .bd-stat-value { font-size: 36px; font-weight: 700; color: #3c8dbc; margin-top: 4px; }
            ');

            $content->row("
                <div class='bd-stat-wrapper'>
                    <div class='bd-stat-card'>
                        <div class='bd-stat-icon'><i class='fa fa-dollar'></i></div>
                        <div class='bd-stat-info'>
                            <span class='bd-stat-label'>{$label}</span>
                            <span class='bd-stat-value'>\${$formattedSalary}</span>
                        </div>
                    </div>
                </div>
            ");
        }

        // main row
        $content->row(function ($row) {
            $row->column(2, view('admin.grid.common.actions'));
            $row->column(10, $this->grid());
        });

        return parent::index($content);
    }

    protected function grid()
    {
        $name = request('name', 'users');

        abort_if(
            !method_exists($this, $name),
            404,
        );

        $grid = $this->{$name}();

        return $grid;
    }

    protected function users()
    {
        $grid = new Grid(new User());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();

        $month = (int) request('month', now()->month);
        $year  = (int) request('year', now()->year);

        $salarySub = DB::table('user_sallaries')
            ->selectRaw('
        user_id,
        SUM(sallary) as salary_sum,
        SUM(cut_amount) as cut_sum,
        SUM(sallary - cut_amount) as total_salary
    ')
            ->where('is_paid', 0)
            ->where('month', $month)
            ->where('year', $year)
            ->groupBy('user_id');

        $diamondSub = DB::table('user_target')
            ->selectRaw('
        user_id,
        SUM(user_diamonds) as diamonds
    ')
            ->where('add_month', $month)
            ->where('add_year', $year)
            ->groupBy('user_id');

        $grid->model()
            ->leftJoinSub(
                $salarySub,
                'salary_table',
                fn($j) =>
                $j->on('salary_table.user_id', '=', 'users.id')
            )
            ->leftJoinSub(
                $diamondSub,
                'diamond_table',
                fn($j) =>
                $j->on('diamond_table.user_id', '=', 'users.id')
            )

            ->with([
                'latestUserSallary',
                'latestTarget',
                'profile',
                'packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'),
                'targets',
                'userSallary' => fn($q) => $q->where('month', $month)->where('year', $year),
                'agency'
            ])
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->where('agency_id', '!=', 0)
            ->where('agency_id', '!=', '')
            ->where('agency_id', '!=', null)
            ->select([
                'users.id',
                'users.name',
                'users.uuid',
                'users.agency_id',
                'users.sender_level',
                'users.received_level',
                'users.special_id',
                'users.country_id',
                DB::raw('COALESCE(diamond_table.diamonds,0) as diamonds'),
                DB::raw('COALESCE((SELECT FLOOR(SUM(us.cut_amount)) FROM user_sallaries us
              WHERE us.user_id = users.id
                AND us.user_agency_id = users.agency_id
                AND us.is_paid = 0
                AND concat(us.year,"-", us.month) <= "' . "$year-$month" . '"),0) as expenses'),
                DB::raw('COALESCE(salary_table.total_salary,0) as total'),
            ]);

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('id', __('ID'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('uuid', __('Unique ID'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('agency_id', __('agency'))
                    ->select(Common::by_agency_filter());
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if (!empty($this->input)) {
                        $query->whereHas(
                            'userSallary',
                            fn($q) =>
                            $q->where('year', $this->input)
                        );
                    }
                }, __('Year'), 'year')->integer();
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if (!empty($this->input)) {
                        $query->whereHas(
                            'userSallary',
                            fn($q) =>
                            $q->where('month', $this->input)
                        );
                    }
                }, __('Month'), 'month')->integer();
            });
        });
        $grid->column('id', __('Id'));

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());
        $grid->column('diamonds', __('diamond'))->display(function ($v) {
            $diamond = floor($v ?? 0);
            $image = asset('images/diamond.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center; '>
                    <span>{$diamond}</span>
                    <img src='{$image}' alt='USD' width='20' height='20'>
                </div>";
        });

        $grid->column('target', __('Target'))->display(function () {
            return $this->latestTarget->target_id ?? 0;
        });


        $grid->column('expenses', __('Expenses'))->display(function ($v) {
            return (int) ($v ?? 0);
        });

        $grid->column('total', __('salary'))->display(function ($v) {
            $salary = round($v, 2) ?? 0;
            $image = asset('images/dollar.jpg'); // Adjust path as needed
            return "<div style='display: flex; align-items: center;'>
                    <span>{$salary}</span>
                    <img src='{$image}' alt='USD' width='20' height='20'>
                </div>";
        });

        $grid->column('sallary_year', __('Year'))->display(function () {
            return $this->latestUserSallary->year ?? '-';
        });

        $grid->column('sallary_month', __('Month'))->display(function () {
            $month = request('month') ?? now()->month;
            $monthName = Carbon::create()->month($month)->translatedFormat('F'); // اسم الشهر حسب اللغة

            $sallary = $this->userSallary;


            return $sallary ? $monthName : '-';
        });


        $grid->column('moments_and_reels', __('Moments & Reels'))->display(function () {
            $salary = $this->userSallary;

            if (!$salary || empty($salary->extras)) {
                return '<span style="color:#aaa">No Data</span>';
            }

            $extras = json_decode($salary->extras, true);

            return view('moments-reels', ['extras' => $extras])->render();
        });

        $grid->column('agency', __('agency'))->display(function () {
            $name = @$this->agency->name ?? '';
            $path = @$this->agency->img;
            $defaultImage = asset("images/icon-agency.jpg");
            $url = getImagePath($path) ?? $defaultImage;
            $showUrl = $this->agency ? url("admin/agencies/profile/{$this->agency->id}") : 0;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->id, $url, 40, 40);

            return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                    $image
                    <span>$name</span>
                </a>
            </div>
        ";
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append('<a href="' . route('admin.custom-export-users', ['month' => request('month'), 'year' => request('year'), 'agency_id' => request('agency_id'), 'id' => request('id')]) . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');
        });

        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();


        return $grid;
    }

    protected function agencies(): Grid
    {
        $grid = new Grid(new Agency());
        $countryID = Common::filterCountryIds();
        $month = request('month', now()->month);
        $year  = request('year', now()->year);

        $grid->disableRowSelector();

        $userSalarySub = DB::table('user_sallaries')
            ->selectRaw('
            user_agency_id as agency_id,
            SUM(target_diamonds) as total_target
        ')
            ->where('month', $month)
            ->where('year', $year)
            ->groupBy('user_agency_id');

        $agencySalarySub = DB::table('agency_sallaries')
            ->selectRaw('
            agency_id,
            SUM(sallary) as salary_sum,
            SUM(cut_amount) as cut_sum,
            SUM(sallary - cut_amount) as net_salary
        ')
            ->where('is_paid', 0)
            ->where(DB::raw('concat(year,"-", month)'), '<=', "$year-$month")
            ->groupBy('agency_id');

        // $grid->model()
        //     ->when($countryID, fn($q) => $q->where('country_id', $countryID))
        //     ->withCount(['users'])
        //     ->with(['owner.profile']);

        $grid->model()

            ->leftJoinSub(
                $userSalarySub,
                'user_salary_table',
                fn($j) => $j->on('user_salary_table.agency_id', '=', 'agencies.id')
            )
            ->leftJoinSub(
                $agencySalarySub,
                'agency_salary_table',
                fn($j) => $j->on('agency_salary_table.agency_id', '=', 'agencies.id')
            )
            ->when($countryID, fn($q) => $q->whereIn('agencies.country_id', $countryID))
            //  ->with(['owner.profile'])

            ->select([
                'agencies.*',
                DB::raw('COALESCE(user_salary_table.total_target,0) as total_target'),
                DB::raw('COALESCE(agency_salary_table.salary_sum,0) as total_salary'),
                DB::raw('COALESCE(agency_salary_table.cut_sum,0) as expenses'),
                DB::raw('COALESCE(agency_salary_table.net_salary,0) as net_salary'),
            ])->withCount(['users']);

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();

            $filter->disableIdFilter();

            $filter->equal('id', __('dashboard.agency'))
                ->select()
                ->ajax(route('admin.filter-agencies'));

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $year = request('year');
                    if (!empty($year)) {
                        $query->whereHas('agencySalaries', fn($q) => $q->where('year', $year));
                    }
                }, __('Year'), 'year')->integer();
            });

            $filter->where(function ($query) {
                $month = request('month');
                if (!empty($month)) {
                    $query->whereHas('agencySalaries', fn($q) => $q->where('month', $month));
                }
            }, __('Month'), 'month')->integer();
        });
        $grid->column('id', __('ID'));

        $grid->column('agency', __('dashboard.agency'))->display(function () {
            $defaultImage = asset('images/icon-agency.jpg');
            $url = getImagePath($this->img) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $profileUrl = route('admin.agency.profile', ['id' => $this->id]);

            return "<a href='{$profileUrl}' style='text-decoration: none; color: inherit;'>
                        <div style='display: flex; align-items: center; gap: 10px;'>
                            <img src='$url' style='height: 40px !important; width: 40px !important; object-fit: cover;' />
                            <div style='display: flex; flex-direction: column;'>
                                <span style='text-decoration: underline; cursor: pointer;'>{$this->name}</span>
                                <span style='font-size: smaller;'>ID: {$this->id}</span>
                            </div>
                        </div>
                    </a>";
        });



        $grid->column('total_target', __('target'))->display(function ($v) {
            $value = is_array($v) ? ($v['target'] ?? 0) : $v;
            return floor((float) $value);
        });


        $grid->column('net_salary', __('Net Salary'))->display(fn($v) => round($v, 2));


        $grid->column('expenses', __('expenses'))->display(fn($v) => round($v, 2));




        $grid->column('total_salary', __('salary'))->display(function ($v) {
            $salary = round($v, 2);
            $image = asset('images/dollar.jpg');
            return "<div style='display: flex; align-items: center;'>
                    <span>{$salary}</span>
                    <img src='{$image}' alt='USD' width='20' height='20'>
                </div>";
        });


        $grid->column('users_count', __('dashboard.hosts'))->display(function ($value) {
            return '<a href="?name=users&desc=' . $this->name . '&aid=' . $this->id . '">' . $value . '</a>';
        });

        $grid->tools(function (Grid\Tools $tools) {
            $query = http_build_query([
                'id' => request('id'),
                'month' => request('month'),
                'year' => request('year'),
            ]);

            $tools->append('<a href="' . route('agency-export-report') . '?' . $query . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i> ' . __('admin.exportExcel') . '</a>');
        });

        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();

        return $grid;
    }

    protected function agencies_manger()
    {
        $grid = new Grid(new AdminUser());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();
        $grid->model()->with([
            'user',
            'user.profile',
            'user.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
        ])
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->where('app_id', '!=', 0);

        $grid->column('user.id', __('Id'));

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->equal('user.id', 'User ID');
        });
        $grid->column('user.name', __('name'))->display(function ($name) {
            $uid = @$this->user->uuid;
            $path = @$this?->user->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = $this->user ? url("admin/users/{$this->user->id}") : 0;

            return "<div class='user-card'>
                    $image
                    <div class='user-info'>
                       <a href='{$showUrl}' class='user-name'>$name</a>
                       <span class='user-uuid'>UUID: $uid</span>
                    </div>
                </div>";
        });
        // Add custom styling for agencies manager table
        Admin::style('
            .agencies-manager-table .due-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 14px;
                min-width: 100px;
                justify-content: center;
            }
            .agencies-manager-table .due-badge.loading {
                background: #f0f0f0;
                color: #888;
            }
            .agencies-manager-table .due-badge.positive {
                background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                color: #155724;
                border: 1px solid #28a745;
            }
            .agencies-manager-table .due-badge.zero {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                color: #6c757d;
                border: 1px solid #dee2e6;
            }
            .agencies-manager-table .user-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 8px;
                border-radius: 12px;
                transition: background 0.2s;
            }
            .agencies-manager-table .user-card:hover {
                background: #f8f9fa;
            }
            .agencies-manager-table .user-info {
                display: flex;
                flex-direction: column;
            }
            .agencies-manager-table .user-name {
                font-weight: 600;
                color: #333;
                text-decoration: none;
            }
            .agencies-manager-table .user-uuid {
                font-size: 12px;
                color: #888;
                font-family: monospace;
            }
            .agencies-manager-table .spinner {
                width: 16px;
                height: 16px;
                border: 2px solid #ddd;
                border-top-color: #28a745;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        ');

        $grid->column('due', __('Due'))->display(function () {
            $url = admin_url('due-salary') . '?' . http_build_query([
                'id'     => $this->id,
                'app_id' => $this->app_id,
            ]);

            return <<<HTML
            <div class="due-salary due-badge loading" data-url="{$url}">
                <div class="spinner"></div>
                <span class="salary-value">جاري التحميل...</span>
            </div>
            HTML;
        });

        Admin::script(<<<JS
            // Add agencies-manager-table class to table for styling
            document.querySelector('.grid-table')?.classList.add('agencies-manager-table');
            
            document.querySelectorAll('.due-salary').forEach(el => {
                fetch(el.dataset.url)
                    .then(res => res.json())
                    .then(data => {
                        const value = parseFloat(data.salary) || 0;
                        const valueEl = el.querySelector('.salary-value');
                        valueEl.innerText = '$' + value.toLocaleString();
                        
                        // Remove spinner and loading class
                        el.querySelector('.spinner')?.remove();
                        el.classList.remove('loading');
                        
                        // Add appropriate class based on value
                        if (value > 0) {
                            el.classList.add('positive');
                        } else {
                            el.classList.add('zero');
                        }
                    })
                    .catch(() => {
                        const valueEl = el.querySelector('.salary-value');
                        valueEl.innerText = '$0';
                        el.querySelector('.spinner')?.remove();
                        el.classList.remove('loading');
                        el.classList.add('zero');
                    });
            });
        JS);
        $grid->export(function ($export) {
            $export->filename('report');
            $export->column('uuid', function ($value, $original) {
                return $value;
            });
        });

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append('<a href="' . route('admin.agency-manger-export', [
                'user_id' => request('user.id') ?? request('filters.user.id') ?? null
            ]) . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i> ' . __('admin.exportExcel') . '</a>');
        });
        $grid->disableExport();

        return $grid;
    }

    protected function bd()
    {
        $grid = new Grid(new Bd());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();

        $grid->model()->with([
            'bdSalaries',
            'appUser.country',
            'appUser.senderLevel',
            'appUser.receiverLevel',
            'appUser',
            'appUser.profile',
            'appUser.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
        ])->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->where('app_id', '!=', 0)
            ->addSelect([
                'net_salary_calc' => DB::table('bd_salaries')
                    ->selectRaw('COALESCE(SUM(salary - cut_amount), 0)')
                    ->whereColumn('bd_salaries.bd_id', 'admin_users.id')
            ])
            ->orderByDesc('net_salary_calc');
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->expand();
            $filter->where(function ($query) {
                $value = $this->input;
                $query->where('id', $value)
                    ->orWhereHas('appUser', function ($q) use ($value) {
                        $q->where('uuid', $value);
                    });
            }, __('BD ID / User UUID'), 'bd_or_uuid');
        });

        // Add BD table styling
        Admin::style('
            .bd-report-table .bd-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 8px;
                border-radius: 12px;
                transition: all 0.2s ease;
            }
            .bd-report-table .bd-card:hover {
                background: rgba(40, 167, 69, 0.05);
                transform: translateX(4px);
            }
            .bd-report-table .bd-info {
                display: flex;
                flex-direction: column;
            }
            .bd-report-table .bd-name {
                font-weight: 600;
                color: #333;
                text-decoration: none;
                font-size: 14px;
            }
            .bd-report-table .bd-meta {
                font-size: 12px;
                color: #888;
                font-family: monospace;
            }
            .bd-report-table .due-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 10px 20px;
                border-radius: 25px;
                font-weight: 700;
                font-size: 15px;
                min-width: 120px;
                justify-content: center;
                background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                color: #155724;
                border: 2px solid #28a745;
                box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
            }
            .bd-report-table .due-badge.zero {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                color: #6c757d;
                border: 2px solid #dee2e6;
                box-shadow: none;
            }
            .bd-report-table .due-badge i {
                color: #28a745;
                font-size: 16px;
            }
        ');

        $grid->column('id', __('Id'));

        $grid->column('username', __('account dashboard'))->display(function ($name) {
            $uid = @$this->id;
            $path = @$this?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = url("admin/usersBd/{$this->id}");

            return "<div class='bd-card'>
                    $image
                    <div class='bd-info'>
                       <a href='{$showUrl}' class='bd-name'>$name</a>
                       <span class='bd-meta'>BD ID: $uid</span>
                    </div>
                </div>";
        });


        $grid->column('appUser', __('account user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->appUser);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('due', __('Due'))->display(function () {
            $salary = round($this->net_sallary ?? 0, 2);
            $class = $salary > 0 ? '' : 'zero';
            return "<div class='due-badge {$class}'>
                    <i class='fa fa-dollar'></i>
                    <span>" . number_format($salary, 2) . "</span>
                </div>";
        });

        $grid->disableExport();
        $grid->disableActions();
        $grid->disableCreateButton();

        // Add bd-report-table class
        Admin::script("
            document.querySelector('.grid-table')?.classList.add('bd-report-table');
        ");

        return $grid;
    }


    public function momentsReels(Request $request)
    {
        $userId = $request->user_id;
        $month  = $request->month;
        $year   = $request->year;
        //  dd( $userId,$month, $year);
        $salary = UserSallary::query()
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$salary || empty($salary->extras)) {
            return response()->json([
                'html' => '<span style="color:#aaa">No Data</span>'
            ]);
        }

        $extras = json_decode($salary->extras, true);

        return response()->json([
            'html' => view('moments-reels', [
                'extras' => $extras
            ])->render()
        ]);
    }

    public function dueSalary(Request $request)
    {
        $managerId = (int) $request->get('app_id');
        $adminUserId = (int) $request->get('id');

        $adminUser = AdminUser::findOrFail($adminUserId);
        $agencies = $adminUser->managerAgenciesWithoutScope()->get();

        if ($agencies->isEmpty()) {
            return response()->json(['salary' => 0]);
        }

        $totalSalary = $agencies->toQuery()
            ->withSum('agencySalaries as total_salaries', 'sallary')
            ->get()
            ->sum('total_salaries');

        $config = Config::where('name', 'agency_manager_percentage')->first();
        $percentage = ((int) ($config->value ?? 100)) / 100;

        $pullingOut = (float) AgencyMangerPullingOut::where(
            'agency_manger_id',
            $managerId
        )->sum('amount');

        $netSalary = ($totalSalary * $percentage) - $pullingOut;

        return response()->json([
            'salary' => floor($netSalary),
        ]);
    }

    public function expenses(Request $request)
    {
        $id = $request->id;
        $month = $request->month ?? now()->month;
        $year  = $request->year ?? now()->year;

        $user = User::find($id);
        if (!$user) {
            return response()->json(['expenses' => 0]);
        }

        if ($user->agency_id) {
            $userSallary = UserSallary::query()
                ->where('user_id', $user->id)
                ->where('user_agency_id', $user->agency_id)
                ->where('is_paid', 0)
                ->where(DB::raw('concat(year,"-", month)'), '<=', "$year-$month")
                ->sum(DB::raw('cut_amount'));

            return response()->json([
                'expenses' => floor($userSallary ?? 0),
            ]);
        }

        return response()->json(['expenses' => 0]);
    }
}
