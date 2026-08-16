<?php

namespace App\Admin\Controllers;

use Carbon\Carbon;
use Encore\Admin\Grid;
use App\Models\GiftLog;
use Modules\RoomBoom\Entities\TotalRoomGift;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GiftLogSummaryController extends MainController
{
    public $permission_name = 'gift-logs';

    /** Request-scoped memo of visitors-per-room, keyed by the active date window. */
    private ?array $visitorsByRoom = null;
    public function index(Content $content)
    {
        $filter = request()->input('filter', 'rooms');

        return parent::index($content
            ->title(__('Gift Summary'))
            ->description($filter === 'monthly_ranking' ? __('Monthly Ranking') : __('Rooms - Agencies - Users without agency'))
            ->row(function ($row) use ($filter) {
                $row->column(12, $this->buildTabs($filter));
                if ($filter === 'monthly_ranking') {
                    $row->column(12, $this->buildRankingSubTabs());
                }
                $row->column(12, $this->grid());
            }));
    }

    protected function grid()
    {
        $grid = new Grid(new GiftLog());
        $filter = request()->input('filter', 'rooms');

        $this->applyModelFilter($grid, $filter);
        $this->addColumns($grid, $filter);

        // Standard filter for all tabs
        $grid->filter(function (Grid\Filter $gridFilter) {
                $gridFilter->expand();

                // Disable default ID filter to reorder it
                $gridFilter->disableIdFilter();

                // From Date (column 1) - start date
                $gridFilter->column(1 / 3, function ($gridFilter) {
                    $gridFilter->where(function ($query) {
                        if ($this->input) {
                            $timezone = getTimezone();
                            $start = Carbon::parse(convertArabicToEnglishNumbers($this->input), $timezone)
                                ->setTimezone('UTC');
                            $query->where('created_at', '>=', $start);
                        }
                    }, __('From Date'), 'from_date')->datetime();
                });

                // To Date (column 2) - end date
                $gridFilter->column(1 / 3, function ($gridFilter) {
                    $gridFilter->where(function ($query) {
                        if ($this->input) {
                            $timezone = getTimezone();
                            $end = Carbon::parse(convertArabicToEnglishNumbers($this->input), $timezone)
                                ->setTimezone('UTC');
                            $query->where('created_at', '<=', $end);
                        }
                    }, __('To Date'), 'to_date')->datetime();
                });

                // Room filter (only for rooms tab) - column 3
                if (request('filter') === 'rooms') {
                    $gridFilter->column(1 / 3, function ($gridFilter) {
                        $gridFilter->equal('room_id', __('room'))
                            ->select()
                            ->ajax(route('admin.filter-rooms'));
                    });
                }

                // ID filter at the end (last column)
                $gridFilter->column(1 / 3, function ($gridFilter) {
                    $gridFilter->equal('id', __('ID'))->placeholder(__('ID'));
                });
            });

        // No custom CSS overrides - use dashboard theme colors (var(--primary-color))

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableExport();
        $grid->disableRowSelector();

        return $grid;
    }

    protected function applyModelFilter(Grid $grid, string $filter): void
    {
        $grid->model()
            ->when($filter === 'rooms', function ($query) {
                $query->select('room_id', DB::raw('SUM(giftPrice) as total'))
                    ->whereNotNull('room_id')
                    ->whereHas('room', function ($q) {})
                    ->with('room')
                    ->groupBy('room_id');
            })
            ->when($filter === 'agencies', function ($query) {
                $query->select('agency_id', DB::raw('SUM(giftPrice) as total'))
                    ->whereNotNull('agency_id')
                    ->whereHas('agency', function ($q) {})
                    ->groupBy('agency_id');
            })
            ->when($filter === 'users_no_agency', function ($query) {
                $query->select('receiver_id', DB::raw('SUM(giftPrice) as total'))
                    ->whereNull('agency_id')
                    ->whereHas('receiver', function ($q) {})
                    ->groupBy('receiver_id');
            })
            ->when($filter === 'monthly_ranking', function ($query) {
                $rankingType = request()->input('ranking_type', 'receiver');

                $column = $rankingType === 'sender' ? 'sender_id' : 'receiver_id';
                $relation = $rankingType === 'sender' ? 'sender' : 'receiver';

                // Default to current month if no date filter is applied
                $hasDateFilter = collect(request()->all())->filter(function ($value, $key) {
                    return !empty($value) && (in_array($key, ['to_date', 'from_date']) || preg_match('/^[a-f0-9]{32}$/', $key));
                })->isNotEmpty();

                if (!$hasDateFilter) {
                    $startOfMonth = Carbon::now()->startOfMonth()->toDateTimeString();
                    $endOfMonth = Carbon::now()->endOfMonth()->toDateTimeString();
                    $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                }

                $sortDir = request()->input('sort_dir', 'desc');
                $query->select($column, DB::raw('SUM(giftPrice) as total'))
                    ->whereHas($relation, function ($q) {})
                    ->groupBy($column)
                    ->orderBy('total', $sortDir === 'asc' ? 'asc' : 'desc');
            });
    }

    protected function addColumns(Grid $grid, string $filter): void
    {
        $grid->column('name', __('Name'))->display(function () use ($filter) {
            return self::renderEntityCard($this, $filter);
        });


        $grid->column('total', __('diamonds'))
            ->display(function () {
                $image = asset('images/diamond.jpg');
                return "<div style='display: flex; align-items: center; gap: 5px;'>
                    <span>" . number_format($this->total) . "</span>
                    <img src='{$image}' alt='💎' width='20' height='20'>
                </div>";
            });

        if ($filter === 'rooms') {
            $controller = $this;
            $grid->column('visitors_count', __('visitors'))
                ->display(function () use ($controller) {
                    return number_format($controller->visitorsForRoom($this->room_id));
                });
        }
    }

    /**
     * Visitors count for a single room, served from a request-scoped map that is
     * built with ONE grouped query (SUM(number_of_visitors) GROUP BY room_id)
     * instead of one aggregate query per grid row. The date window matches the
     * previous per-row logic exactly, so the displayed metric is unchanged.
     */
    public function visitorsForRoom($roomId): int
    {
        if ($this->visitorsByRoom === null) {
            $query = TotalRoomGift::query()
                ->select('room_id', DB::raw('SUM(number_of_visitors) as visitors'))
                ->groupBy('room_id');

            $toDate = request()->input('to_date');
            $fromDate = request()->input('from_date');
            $timezone = getTimezone();

            if ($toDate) {
                $end = Carbon::parse(convertArabicToEnglishNumbers($toDate), $timezone)
                    ->setTimezone('UTC');
                $query->where('created_at', '<=', $end);
            }

            if ($fromDate) {
                $start = Carbon::parse(convertArabicToEnglishNumbers($fromDate), $timezone)
                    ->setTimezone('UTC');
                $query->where('created_at', '>=', $start);
            }

            $this->visitorsByRoom = $query->pluck('visitors', 'room_id')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        return $this->visitorsByRoom[$roomId] ?? 0;
    }

    protected function buildTabs(string $active): string
    {
        $tabs = [
            'rooms'           => __('Rooms'),
            'agencies'        => __('Agencies'),
            'users_no_agency' => __('User without agency'),
            'monthly_ranking' => __('Monthly Ranking'),
        ];

        $html = '<div class="nav-tabs-custom" style="margin-bottom:20px;"><ul class="nav nav-tabs">';
        foreach ($tabs as $key => $label) {
            $isActive = $key === $active ? 'active' : '';
            $url = request()->fullUrlWithQuery(['filter' => $key]);
            $html .= "<li class='{$isActive}'><a href='{$url}' class='tab-link'>{$label}</a></li>";
        }
        $html .= '</ul></div>';

        $html .= <<<JS
            <style>
                .nav-tabs-custom { z-index: 0; position: relative; }
                .tab-content, .filters { position: relative; z-index: 9; }
            </style>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.tab-link').forEach(tab => {
                        tab.addEventListener('click', function(e) {
                            e.preventDefault();
                            window.location.href = tab.getAttribute('href');
                        });
                    });
                });
            </script>
        JS;

        return $html;
    }

    protected function buildRankingSubTabs(): string
    {
        $subTab = request()->input('ranking_type', 'receiver');
        $sortDir = request()->input('sort_dir', 'desc');

        $receiverActive = $subTab === 'receiver'
            ? 'background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;box-shadow:0 4px 12px rgba(99,102,241,0.3);border:none;'
            : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;';
        $senderActive = $subTab === 'sender'
            ? 'background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;box-shadow:0 4px 12px rgba(245,158,11,0.3);border:none;'
            : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;';

        $descActive = $sortDir === 'desc'
            ? 'background:var(--primary-color,#334155);color:#fff;border:none;box-shadow:0 2px 8px rgba(0,0,0,0.15);'
            : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;';
        $ascActive = $sortDir === 'asc'
            ? 'background:var(--primary-color,#334155);color:#fff;border:none;box-shadow:0 2px 8px rgba(0,0,0,0.15);'
            : 'background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;';

        $receiverUrl = request()->fullUrlWithQuery(['filter' => 'monthly_ranking', 'ranking_type' => 'receiver', 'sort_dir' => $sortDir]);
        $senderUrl = request()->fullUrlWithQuery(['filter' => 'monthly_ranking', 'ranking_type' => 'sender', 'sort_dir' => $sortDir]);
        $descUrl = request()->fullUrlWithQuery(['filter' => 'monthly_ranking', 'ranking_type' => $subTab, 'sort_dir' => 'desc']);
        $ascUrl = request()->fullUrlWithQuery(['filter' => 'monthly_ranking', 'ranking_type' => $subTab, 'sort_dir' => 'asc']);

        $receiverLabel = __('Receiver');
        $senderLabel = __('Sender');
        $descLabel = __('Highest First');
        $ascLabel = __('Lowest First');

        return <<<HTML
        <div style="background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:16px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;gap:8px;">
                <a href="{$receiverUrl}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:10px;font-weight:700;font-size:13px;text-decoration:none;transition:all 0.2s;{$receiverActive}">
                    <i class="fas fa-download" style="font-size:12px;"></i> {$receiverLabel}
                </a>
                <a href="{$senderUrl}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:10px;font-weight:700;font-size:13px;text-decoration:none;transition:all 0.2s;{$senderActive}">
                    <i class="fas fa-upload" style="font-size:12px;"></i> {$senderLabel}
                </a>
            </div>
            <div style="display:flex;gap:6px;align-items:center;">
                <span style="font-size:12px;color:#94a3b8;font-weight:600;margin-right:4px;"><i class="fas fa-sort" style="margin-right:3px;"></i> Sort:</span>
                <a href="{$descUrl}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 18px;border-radius:8px;font-weight:600;font-size:12px;text-decoration:none;transition:all 0.2s;{$descActive}">
                    <i class="fas fa-sort-amount-down" style="font-size:11px;"></i> {$descLabel}
                </a>
                <a href="{$ascUrl}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 18px;border-radius:8px;font-weight:600;font-size:12px;text-decoration:none;transition:all 0.2s;{$ascActive}">
                    <i class="fas fa-sort-amount-up" style="font-size:11px;"></i> {$ascLabel}
                </a>
            </div>
        </div>
        HTML;
    }

    protected static function renderEntityCard($row, string $filter): string
    {
        if ($filter === 'agencies' && $agency = $row->agency) {
            return self::entityDisplay($agency->id, $agency->name, $agency->img, 'icon-agency.jpg', route('admin.agency.profile', $agency->id));
        }

        if ($filter === 'rooms' && $room = $row->room) {
            return self::entityDisplay($room->id, $room->room_name, $room->room_cover, 'room.jpg', url("admin/rooms/{$room->id}"));
        }

        if ($filter === 'users_no_agency' && $user = $row->receiver) {
            return self::entityDisplay($user->id, $user->name, $user->profile?->avatar, 'businessman-icon.jpg', url("admin/users/{$user->id}"));
        }

        if ($filter === 'monthly_ranking') {
            $rankingType = request()->input('ranking_type', 'receiver');
            $user = $rankingType === 'sender' ? $row->sender : $row->receiver;
            if ($user) {
                return self::entityDisplay($user->id, $user->name, $user->profile?->avatar, 'businessman-icon.jpg', url("admin/users/{$user->id}"));
            }
        }

        return '';
    }

    protected static function entityDisplay($id, $name, $path, $default, $url): string
    {
        $cacheKey = "entity_image_{$id}";
        $image = Cache::remember($cacheKey, 3600, function () use ($path, $default, $id) {
            $url = getImagePath($path) ?? asset("images/{$default}");
            if (!isImageExists($url)) $url = asset("images/{$default}");
            return handleShowImageWithTypes($id, $url, 40, 40, 0);
        });

        return "<a href='{$url}' style='text-decoration: none; color: inherit;'>
                    <div style='display: flex; align-items: center; gap: 10px;'>
                        {$image}
                        <div style='display: flex; flex-direction: column;'>
                            <span style='text-decoration: underline; cursor: pointer;'>{$name}</span>
                            <span style='font-size: smaller;'>ID: {$id}</span>
                        </div>
                    </div>
                </a>";
    }
}
