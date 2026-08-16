<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use Carbon\Carbon;
use App\Models\Charge;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use App\Helpers\Common;
use App\Models\CoinLog;
use Encore\Admin\Admin;
use App\Helpers\UserCommon;
use App\Models\ExchangeLog;
use App\Models\PaymentCoin;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\HasResourceActions;

class ChargeReportController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    public $permission_name = 'charger-reports';
    use HasResourceActions;

    public function index(Content $content)
    {
        return parent::index($content
            ->title(trans("Reports"))
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $box = new Box();
                    $box->title(__('Fields'));
                    $box->content($this->combinedContent());
                    $column->append($box);
                });
            }));
    }

    private function combinedContent()
    {
        $tabs = $this->tabsComponent();
        $grid = $this->grid()->render();

        return "<div style='margin-bottom: 20px;'>{$tabs}</div>{$grid}";
    }

    protected function grid()
    {
        $name = "result";
        if (request("name") == "stripe") {
            $name = "stripe";
        }
        if (request("name") == "in-app-purchas") {
            $name = "in_app_purchas";
        }
        if (request("name") == "exchange") {
            $name = "exchange";
        }


        $grid = $name;
        $grid = $this->{$grid}();
        $grid->disableexport();
        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableColumnSelector();

        return $grid;
    }

    public function form()
    {
        $form = new Form(new Charge);
        return $form;
    }


    protected function result()
    {
        $charger_type = "";
        if (request("name") == "shipping-agency-activity") {
            $charger_type = "shipping-agency-activity";
        } elseif (request("name") == "host") {
            $charger_type = "host";
        } else {
            $charger_type = "dash";
        }

        $grid = new Grid(new Charge());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();
        $grid->model()
            ->when($countryID, fn($q) =>
            $q->where(function ($q) use ($countryID) {
                $q->whereHas('receiver', fn($q) => $q->whereIn('country_id', $countryID))
                    ->orWhereHas('agency', fn($q) => $q->whereIn('country_id', $countryID));
            }))
            ->orderByDesc('created_at')->with([
                // 'sender',
                // 'sender.profile:id,user_id,avatar',
                'receiver',
                'receiver.profile:id,user_id,avatar',
                // 'sender.packs' => function ($q) {
                //     $q->whereIn('type', [25])
                //         ->where('is_used', true)
                //         ->with('ware:id,value');
                // },
                'receiver.packs' => function ($q) {
                    $q->whereIn('type', [25])
                        ->where('is_used', true)
                        ->with('ware:id,value');
                },
                'admin',
                'admin.agency',
                'senderUser',
                'senderUser.profile:id,user_id,avatar',
                'senderUser.packs' => function ($q) {
                    $q->whereIn('type', [25])
                        ->where('is_used', true)
                        ->with('ware:id,value');
                },
                'receiveragency',

            ]);

        if ($charger_type == "dash") {
            $grid->model()->where('charger_type', "dash");
        } elseif (request("name") == "host") {

            $grid->model()->where('charger_type', 'host_agency')->with([
                'senderAgency',
                'senderAgency.owner',
                'senderAgency.owner.packs' => function ($q) {
                    $q->whereIn('type', [25])
                        ->where('is_used', true)
                        ->with('ware:id,value');
                },
                'senderAgency.owner.specialId.ware',
                'senderAgency.owner.packs'
            ]);
        } else {
            $grid->model()->where(function ($query) {
                $query->where('charger_type', 'agency')
                    ->orWhere('user_type', 'agency');
            });
        }
        if ($charger_type == "shipping-agency-activity") {
            $this->filterShipping($grid);
        }

        Admin::style("
            @media (min-width: 992px) {
                .ltr label {
                    margin: 0 20px 0 0 !important;
                }
                .col-md-8 {
                    width: auto !important;
                }
            }
        ");
        if ($charger_type != "shipping-agency-activity") {
            $this->filterChargeDash($grid);
        }
        $grid->model()->when(request('from_date') && request('to_date'), function ($query,) {

            $start = Carbon::parse(convertArabicToEnglishNumbers(request('from_date')))->startOfDay();
            $end = Carbon::parse(convertArabicToEnglishNumbers(request('to_date')))->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        })->when(!request('from_date'), function ($query,) {

            $start = now()->startOfMonth();
            $end = $end = now()->endOfMonth();
            $query->whereBetween('created_at', [$start, $end]);
        });
        $grid->column('id', __('transaction id'));
        $grid->column('charger_id', __("sender"))->display(function () use ($charger_type) {

            $sender = Common::getChargerInfoII($this);
            if (empty($sender['name']) && empty($sender['uuid'])) {
                return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                            <span style=' cursor: pointer;'>Unknown </span>

                </div>
            ";
            }

            $name = $sender['name'];
            $uuid = $sender['uuid'];
            $path = $sender['image'];
            $showUrl = $sender['url'];
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }


            $imageStyle = $this->charger_type == 'agency'
                ? 'width: 40px; height: 40px; object-fit: cover; border-radius: 0;'     // rectangle
                : 'width: 40px; height: 40px; object-fit: cover; border-radius: 50%;';
            $image = "<img src='{$url}' alt='User Image' style='{$imageStyle}'>";

            return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                            <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uuid</span>
                    </div>
                </div>
            ";
        });
        $grid->column('user_id', __('recipient'))->display(function ($recever) {


            $sender = Common::getReceiverInfoII($this);
            if (empty($sender['name']) && empty($sender['uuid'])) {
                return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                            <span style=' cursor: pointer;'>Unknown </span>

                </div>
            ";
            }
            $name = $sender['name'];
            $uid = $sender['uuid'];
            $path = $sender['image'];
            $defaultImage = asset("images/businessman-icon.jpg");
            $showUrl = $sender['url'];
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $imageStyle = $this->user_type == 'agency'
                ? 'width: 40px; height: 40px; object-fit: cover; border-radius: 0;'     // rectangle
                : 'width: 40px; height: 40px; object-fit: cover; border-radius: 50%;';
            $image = "<img src='{$url}' alt='User Image' style='{$imageStyle}'>";

            return "
            <div style='display: flex; align-items: center; gap: 10px;'>
                $image
                <div>
                    <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                            <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                    <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                </div>
            </div>
        ";
        });

        if (request("name") == "dash") {
            $grid->column('agency_id', __('Agency'))->display(function () {
                if (!$this->agency) {
                    return "<span style='color: #aaa;'>No Agency</span>";
                }

                $name = $this->agency->name ?? 'Unknown Agency';
                $coins = number_format($this->agency->coins ?? 0);
                $path = $this->agency->img ?? '';
                $defaultImage = asset("images/agency-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;
                $icon = asset('images/coin.jpg'); // تأكد من وجود الصورة في هذا المسار

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <strong>$name</strong><br>
                        <span style='color: green;'> Coins: $coins</span>
                        <img src='{$icon}' alt='Coin' width='20' height='20'>
                    </div>
                </div>
                ";
            });
        }

        $grid->column('usd', __('amount $'))->display(function ($coin) {
            $icon = asset('images/dollar.jpg'); // تأكد من وجود الصورة في هذا المسار
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });

        $image = asset('images/coin.png');
        $grid->column('amount', __('coins') . ' ' . "<img src='{$image}' alt='USD' width='20' height='20' style='vertical-align: middle;'> ")
            ->display(function ($coin) {
                $image = asset('images/coin.png'); // تأكد من أن الصورة موجودة

                return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$coin}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
            });

        if ($charger_type == "dash") {
            $grid->column('status', __('Status'))->display(function () {
                if ($this->amount > 1) {
                    return '<span style="display:inline-block; padding:5px 10px; font-size:12px; font-weight:bold; border-radius:4px; background-color:#28a745; color:white;">Increment</span>';
                } elseif ($this->amount < 0) {
                    return '<span style="display:inline-block; padding:5px 10px; font-size:12px; font-weight:bold; border-radius:4px; background-color:#dc3545; color:white;">Decrement</span>';
                }
            });
        }

        $grid->column('created_at', __('shipping date'));
        if (request("name") == "host" || request("name") == null) {
            $grid->tools(function (Grid\Tools $tools) {
                $query = http_build_query([
                    'from_date' => request('from_date') ? convertArabicToEnglishNumbers(request('from_date')) : '',
                    'to_date' => request('from_date') ? convertArabicToEnglishNumbers(request('to_date')) : '',
                    'filter_type' => request('filter_type'),
                    'filtering' => request('filtering'),
                    'name' => request("name"),
                ]);

                $tools->append('<a href="' . url('/admin/exchange-charge-history') . '?' . $query . '" target="_blank" class="btn btn-sm btn-success">
                <i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');
            });
        }

        $this->extendGrid($grid);
        return $grid;
    }

    public function filterShipping($grid)
    {
        $grid->filter(function (Grid\Filter $filter) {

            if (!request()->has('filter_type')) {
                request()->merge(['filter_type' => 'shipping']);
            }
            $filter->disableIdFilter();
            $filter->expand();

            $filter->column(1 / 4, function ($filter) {
                $filter->where(function () {}, __('Type'), 'filter_type')
                    ->select([
                        'user' => 'User',
                        'shipping' => 'Shipping Agency',
                    ])->default('shipping');
            });

            $filter->column(3 / 4, function ($filter) {
                $filter->where(function ($query) {
                    $type = request('filter_type');
                    $value = trim($this->input);

                    if ($type === 'user') {
                        $query->whereHas('senderUser', fn($q) => $q->where('uuid', $value))
                            ->orWhereHas('receiverUser', fn($q) => $q->where('uuid', $value));
                    } elseif ($type === 'shipping') {
                        $query->whereHas('senderShippingAgency', fn($q) => $q->where('id', $value))
                            ->orWhereHas('shippingAgency', fn($q) => $q->where('id', $value));
                    }
                }, __('Dynamic Sender/Receiver Filter'));
            });

            $filter->column(1 / 4, function ($filter) {
                $filter->where(function () {}, __('sender type'), 'sender_type')
                    ->select([
                        'user' => 'User',
                        'shipping' => 'Shipping Agency',
                    ])->default('shipping');
            });

            $filter->column(3 / 4, function ($filter) {
                $filter->where(function ($query) {
                    $type = request('sender_type');
                    $value = trim($this->input);

                    if ($type === 'user') {
                        $query->whereHas('senderUser', fn($q) => $q->where('uuid', $value));
                    } elseif ($type === 'shipping') {
                        $query->whereHas('senderShippingAgency', fn($q) => $q->where('id', $value));
                    }
                }, __('Sender UUID or Shipping Agency ID'));
            });

            $filter->column(1 / 4, function ($filter) {
                $filter->where(function () {}, __('receiver type'), 'receiver_type')
                    ->select([
                        'user' => 'User',
                        'shipping' => 'Shipping Agency',
                    ])->default('shipping');
            });

            $filter->column(3 / 4, function ($filter) {
                $filter->where(function ($query) {
                    $type = request('receiver_type');
                    $value = trim($this->input);

                    if ($type === 'user') {
                        $query->whereHas('receiverUser', fn($q) => $q->where('uuid', $value));
                    } elseif ($type === 'shipping') {
                        $query->whereHas('shippingAgency', fn($q) => $q->where('id', $value));
                    }
                }, __('Receiver UUID or Shipping Agency ID'));
            });


            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $from = request('from_date');
                }, __('From Date'), 'from_date')->date()->default(now()->startOfMonth()->toDateString());
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $to = request('to_date');
                }, __('To Date'), 'to_date')->date()->default(convertArabicToEnglishNumbers(request('to_date')));
            });
        });
    }
    public function filterChargeDash($grid)
    {
        $grid->filter(function (Grid\Filter $filter) {
            $filter->disableIdFilter();
            $filter->expand();
            $filter->column(1 / 4, function ($filter) {
                $filter->where(function () {}, __('Type'), 'filter_type')
                    ->select([
                        'user' => 'User',
                        'shipping' => 'Shipping Agency',
                    ])->default('shipping');
            });

            $filter->column(3 / 4, function ($filter) {
                $filter->where(function ($query) {
                    $input = $this->input;
                    $type = request('filter_type');
                    if ($type == 'user') {
                        $query->whereHas('receiverUser', function ($q) use ($input) {
                            $q->where('uuid', $input)
                                ->orWhere('name', 'like', "%$input%");
                        });
                    } else {
                        $query->whereHas('receiver', function ($q) use ($input) {
                            $q->where('id', $input)
                                ->orWhere('name', 'like', "%$input%");
                        });
                    }
                }, __('Receiver UUID or Shipping Agency ID'), 'filtering');
            });



            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {}, __('From Date'), 'from_date')->date()->default(now()->startOfMonth()->toDateString());
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {}, __('To Date'), 'to_date')->date()->default(convertArabicToEnglishNumbers(request('to_date')));
            });
        });
    }

    protected function stripe()
    {
        $grid = new Grid(new CoinLog());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();

        $grid->model()->when(!request('from_date'), function ($query,) {

            $start =   now()->startOfMonth();

            $end = $end = now()->endOfMonth();
            $query->where('created_at', '>=', $start);
        })->with([
            'user',
            'user.profile',
            'user.packs' => function ($q) {
                $q->whereIn('type', [25])
                    ->where('is_used', true)
                    ->with('ware:id,value');
            },
        ])
            ->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->orderByDesc('created_at');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
             $filter->disableIdFilter();
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user.uuid', __('charger'));
                $filter->equal('trx', __('trx'));
                 $filter->equal('id', __('transaction id'));

                $filter->where(function ($query) {
                    if ($this->input !== '') {
                        $query->where('method', $this->input);
                    }
                }, __('Select type'), 'method')->select(
                    ['' => __('All')] + PaymentCoin::orderBy('type')->pluck('title', 'type')->toArray()
                );

                $filter->equal('status', __('Status'))->select([
                    '' => __('All'),
                    1 => __('success'),
                    0 => __('failed'),
                ]);
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $from = request('from_date')
                        ? Carbon::parse(convertArabicToEnglishNumbers(request('from_date')))
                        : now()->startOfMonth();
                    $query->where('created_at', '>=', $from->startOfDay());
                }, __('From Date'), 'from_date')->date()->default(now()->startOfMonth()->toDateString());

                $filter->where(function ($query) {
                    if ($to = request('to_date')) {
                        $end = Carbon::parse(convertArabicToEnglishNumbers($to))->endOfDay();
                        $query->where('created_at', '<=', $end);
                    }
                }, __('To Date'), 'to_date')->date();
            });
        });

        Admin::script("
            function fixDateFilters() {
                $('.utd-custom-date').each(function() {
                    $(this).find('.input-group').css('width', '100%');
                    $(this).find('input').css('width', '100%');
                });

                // Stacking fix: elevate the active container
                $('.utd-custom-date input').on('focus click', function() {
                    $('.utd-custom-date').removeClass('active-date-container');
                    $(this).closest('.utd-custom-date').addClass('active-date-container');
                });
            }
            $('[name=from_date], [name=to_date]').closest('.form-group').addClass('utd-custom-date');
            fixDateFilters();
            $(document).on('pjax:complete', fixDateFilters);
        ");

        Admin::style("
            .utd-custom-date {
                display: block !important;
                margin-bottom: 20px !important;
                backdrop-filter: blur(5px) !important;
                padding: 15px !important;
                transition: all 0.3s ease !important;
                position: relative !important;
                overflow: visible !important;
            }
            .utd-custom-date:hover {
                background: rgba(255, 255, 255, 0.08) !important;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08) !important;
                transform: translateY(-2px) !important;
            }
            .utd-custom-date label {
                display: block !important;
                width: 100% !important;
                text-align: left !important;
                padding: 0 0 8px 0 !important;
                margin: 0 !important;
                white-space: nowrap !important;
                float: none !important;
                font-weight: 600 !important;
                color: #555 !important;
                font-size: 11px !important;
                letter-spacing: 0.2px !important;
                text-transform: uppercase !important;
                overflow: visible !important;
                text-overflow: clip !important;
            }
            .utd-custom-date .col-sm-8,
            .utd-custom-date .col-sm-2 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding: 0 !important;
                overflow: visible !important;
            }
            .utd-custom-date .input-group.date {
                width: 100% !important;
                border-radius: 8px !important;
                overflow: visible !important;
                border: 1px solid #ddd !important;
            }
            .utd-custom-date .input-group.date input {
                border: none !important;
                height: 36px !important;
                padding: 8px 10px !important;
                font-size: 13px !important;
            }
            .utd-custom-date .input-group-addon {
                border: none !important;
                color: #777 !important;
                padding: 0 8px !important;
            }
            .bootstrap-datetimepicker-widget {
                z-index: 999999999999999 !important;
                max-width: 300px !important;
                border-radius: 12px !important;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2) !important;
                border: 1px solid rgba(0,0,0,0.15) !important;
                padding: 10px !important;
                display: block !important;
                background: #fff !important;

            }
           .dark-mode .bootstrap-datetimepicker-widget {
                z-index: 999999999999999 !important;
                max-width: 300px !important;
                border-radius: 12px !important;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2) !important;
                border: 1px solid rgba(0,0,0,0.15) !important;
                padding: 10px !important;
                display: block !important;
                background: #000000 !important;

            }
            /* Force parent containers to show the calendar */
            .filter-container, .filter-container .row, .filter-container .box-body, .box, .box-body {
                overflow: visible !important;
            }
            .utd-custom-date {
                z-index: 100 !important;
            }
            .utd-custom-date.active-date-container {
                z-index: 9999 !important;
            }
        ");



        $grid->header(function () {
            $query = CoinLog::query()/*->whereNotIn('method', ['huawei_pay', 'google_pay', 'apple_pay'])*/;

            $query->when(!request('from_date'), function ($query) {
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                $query->whereBetween('created_at', [$start, $end]);
            });

            $query->when(
                request('user.uuid'),
                fn($q, $uuid) =>
                $q->whereHas('user', fn($u) => $u->where('uuid', $uuid))
            );

            $query->when(
                request('trx'),
                fn($q, $trx) =>
                $q->where('trx', $trx)
            );

            $query->when(
                request('method') !== null && request('method') !== '',
                fn($q) =>
                $q->where('method', request('method'))
            );

            $query->when(
                request('from_date'),
                fn($q, $from) =>
                $q->whereDate('created_at', '>=', Carbon::parse(convertArabicToEnglishNumbers($from))->startOfDay())
            );



            $query->when(
                request('to_date'),
                fn($q, $to) =>
                $q->whereDate('created_at', '<=', Carbon::parse(convertArabicToEnglishNumbers($to))->endOfDay())
            );

            // $query->when(request('status') !== null && request('status') !== '', fn($q) =>
            //     $q->where('status', request('status'))
            // );

            $total = $query->where('status', 1)->sum('paid_usd');
            return view('admin.grid.common.report.charge-summary', [
                'total' => $total,
            ])->render();
        });

        $grid->column('id', __('transaction id'));

        $grid->column('user_id', __('charger'))->display(function () {
            $user = $this->user;
            if (!$user) {
                return "<div style='display: flex; align-items: center; gap: 10px;'><span style='cursor: pointer;'>Unknown</span></div>";
            }

            $name = $user->name ?? '';
            $uid = $user->uuid ?? 0;
            $path = $user->profile->avatar ?? null;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            if (!isImageExists($url)) {
                $url = $defaultImage;
            }

            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $editUrl = $this->user ? url("admin/users/{$this->user->id}") : '';
            return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                    <a href='{$editUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <strong style='text-decoration: underline; cursor: pointer;'>$name</strong><br>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                    </div>
                </a>
                </div>
            ";
        });

        $grid->column('coin.usd', __('dollar'))->display(function ($coin) {
            $icon = asset('images/dollar.jpg');
            $coin = $coin ?? $this->paid_usd;
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . $coin . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>
                </div>
            ";
        });

        $grid->column('obtained_coins', __('Amount'))->display(function ($coin) {
            $icon = asset('images/coin.jpg');
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>
                </div>
            ";
        });

        $grid->column('trx', __('trx'));
        // $grid->column('coin.payment_gateway_id', __('type'))->display(function ($value) {
        //     $paymentCoin = PaymentCoin::find($value);
        //     return $paymentCoin ? __($paymentCoin->title) : '';
        // });
        $grid->column('method', __('type'));

        $grid->column('status', __('Status'))->display(function () {
            return match ($this->status) {
                1 => '<span style="display:inline-block; padding:5px 10px; font-size:12px; font-weight:bold; border-radius:4px; background-color:#28a745; color:white;">Success</span>',
                0 => '<span style="display:inline-block; padding:5px 10px; font-size:12px; font-weight:bold; border-radius:4px; background-color:#dc3545; color:white;">Failed</span>',
                default => ''
            };
        });

        $grid->column('created_at', __('shipping date'));
        $this->extendGrid($grid);
        $grid->tools(function (Grid\Tools $tools) {
            $uuid = request('uuid') ?? (request('user')['uuid'] ?? null);
            $query = http_build_query([
                'from_date' => request('from_date') ? convertArabicToEnglishNumbers(request('from_date')) : '',
                'to_date' => request('to_date') ? convertArabicToEnglishNumbers(request('to_date')) : '',
                'trx' => request('trx'),
                'status' => request('status'),
                'method' => request('method'),
                'uuid' => $uuid,
            ]);

            $tools->append('<a href="' . url('/admin/exchange-coin-history') . '?' . $query . '" target="_blank" class="btn btn-sm btn-success">
                <i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');
        });

        return $grid;
    }


    protected function in_app_purchas()
    {

        $grid = new Grid(new CoinLog());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();

        $grid->model()->when(request('from_date') && request('to_date'), function ($query,) {

            $start = Carbon::parse(convertArabicToEnglishNumbers(request('from_date')))->startOfDay();
            $end = Carbon::parse(convertArabicToEnglishNumbers(request('to_date')))->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        })->when(!request('from_date'), function ($query,) {

            $start = now()->startOfMonth();
            $end = $end = now()->endOfMonth();
            $query->whereBetween('created_at', [$start, $end]);
        })->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->orderByDesc('created_at')->whereIn('method', ['huawei_pay', 'google_pay', 'apple_pay']);

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user.uuid', __('charger'));
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($this->input !== '' && $this->input !== null) {
                        $query->where('method', $this->input);
                    }
                }, __('Select type'), 'name_for_url_shortcut')->select([
                    '' => __('All'),
                    'huawei_pay' => __('huawei pay'),
                    'google_pay' => __('Google Pay'),
                    'apple_pay' => __('Apple Pay'),
                ]);
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    $from = request('from_date')
                        ? Carbon::parse(convertArabicToEnglishNumbers(request('from_date')))
                        : now()->startOfMonth();
                    $query->where(
                        'created_at',
                        '>=',
                        $from->startOfMonth()
                    );
                }, __('From Date'), 'from_date')
                    ->date()
                    ->default(now()->startOfMonth()->toDateString());
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->where(function ($query) {
                    if ($to = request('to_date')) {
                        $end = Carbon::parse(convertArabicToEnglishNumbers($to))->endOfDay();
                        $query->whereDate('created_at', '<=', $end);
                    }
                }, __('To Date'), 'to_date')->date();
            });
        });

        // Fix select type dropdown to match same width as other inputs on mobile
        Admin::style("
            @media (max-width: 767px) {
                .select2-container {
                    width: 100% !important;
                    max-width: 60% !important;
                }
            }
        ");

        $grid->quickSearch();
        $grid->column('id', __('id'));
        $grid->column('user_id', __('charger'))->display(function ($recever) {
            if (!$this->user) {
                return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                            <span style=' cursor: pointer;'>Unknown </span>

                </div>
            ";
            }
            $name = $this->user->name ?? '';
            $uid = @$this->user->uuid ?? 0;
            $path = @$this->user?->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $editUrl = $this->user ? url("admin/users/{$this->user->id}") : '';
            return "
             <div style='display: flex; align-items: center; gap: 10px;'>

                 <a href='{$editUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <strong style='text-decoration: underline; cursor: pointer;'>$name</strong><br>
                        <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                    </div>
                </a>
             </div>
         ";
        });

        $grid->column('obtained_coins', __('amount'))->display(function ($coin) {
            $icon = asset('images/coin.jpg'); // تأكد من وجود الصورة في هذا المسار
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });
        $grid->column('trx', __('trx'));
        $grid->column('status', __('Status'))->display(function () {
            if ($this->status == 1) {
                return '<span style="display:inline-block; padding:5px 10px; font-size:12px; font-weight:bold; border-radius:4px; background-color:#28a745; color:white;">Success</span>';
            } elseif ($this->status == 0) {
                return '<span style="display:inline-block; padding:5px 10px; font-size:12px; font-weight:bold; border-radius:4px; background-color:#dc3545; color:white;">Failed</span>';
            }
        }); // Allows rendering raw HTML

        $grid->column('method', __('type'))->display(function ($value) {
            $options = [
                'huawei_pay' => __('huawei pay'),
                'google_pay' => __('google pay'),
                'apple_pay' => __('apple pay'),
            ];

            return $options[$value] ?? $value;
        });
        $grid->column('created_at', __('shipping date'));
        // $grid->column('action', __('action'))->display (function (){
        //     return '<a href="?name=in-app-purchas&id='.@$this->id.'" class="btn btn-xs btn-danger">'.__("Return").'</a>';
        // });
        $grid->column('return', __('Return'))->display(function () {
            return (new \App\Admin\Actions\ReturnDiAction($this->id))->render();
        });
        $this->extendGrid($grid);
        return $grid;
    }

    protected function exchange()
    {
        $grid = new Grid(new ExchangeLog());
        $countryID = Common::filterCountryIds();

        $grid->disableRowSelector();

        $grid->model()->with([
            'user',
            'user.profile',
            'user.packs' => function ($q) {
                $q->whereIn('type', [25])
                    ->where('is_used', true)
                    ->with('ware:id,value');
            },
        ])
            ->when(request('from_date') && request('to_date'), function ($query,) {

                $start = Carbon::parse(convertArabicToEnglishNumbers(request('from_date')))->startOfDay();
                $end = Carbon::parse(convertArabicToEnglishNumbers(request('to_date')))->endOfDay();
                $query->whereBetween('created_at', [$start, $end]);
            })->when(!request('from_date'), function ($query,) {

                $start = now()->startOfMonth();
                $end = $end = now()->endOfMonth();
                $query->whereBetween('created_at', [$start, $end]);
            })->when($countryID, fn($q) => $q->whereHas('user', fn($q) => $q->whereIn('country_id', $countryID)))
            ->orderByDesc('created_at')->where('status', 1);
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('user.uuid', __('charger'));
            });

            $filter->column(1 / 6, function ($filter) {
                $filter->where(function ($query) {
                    $from = request('from_date')
                        ? Carbon::parse(convertArabicToEnglishNumbers(request('from_date')))
                        : now()->startOfMonth();
                    $query->where(
                        'created_at',
                        '>=',
                        $from->startOfMonth()
                    );
                }, __('From Date'), 'from_date')
                    ->date()
                    ->default(now()->startOfMonth()->toDateString());

                $filter->where(function ($query) {
                    if ($to = request('to_date')) {
                        $end = Carbon::parse(convertArabicToEnglishNumbers($to))->endOfDay();
                        $query->whereDate('created_at', '<=', $end);
                    }
                }, __('To Date'), 'to_date')->date();
            });
        });

        Admin::script("
            function fixInAppDateFilters() {
                $('.utd-custom-date').each(function() {
                    $(this).find('.input-group').css('width', '100%');
                    $(this).find('input').css('width', '100%');
                });

                // Stacking fix: elevate the active container
                $('.utd-custom-date input').on('focus click', function() {
                    $('.utd-custom-date').removeClass('active-date-container');
                    $(this).closest('.utd-custom-date').addClass('active-date-container');
                });
            }
            $('[name=from_date], [name=to_date]').closest('.form-group').addClass('utd-custom-date');
            fixInAppDateFilters();
            $(document).on('pjax:complete', fixInAppDateFilters);
        ");

        Admin::style("
            .utd-custom-date {
                display: block !important;
                margin-bottom: 20px !important;
                background: rgba(255, 255, 255, 0.05) !important;
                backdrop-filter: blur(5px) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                border-radius: 12px !important;
                padding: 15px !important;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
                transition: all 0.3s ease !important;
                position: relative !important;
                overflow: visible !important;
                width: 100% !important;
            }
            .utd-custom-date:hover {
                background: rgba(255, 255, 255, 0.08) !important;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08) !important;
                transform: translateY(-2px) !important;
            }
            .utd-custom-date label {
                display: block !important;
                width: 100% !important;
                text-align: left !important;
                padding: 0 0 8px 0 !important;
                margin: 0 !important;
                white-space: nowrap !important;
                float: none !important;
                font-weight: 600 !important;
                color: #555 !important;
                font-size: 11px !important;
                letter-spacing: 0.2px !important;
                text-transform: uppercase !important;
                overflow: visible !important;
                text-overflow: clip !important;
            }
            .utd-custom-date .col-sm-8,
            .utd-custom-date .col-sm-2 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding: 0 !important;
                overflow: visible !important;
            }
            .utd-custom-date .input-group.date {
                width: 100% !important;
                border-radius: 8px !important;
                overflow: visible !important;
                border: 1px solid #ddd !important;
                background: #fff !important;
            }
            .utd-custom-date .input-group.date input {
                border: none !important;
                height: 36px !important;
                padding: 8px 10px !important;
                font-size: 13px !important;
            }
            .utd-custom-date .input-group-addon {
                background: #f8f9fa !important;
                border: none !important;
                color: #777 !important;
                padding: 0 8px !important;
            }
            .bootstrap-datetimepicker-widget {
                z-index: 9999999999 !important;
                max-width: 300px !important;
                border-radius: 12px !important;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2) !important;
                border: 1px solid rgba(0,0,0,0.15) !important;
                padding: 10px !important;
                background: #fff !important;
                display: block !important;
            }

            /* Force parent containers to show the calendar */
            .filter-container, .filter-container .row, .filter-container .box-body, .box, .box-body {
                overflow: visible !important;
            }
            .utd-custom-date {
                z-index: 100 !important;
            }
            .utd-custom-date.active-date-container {
                z-index: 9999 !important;
            }
        ");

        $grid->quickSearch();
        $grid->column('id', __('id'));
        $grid->column('user_id', __('charger'))->display(function ($recever) {
            if (!$this->user) {
                return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                            <span style=' cursor: pointer;'>Unknown </span>

                </div>
            ";
            }
            $name = $this->user->name ?? '';
            $uid = @$this->user->uuid ?? 0;
            $path = @$this->user?->profile?->avatar;
            $defaultImage = asset("images/businessman-icon.jpg");
            $url = getImagePath($path) ?? $defaultImage;

            // Check if the image exists
            if (!isImageExists($url)) {
                $url = $defaultImage;
            }
            $image = handleShowImageWithTypes($this->id, $url, 40, 40);
            $showUrl = $this->user->id ? url("admin/users/{$this->user->id}") :  '';
            return "
             <div style='display: flex; align-items: center; gap: 10px;'>
                 $image
                 <div>
                     <strong>$name</strong><br>
                     <span style='color: #aaa; font-size: smaller;'>UID: $uid</span>
                 </div>

                  $image
                    <div>
                        <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                            <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uid</span>
                    </div>
             </div>
         ";
        });
        $grid->column('diamonds', __('diamonds'))->display(function ($usd) {

            $image = asset('images/diamond.jpg'); // تأكد من أن الصورة موجودة

            return "<div style='display: flex; align-items: center; gap: 5px;'>
                        <span>{$usd}</span>
                        <img src='{$image}' alt='USD' width='20' height='20'>
                    </div>";
        });

        $grid->column('value', __('amount'))->display(function ($coin) {
            $icon = asset('images/coin.jpg'); // تأكد من وجود الصورة في هذا المسار
            return "
                <div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($coin) . "</span>
                    <img src='{$icon}' alt='Coin' width='20' height='20'>

                </div>
            ";
        });

        $grid->column('status', __('Status'))->display(function () {
            if ($this->status == 1) {
                return '<span style="display:inline-block; padding:5px 10px; font-size:12px; font-weight:bold; border-radius:4px; background-color:#28a745; color:white;">Success</span>';
            } elseif ($this->status == 0) {
                return '<span style="display:inline-block; padding:5px 10px; font-size:12px; font-weight:bold; border-radius:4px; background-color:#dc3545; color:white;">Failed</span>';
            }
        });
        $grid->column('created_at', __('shipping date'));
        $grid->tools(function (Grid\Tools $tools) {
            $uuid = request('uuid') ?? (request('user')['uuid'] ?? null);
            $query = http_build_query([
                'from_date' => request('from_date') ? convertArabicToEnglishNumbers(request('from_date')) : '',
                'to_date' => request('from_date') ? convertArabicToEnglishNumbers(request('to_date')) : '',
                'uuid' => $uuid,
            ]);

            $tools->append('<a href="' . url('/admin/exchange-diamond-history') . '?' . $query . '" target="_blank" class="btn btn-sm btn-success">
            <i class="fa fa-download"></i>' . __('admin.exportExcel') . '</a>');
        });

        $this->extendGrid($grid);
        return $grid;
    }

    private function tabsComponent()
    {
        return view('admin.grid.common.report.charge')->render();
    }


    public function showChargeReports(Content $content, $agency_id)
    {
        if (!request()->has('scope')) {
            return redirect()->to(url()->current() . '?scope=dash');
        }

        return $content
            ->title(__('Charge Reports'))
            ->row(function ($row) use ($agency_id) {
                $row->column(12, $this->gridTabs($agency_id)); // <-- Tab buttons
            })
            ->row(function ($row) use ($agency_id) {
                $row->column(12, $this->customGrid($agency_id)); // <-- Main grid
            });
    }

    protected function gridTabs($agency_id)
    {
        $scope = request('scope', 'dash');

        $html = '
    <style>
        .tab-buttons {
            margin-bottom: 15px;
        }
        .tab-buttons .tab-button {
            color: black !important;
            margin-right: 10px;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f7f7f7;
        }
        .tab-buttons .tab-button.active {
            background-color: #007bff;
            color: white !important;
            border-color: #007bff;
        }
    </style>
    <div class="tab-buttons">
        <a href="?scope=dash" class="tab-button btn-dash ' . ($scope === 'dash' ? 'active' : '') . '">' . __('Charged by dash') . '</a>
        <a href="?scope=not_dash" class="tab-button btn-agency ' . ($scope === 'not_dash' ? 'active' : '') . '">' . __('Charged by app') . '</a>
    </div>';

        return new \Encore\Admin\Widgets\Box(__(), $html);
    }


    protected function customGrid($agency_id)
    {
        $grid = new Grid(new Charge());
        $grid->disableRowSelector();
        $grid->model()->where('user_id', $agency_id)->where('user_type', 'agency');

        // Apply scope based on query parameter
        $scope = request('scope');
        if ($scope === 'dash') {
            $grid->model()->where('charger_type', 'dash');
        } else {
            $grid->model()->where('charger_type', '!=', 'dash');
        }

        // Add filters
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->where(function ($query) {
                $date = UserCommon::arabicToEnglishNumbers($this->input);
                $query->whereDate('created_at', '>=', $date);
            }, __('from_date'), 'from_date')->date();

            $filter->where(function ($query) {
                $date = UserCommon::arabicToEnglishNumbers($this->input);
                $query->whereDate('created_at', '<=', $date);
            }, __('to_date'), 'to_date')->date();
        });

        // Define columns
        $grid->column('id', __('ID'));
        if ($scope === 'dash') {
            $grid->column('admin.name', __('created by'))->display(function () {
                $name = $this->admin->name ?? '';
                $path = $this->admin->avatar ?? null;
                $id = $this->admin->id ?? 0;
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $image = handleShowImageWithTypes($this->id, $url, 40, 40);

                $showUrl = '#';
                if ($this->admin && $this->admin->id) {
                    $showUrl = url("admin/auth/users/{$this->admin->id}");
                }

                return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                        $image
                        <div style='display: flex; flex-direction: column;'>
                            <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                            <span style='font-size: 12px; color: #666;'>ID: $id</span>
                        </div>
                    </a>
                </div>
            ";
            });
        }

        if ($scope !== 'dash') {

            $grid->column('charger_id', __("charger"))->display(function () {

                $sender = Common::getChargerInfo($this);
                if (empty($sender['name']) && empty($sender['uuid'])) {
                    return "
                <div style='display: flex; align-items: center; gap: 10px;'>

                            <span style=' cursor: pointer;'>Unknown </span>

                </div>
            ";
                }

                $name = $sender['name'];
                $uuid = $sender['uuid'];
                $path = $sender['image'];
                $showUrl = $sender['url'];
                $defaultImage = asset("images/businessman-icon.jpg");
                $url = getImagePath($path) ?? $defaultImage;

                // Check if the image exists
                if (!isImageExists($url)) {
                    $url = $defaultImage;
                }

                $imageStyle = $this->charger_type == 'agency'
                    ? 'width: 40px; height: 40px; object-fit: cover; border-radius: 0;'     // rectangle
                    : 'width: 40px; height: 40px; object-fit: cover; border-radius: 50%;';
                $image = "<img src='{$url}' alt='User Image' style='{$imageStyle}'>";

                return "
                <div style='display: flex; align-items: center; gap: 10px;'>
                    $image
                    <div>
                        <a href='{$showUrl}' style='text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;'>
                            <span style='text-decoration: underline; cursor: pointer;'>$name</span>
                        </a>
                        <span style='color: #aaa; font-size: smaller;'>UUID: $uuid</span>
                    </div>
                </div>
            ";
            });
        }


        $grid->column('amount_and_usd', __('coins & USD'))->display(function () {
            $coin = number_format($this->amount); // Assuming 'amount' is the coin value
            $usd = $this->usd;

            $coinIcon = asset('images/coin.jpg');
            $usdIcon = asset('images/dollar.jpg');

            return "
                    <div style='display: flex; flex-direction: column; gap: 5px;'>
                        <div style='display: flex; align-items: center; gap: 5px;'>
                            <span>{$coin}</span>
                            <img src='{$coinIcon}' alt='Coin' width='20' height='20'>
                        </div>
                        <div style='display: flex; align-items: center; gap: 5px;'>
                            <span>{$usd}</span>
                            <img src='{$usdIcon}' alt='USD' width='20' height='20'>
                        </div>
                    </div>
                ";
        });
        if ($scope == 'dash') {
            $grid->column('balance_before', __("amount before"))->display(function ($coin) {
                $balance_after = $this->balance_before;
                $icon = asset('images/coin.png'); // أيقونة نزول إذا كان الرصيد بعد أقل من قبل

                return "<div style='display: flex; align-items: center; gap: 5px;'>
                   <span>
                   " . number_format($balance_after) . "</span>
                   <img src='{$icon}' alt='USD' width='20' height='20'>
                   </div>";
            });

            $grid->column('balance_after', __("amount after"))->display(function () {

                $balance_after = $this->amount + $this->balance_before;
                $icon = asset('images/arrows.png'); // أيقونة صعود أو نزول حسب المبلغ

                return "<div style='display: flex; align-items: center; gap: 5px;'>
            <span>
            " . number_format($balance_after) . "</span>
            <img src='{$icon}' alt='USD' width='20' height='20'>
            </div>";
            });

            $grid->column('custom_button2', __('reason'))->modal(__('reason'), function ($model) {
                $reason = \App\Models\ChargeInvoice::where('charge_id', $this->id)->first();

                if (!$reason) {
                    return new \Encore\Admin\Widgets\Table(
                        [__('Field Name'), __('Value')],
                        [[__('No reasons available'), '-']]
                    );
                }

                $invoicePath = $reason->invoice ?? '';
                $imgUrl = $invoicePath ? getDriverUrl() . '/' . $invoicePath : '';
                $imgTag = $imgUrl
                    ? "<img src='" . e($imgUrl) . "' style='width:80px; height:80px;' class='img img-thumbnail' />"
                    : '-';

                $reasonText = app()->getLocale() === 'en'
                    ? ($reason->reason_en ?? $reason->reason_ar)
                    : ($reason->reason_ar ?? $reason->reason_en);

                $reasonDiv = "<div style='
        max-height: 150px;
        overflow: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
        word-break: break-word;
        padding: 8px;
        border: 1px solid #ddd;
        background-color: #f9f9f9;
        font-size: 14px;
        line-height: 1.5;
    '>" . e($reasonText) . "</div>";

                $results = [
                    __('Reason') => $reasonDiv,
                    __('Invoice') => $imgTag,
                ];

                return new \Encore\Admin\Widgets\Table(
                    [__('Field Name'), __('Value')],
                    collect($results)->map(fn($v, $k) => [$k, $v])->values()->all()
                );
            });
        }
        if ($scope === 'not_dash') {
            // dd(123);
            $grid->column('amount_type', __('status'))->display(function () use ($agency_id) {
                return $this->user_id == $agency_id ? __('increment') : __('decrement');
            });
        } else {
            $grid->column('amount_type', __('status'))->display(function () {
                return $this->amount < 0 ? __('decrement') : __('increment');
            });
        }
        $grid->column('created_at', __('charge date'));
        $grid->tools(function (Grid\Tools $tools) {
            $url = '/admin/charges';
            $button = '<a href="' . $url . '" class="btn btn-sm btn-success"><i class="fa fa-go"></i>&nbsp;&nbsp;' . __("back") . '</a>';
            $tools->append($button);
        });
        // Disable unnecessary buttons
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableActions();

        return $grid;
    }
}
