<?php

namespace App\Admin\Controllers\AgencyControllers;

use App\Admin\Controllers\MainController;
use App\Admin\Services\AgencyService;
use App\Admin\Services\UserService;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use App\Models\GiftLog;
use Carbon\Carbon;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;

class HostDiamondController extends MainController
{
    use HasResourceActions;

    public $permission_name = 'host-diamond';

    public function index(Content $content)
    {
        $this->arabicToEnglishDates();

        return parent::index($content
            ->title(trans('Host Diamond'))
            ->body($this->grid()));
    }

    public function arabicToEnglishDates()
    {
        if (request()->has('from_date')) {
            request()->merge([
                'from_date' => UserCommon::arabicToEnglishNumbers(request('from_date')),
            ]);
        }
        if (request()->has('to_date')) {
            request()->merge([
                'to_date' => UserCommon::arabicToEnglishNumbers(request('to_date')),
            ]);
        }
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new GiftLog());
        $countryID = Common::filterCountryIds();

        $grid->model()
            ->when($countryID, fn($q) =>
            $q->where(function ($q) use ($countryID) {
                $q->whereHas('receiver', fn($q) => $q->whereIn('country_id', $countryID))
                    ->orWhereHas('agency', fn($q) => $q->whereIn('country_id', $countryID));
            }))
            ->selectRaw('receiver_id, agency_id, SUM(giftPrice) as total_gift_price')
            ->with([
                'receiver:id,uuid,original_uuid,name,country_id',
                'receiver.profile:id,user_id,avatar',
                'receiver.country:id,name,e_name,flag',
                'agency:id,name,img'
            ])
            ->where('agency_id', '!=', 0)
            ->groupBy('receiver_id', 'agency_id')
            ->when(!request('from_date'), fn($q) => $q->where('created_at', '>=', now()->startOfMonth()))
            ->when(!request('to_date'), fn($q) => $q->where('created_at', '<=', now()->endOfMonth()))
            ->when(request('total_gift_price'), fn($q) => $q->havingRaw('total_gift_price >= ?', [(int) request('total_gift_price')]))
            ->orderByDesc('total_gift_price');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();
            $filter->column(1 / 2, function (Grid\Filter $filter) {
                $filter->equal('receiver.uuid', __('uuid'));
                $filter->where(function ($query) {
                    $tz = getTimezone();

                    $input = $this->input ?? now()->startOfMonth();
                    $date = UserCommon::arabicToEnglishNumbers($input);

                    $utcDate = Carbon::parse($date, $tz)->timezone('UTC')->startOfDay();

                    $query->where('created_at', '>=', $utcDate);
                }, __('from_date'), 'from_date')
                    ->date()
                    ->default(request('from_date'));

                $filter->where(function ($query) {}, __('Greater than diamond'), 'total_gift_price')->integer();
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('agency.id', __('agency id'));
                $filter->where(function ($query) {
                    $tz = getTimezone();
                    $input = $this->input ?? now()->endOfMonth();
                    $date = UserCommon::arabicToEnglishNumbers($input);
                    $utcDateOnly = Carbon::parse($date, $tz)->timezone('UTC')->toDateString();
                    $query->whereDate('created_at', '<=', $utcDateOnly);
                }, __('to_date'), 'to_date')
                    ->date()
                    ->default(request('to_date'));
            });
        });

      
        // ── User Column (Modern Card) ──
        $grid->column('receiver', __('user'))->display(function () {
            if (request()->filled('_export_')) {
                return $this?->receiver?->name ?: __('No user');
            }
            $user = @$this->receiver ?? '';

            /** @var UserService $service */
            $service = app(UserService::class);

            return $service->adminUserCard($user, withoutLevels: true);
        });

        // ── Agency Column ──
        $grid->column('agency_id', __('agency'))->display(function () {
            if (request()->filled('_export_')) {
                return $this?->agency?->name ?: __('No agency');
            }
            $agency = $this->agency;
            /** @var AgencyService $agencyService */
            $agencyService = app(AgencyService::class);

            return $agencyService->adminAgencyData($agency);
        });

        // ── Total Diamond Column ──
        $grid->column('total_gift_price', __('Total Diamond received'))->display(function ($val) {
            if (request()->filled('_export_')) {
                return "\t" . number_format($val);
            }
            $formatted = number_format($val);
            $icon = '💎';

            // Dynamic size class based on value
            $sizeClass = 'hd-diamond-sm';
            if ($val >= 1000000) {
                $sizeClass = 'hd-diamond-xl';
            } elseif ($val >= 100000) {
                $sizeClass = 'hd-diamond-lg';
            } elseif ($val >= 10000) {
                $sizeClass = 'hd-diamond-md';
            }

            return "<div class='hd-diamond-cell {$sizeClass}'>
                        <span class='hd-diamond-icon'>{$icon}</span>
                        <span class='hd-diamond-value'>{$formatted}</span>
                    </div>";
        })->style('width:220px;');

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disableRowSelector();

        // ── Inject Styles & Scripts ──
        Admin::style(UserService::adminUserCardStyles() . $this->gridStyles());
        Admin::script($this->gridScripts());

        return $grid;
    }

    /**
     * Modern grid CSS styles for Host Diamond page
     */
    protected function gridStyles(): string
    {
        return '
            /* ═══════════════════════════════════════════
               HOST DIAMOND GRID — Clean Modern UI
               Prefix: hd- (host-diamond)
               ═══════════════════════════════════════════ */

            /* ── Page Title Enhancement ── */
            .content-header h1 {
                font-weight: 800;
                letter-spacing: -0.5px;
            }

            /* ── Box Container ── */
            .box {
                border: none !important;
                border-radius: 16px !important;
                box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important;
                overflow: hidden;
            }
            .box-header {
                border-bottom: 1px solid #f0f2f8 !important;
                padding: 16px 20px !important;
            }

            /* ── Filter Area ── */
            .box.grid-filter {
                border-radius: 16px !important;
                border: 1px solid #e8ecf3 !important;
                background: linear-gradient(135deg, #fafbff 0%, #f8f9fc 100%) !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
                margin-bottom: 20px !important;
            }
            .box.grid-filter .box-header {
                background: transparent !important;
                border-bottom: 1px solid #eef0f6 !important;
            }
            .box.grid-filter .box-header .btn-link {
                font-weight: 700 !important;
                font-size: 14px !important;
                letter-spacing: 0.3px;
            }
            .box.grid-filter .box-body {
                padding: 20px 24px !important;
            }
            .box.grid-filter .form-group label {
                font-weight: 600 !important;
                font-size: 12px !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                color: #64748b !important;
                margin-bottom: 6px !important;
            }
            .box.grid-filter .form-control {
                border-radius: 10px !important;
                border: 2px solid #e2e8f0 !important;
                padding: 8px 14px !important;
                font-size: 13px !important;
                transition: all 0.3s ease !important;
                background: #fff !important;
            }
            .box.grid-filter .form-control:focus {
                border-color: #667eea !important;
                box-shadow: 0 0 0 3px rgba(102,126,234,0.12) !important;
            }
            .box.grid-filter .btn-primary {
                border-radius: 10px !important;
                padding: 8px 24px !important;
                font-weight: 700 !important;
                font-size: 13px !important;
                letter-spacing: 0.3px !important;
                border: none !important;
                transition: all 0.25s ease !important;
            }
            .box.grid-filter .btn-primary:hover {
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            }
            .box.grid-filter .btn-default {
                border-radius: 10px !important;
                padding: 8px 18px !important;
                font-weight: 600 !important;
                font-size: 13px !important;
                border: 2px solid #e2e8f0 !important;
                transition: all 0.25s ease !important;
            }

            /* ── Table Base ── */
            .grid-table {
                border-collapse: separate !important;
                border-spacing: 0 !important;
            }
            .grid-table > thead > tr > th {
                background: #f8fafc !important;
                color: #475569 !important;
                font-weight: 700 !important;
                font-size: 12px !important;
                text-transform: uppercase !important;
                letter-spacing: 0.8px !important;
                padding: 14px 16px !important;
                border-bottom: 2px solid #e2e8f0 !important;
                white-space: nowrap;
            }
            .grid-table > tbody > tr {
                transition: all 0.2s ease;
            }
            .grid-table > tbody > tr > td {
                padding: 10px 16px !important;
                vertical-align: middle !important;
                border-bottom: 1px solid #f1f5f9 !important;
            }
            .grid-table > tbody > tr:nth-child(even) > td {
                background: #fafbfd;
            }
            .grid-table > tbody > tr:hover > td {
                background: #f0f4ff !important;
            }

            /* ── Rank Badge ── */
            .hd-rank {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 38px;
                padding: 5px 10px;
                background: #f0f2f8;
                color: #64748b;
                font-weight: 700;
                font-size: 13px;
                border-radius: 8px;
                letter-spacing: 0.3px;
            }
            .hd-rank-top {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                color: #92400e;
                font-size: 14px;
                border: 1px solid #fbbf24;
                box-shadow: 0 2px 6px rgba(251,191,36,0.2);
            }

            /* ── Diamond Cell ── */
            .hd-diamond-cell {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 16px;
                border-radius: 12px;
                font-weight: 700;
                transition: all 0.25s ease;
                border: 1px solid transparent;
            }
            .hd-diamond-cell:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            }
            .hd-diamond-icon {
                font-size: 18px;
                line-height: 1;
            }
            .hd-diamond-value {
                font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
                letter-spacing: 0.3px;
            }

            /* Diamond size variants */
            .hd-diamond-sm {
                background: #f8fafc;
                border-color: #e2e8f0;
                color: #475569;
                font-size: 13px;
            }
            .hd-diamond-sm .hd-diamond-icon { font-size: 15px; }

            .hd-diamond-md {
                background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                border-color: #93c5fd;
                color: #1e40af;
                font-size: 14px;
            }

            .hd-diamond-lg {
                background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
                border-color: #a78bfa;
                color: #5b21b6;
                font-size: 15px;
            }
            .hd-diamond-lg .hd-diamond-icon { font-size: 20px; }

            .hd-diamond-xl {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                border-color: #f59e0b;
                color: #92400e;
                font-size: 16px;
                padding: 10px 20px;
            }
            .hd-diamond-xl .hd-diamond-icon { font-size: 22px; }
            .hd-diamond-xl .hd-diamond-value { font-weight: 800; }

            /* ── No Agency Label ── */
            .ug-no-agency {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #94a3b8;
                font-size: 12px;
                font-style: italic;
            }

            /* ── Agency Card Enhancement ── */
            .ug-agency-card {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px 12px;
                border-radius: 12px;
                background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
                border: 1px solid #e8ecf3;
                text-decoration: none;
                color: inherit;
                transition: all 0.25s ease;
            }
            .ug-agency-card:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.07);
                border-color: #667eea;
                text-decoration: none;
                color: inherit;
            }
            .ug-agency-avatar {
                width: 44px;
                height: 44px;
                border-radius: 10px;
                object-fit: cover;
                border: 2px solid #e0e5f0;
                transition: border-color 0.3s;
            }
            .ug-agency-card:hover .ug-agency-avatar {
                border-color: #667eea;
            }
            .ug-agency-name {
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 160px;
            }
            .ug-agency-id {
                font-size: 11px;
                color: #94a3b8;
                font-family: monospace;
                background: #f0f2f8;
                padding: 1px 8px;
                border-radius: 4px;
                display: inline-block;
                margin-top: 2px;
            }

            /* ── Pagination ── */
            .box-footer .pagination > li > a,
            .box-footer .pagination > li > span {
                border-radius: 8px !important;
                margin: 0 2px !important;
                border: 1px solid #e2e8f0 !important;
                color: #475569;
                font-weight: 600;
                transition: all 0.2s;
            }
            .box-footer .pagination > .active > a,
            .box-footer .pagination > .active > span {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                border-color: transparent !important;
                color: #fff !important;
            }
            .box-footer .pagination > li > a:hover {
                background: #f1f5f9 !important;
                border-color: #667eea !important;
                color: #667eea !important;
            }

            /* ── Responsive ── */
            @media (max-width: 992px) {
                .hd-diamond-cell { padding: 6px 10px; font-size: 12px !important; }
                .hd-diamond-icon { font-size: 14px !important; }
                .auc-name { max-width: 100px; }
                .ug-agency-name { max-width: 100px; }
            }
            @media (max-width: 768px) {
                .grid-table > thead > tr > th { font-size: 10px !important; padding: 10px 8px !important; }
                .grid-table > tbody > tr > td { padding: 8px !important; }
                .auc-card { padding: 6px 8px; gap: 8px; }
                .ug-agency-card { padding: 6px 8px; gap: 8px; }
            }
        ';
    }

    /**
     * JavaScript for clipboard and table enhancements
     */
    protected function gridScripts(): string
    {
        return "
            // Copy to clipboard fallback
            if (typeof copyToClipboard === 'undefined') {
                window.copyToClipboard = function(elemId) {
                    var text = document.getElementById(elemId).textContent;
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text).then(function() {
                            toastr.success('" . __('Copied!') . "');
                        });
                    } else {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        toastr.success('" . __('Copied!') . "');
                    }
                };
            }

            // Add table class for styling
            $('.grid-table').closest('.box').addClass('hd-grid-box');
        ";
    }
}
