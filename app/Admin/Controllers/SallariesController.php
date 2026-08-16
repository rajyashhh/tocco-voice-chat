<?php

namespace App\Admin\Controllers;

use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Models\Agency;
use App\Models\AgencySallary;
use App\Models\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function request;

class SallariesController extends MainController
{
    public $permission_name = 'users-Wallet';

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans('Agencies Wallet'))
            ->description(__(request('desc') ?: 'users'))
            ->row(function ($row) {
                $row->column(3, $this->salaryNavbar());
                $row->column(9, $this->grid());
            }));
    }

    protected function grid()
    {
        $name = request('name') ?: 'users';


        $grid = $name;
        $grid = $this->{$grid}();
        $grid->disableexport();
        $grid->disableActions();
        $grid->disableCreateButton();
        return $grid;
    }

    protected function users()
    {
        $grid = new Grid(new User());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();
        $month = request('month');
        $year  = request('year');

        $grid->model()
            ->with([
                'profile',
                'country',
                'senderLevel',
                'receiverLevel',
                'packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
            ])
            ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
            ->where('agency_id', '!=', 0)

            ->leftJoin('user_sallaries as us', function ($join) use ($month, $year) {
                $join->on('us.user_id', '=', 'users.id')
                    ->where('us.is_paid', 0)->orderByDesc('id');

                if ($month && $year) {
                    $join->where('us.year', $year)
                        ->where('us.month', $month);
                }
            })

            ->selectRaw('
        users.id,
        users.name,
        users.uuid,
        users.country_id,
        users.agency_id,
        users.online,
        users.sender_level, 
        users.received_level,
        users.special_id,
        users.created_at,
        users.updated_at,
        COALESCE(SUM(us.sallary),0) AS sallary,
        COALESCE(SUM(us.cut_amount),0) AS withdrawal,
        COALESCE(SUM(us.sallary - us.cut_amount),0) AS total
    ')
            ->groupBy(
                'users.id',
                'users.name',
                'users.uuid',
                'users.country_id',
                'users.agency_id',
                'users.online',
                'users.created_at',
                'users.updated_at',
                'users.sender_level',
                'users.received_level',
                'users.special_id'
            );

        $grid->filter(function (Grid\Filter $filter) {
            $filter->disableIdFilter();
            $filter->expand();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('uuid', __('uuid'));
            });

            $filter->column(1 / 2, function ($filter) {

                $filter->where(function ($query) {
                    $year = request('year');
                }, __('Year'), 'year')->integer();
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $month = request('month');
                }, __('Month'), 'month')->integer();
            });
        });

        $grid->column('name', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());

        $grid->column('total', __('net salary'))->display(function ($v) {
            $v = truncateAndTrim($v) ?? 0;
            $img = asset('images/dollar.jpg');
            return "<div style='display:flex;align-items:center'>
              <span>{$v}</span>
              <img src='{$img}' width='20'>
            </div>";
        });

        $grid->column('sallary', __('salary'))->display(function ($v) {
            $v = truncateAndTrim($v) ?? 0;
            $img = asset('images/dollar.jpg');
            return "<div style='display:flex;align-items:center'>
              <span>{$v}</span>
              <img src='{$img}' width='20'>
            </div>";
        });


        $grid->column('withdrawal', __('withdrawal'))->display(function ($v) {
            $v = truncateAndTrim($v) ?? 0;
            $img = asset('images/dollar.jpg');
            return "<div style='display:flex;align-items:center'>
              <span>{$v}</span>
              <img src='{$img}' width='20'>
            </div>";
        });

        $grid->tools(function (Grid\Tools $tools) {
            //  $tools->append('<a href="' . url('admin/wallet-export-users?uuid=' . request('uuid')) . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');
            $tools->append('<a href="' . url('admin/wallet-export-users?uuid=' . request('uuid') . '&month=' . request('month') . '&year=' . request('year')) . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');

            $tools->append('<a href="' . url('/admin/sallaries_history?type=0') . '"  class="btn btn-sm btn-success">' . __('admin.history') . '</a>');
        });

        return $grid;
    }

    protected function agencies()
    {
        $grid = new Grid(new Agency());
        $countryID = Common::filterCountryIds();

        //     $grid->model()->when($countryID, fn($q) => $q->where('country_id', $countryID));

        $month = request('month');
        $year  = request('year');

        $agencySalarySub = AgencySallary::query()
            ->selectRaw('
        agency_id,
        SUM(sallary) AS salary,
        SUM(cut_amount) AS withdrawal,
        SUM(sallary - cut_amount) AS total
    ')
            ->when(
                $month && $year,
                fn($q) =>
                $q->where('year', $year)
                    ->where('month', $month)
            )
            ->groupBy('agency_id');
        $grid->model()
            ->when($countryID, fn($q) => $q->whereIn('agencies.country_id', $countryID))
            ->leftJoinSub($agencySalarySub, 'asum', 'asum.agency_id', '=', 'agencies.id')
            ->select([
                'agencies.*',
                DB::raw('COALESCE(asum.salary, 0) AS sallary'),
                DB::raw('COALESCE(asum.withdrawal, 0) AS withdrawal'),
                DB::raw('COALESCE(asum.total, 0) AS total'),
            ]);
        $grid->disableRowSelector();


        $grid->filter(function (Grid\Filter $filter) {
            $filter->disableIdFilter();
            $filter->expand();

            $filter->where(function ($query) {
                $query->where('agencies.id', $this->input) // Match directly on agency_id
                    ->orWhereHas('owner', function ($subQuery) {
                        $subQuery->where('uuid', $this->input); // Match on related owner UUID
                    });
            }, __('UUID'), 'agency_or_uuid')->placeholder(__('search for agency or host by UUID'));

            $filter->column(1 / 2, function ($filter) {

                $filter->where(function ($query) {
                    $year = request('year');
                }, __('Year'), 'year')->integer();
            });

            $filter->where(function ($query) {
                $month = request('month');
            }, __('Month'), 'month')->integer();
        });

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

                $profileUrl = route('admin.agency.profile', ['id' => $this->id]);

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
        // $grid->column('total', __('net salary'))
        //     ->display(function () {
        //         $usd =   @$this->sumNetSalary(request('month'), request('year')) ?? 0;
        //         $image = asset('images/dollar.jpg'); // Adjust path as needed
        //         $usd = rtrim(rtrim(number_format($usd, 10, '.', ''), '0'), '.');
        //         return "<div style='display: flex; align-items: center; '>

        //                 <span>{$usd}</span>
        //                   <img src='{$image}' alt='USD' width='20' height='20'>
        //             </div>";
        //     })->default(0);

        $grid->column('total', __('net salary'))->display(function ($v) {
            $v = truncateAndTrim($v) ?? 0;
            $img = asset('images/dollar.jpg');
            return "<div style='display:flex;align-items:center'>
              <span>{$v}</span>
              <img src='{$img}' width='20'>
            </div>";
        });
        // $grid->column('salary', __('salary'))->display(function () {
        //     $usd =   @$this->sumSalary(request('month'), request('year')) ?? 0;
        //     $image = asset('images/dollar.jpg'); // Adjust path as needed
        //     return "<div style='display: flex; align-items: center; '>

        //                 <span>{$usd}</span>
        //                   <img src='{$image}' alt='USD' width='20' height='20'>
        //             </div>";
        // })->default(0);

        $grid->column('sallary', __('salary'))->display(function ($v) {
            $v = truncateAndTrim($v) ?? 0;
            $img = asset('images/dollar.jpg');
            return "<div style='display:flex;align-items:center'>
              <span>{$v}</span>
              <img src='{$img}' width='20'>
            </div>";
        });

        // $grid->column('withdrawal', __('withdrawal'))->display(function () {
        //     $usd =   @$this->sumCutAmount(request('month'), request('year')) ?? 0;

        //     $image = asset('images/dollar.jpg'); // Adjust path as needed
        //     return "<div style='display: flex; align-items: center; '>

        //                 <span>{$usd}</span>
        //                   <img src='{$image}' alt='USD' width='20' height='20'>
        //             </div>";
        // })->default(0);

        $grid->column('withdrawal', __('withdrawal'))->display(function ($v) {
            $v = floor($v ?? 0);
            $img = asset('images/dollar.jpg');
            return "<div style='display:flex;align-items:center'>
              <span>{$v}</span>
              <img src='{$img}' width='20'>
            </div>";
        });


        $grid->tools(function (Grid\Tools $tools) {
            //  $tools->append('<a href="' . url('admin/wallet-export-agency?id=' . request('2f787fe5f965209024c8597149cbb43e')) . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');
            $tools->append('<a href="' . url('admin/wallet-export-agency') . '?' . http_build_query([
                'id' => request('agency_or_uuid'),
                'month' => request('month'),
                'year' => request('year'),
            ]) . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i> ' . __('admin.exportExcel') . '</a>');
            $tools->append('<a href="' . url('/admin/sallaries_history?type=1') . '"  class="btn btn-sm btn-success">' . __('admin.history') . '</a>');
        });

        return $grid;
    }

    private function salaryNavbar()
    {
        $content = new Row();

        $box = (new Box(
            title: __('Fields'),
            content: view('admin.grid.common.salaries')
        ));
        $content->column(12, $box);
        $box = (new Box(
            title: __('Details'),
            content: view('admin.grid.common.salaries-statistics')
        ))->collapsable();
        $content->column(12, $box);


        return $content;
    }
}
