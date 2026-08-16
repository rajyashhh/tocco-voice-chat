<?php

namespace App\Services\Admin;

use App\Admin\Services\UserGameService;
use App\Admin\Services\UserService;
use App\Admin\Widgets\CustomInfoBox;
use App\Models\AllGame;
use App\Models\CoinGameUserAggregated;
use App\Models\CoinGameUserAll;
use App\Models\CoinGameUserArchive;
use App\Models\CoinGameUserDailyAggregated;
use App\Models\User;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;


class CoinGameUserService
{
    protected $userService;

    public function __construct(UserGameService $userService)
    {
        $this->userService = $userService;
    }


    /**
     * Register the shared "From / To" datetime range filters on a Laravel-Admin
     * grid filter, bound to the given archive datetime column. The detail, rounds
     * and show grids all reused the same two closures verbatim — this is the single
     * definition. ($column is always a trusted constant from this class, never user
     * input, so embedding it in the where() column name is safe.)
     */
    private function archiveDateRangeFilters(Grid\Filter $filter, string $column, ?float $columnWidth = null): void
    {
        $from = function ($filter) use ($column) {
            $filter->where(function ($query) use ($column) {
                if ($value = $this->input) {
                    $query->where($column, '>=', $value);
                }
            }, __('From'), 'from_date')->datetime();
        };

        $to = function ($filter) use ($column) {
            $filter->where(function ($query) use ($column) {
                if ($value = $this->input) {
                    $query->where($column, '<=', $value);
                }
            }, __('To'), 'to_date')->datetime();
        };

        if ($columnWidth !== null) {
            $filter->column($columnWidth, $from);
            $filter->column($columnWidth, $to);
        } else {
            $from($filter);
            $to($filter);
        }
    }

    public function applyFilters($query,  array $filters)
    {
        if (!empty($filters['user']['uuid'])) {
            $userUuid = $filters['user']['uuid'];
            $query->whereHas('user', function ($q) use ($userUuid) {
                $q->where('uuid', $userUuid);
            });
        }

        if (!empty($filters['game_id'])) {
            $gameId = $filters['game_id'];
            $query->Where('game_id', $gameId);
        }

        if (
            isset($filters['date']['start'], $filters['date']['end']) &&
            $filters['date']['start'] && $filters['date']['end']
        ) {

            $startInput = $filters['date']['start'];
            $endInput   = $filters['date']['end'];

            $start = Carbon::parse($startInput);
            $end   = Carbon::parse($endInput);



            $query->whereBetween('date', [$start, $end]);
        }

        return $query;
    }


    public function calculateTotals($query, $filters): object
    {

        return $query->selectRaw("
            SUM(total_played) as total_played,
            SUM(total_loss) as total_loss,
            SUM(total_win) as total_win,
            SUM(total_loss - total_win) as app_profit
        ")->first();
    }


    public function renderInfoBoxes(Row $row, $totals): void
    {
        $row->column(3, new CustomInfoBox(__('Total Played'), 'gamepad', 'blue',  number_format($totals->total_played ?? 0, 2), '50px'));
        $row->column(3, new CustomInfoBox(__('Total Loss'), 'times-circle', 'red',  number_format($totals->total_loss ?? 0, 2), '50px'));
        $row->column(3, new CustomInfoBox(__('Total Win'), 'trophy', 'orange',  number_format($totals->total_win ?? 0, 2), '50px'));
        $row->column(3, new CustomInfoBox(__('App Profit'), 'dollar', 'green',  number_format($totals->app_profit ?? 0, 2), '50px'));
    }

    /**
     * Calculate totals for archive-based queries.
     */
    public function calculateTotals0($query, $filters): object
    {
        return $query->selectRaw("
            SUM(coins) as total_played,
            SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as total_loss,
            SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_win,
            SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) - SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as app_profit
        ")->first();
    }


    /**
     * Render info boxes for archive-based totals.
     */
    public function renderInfoBoxes0(Row $row, $totals): void
    {
        $row->column(3, new CustomInfoBox(__('Total Played'), 'gamepad', 'blue', number_format($totals->total_played ?? 0, 2), '50px'));
        $row->column(3, new CustomInfoBox(__('Total Loss'), 'times-circle', 'red', number_format($totals->total_loss ?? 0, 2), '50px'));
        $row->column(3, new CustomInfoBox(__('Total Win'), 'trophy', 'orange', number_format($totals->total_win ?? 0, 2), '50px'));
        $row->column(3, new CustomInfoBox(__('App Profit'), 'dollar', 'green', number_format($totals->app_profit ?? 0, 2), '50px'));
    }


    /**
     * Build grid from coin_game_users_archive with datetime filter.
     * Grouped by user and game with game details displayed.
     */
    // public function buildGrid(): Grid
    // {
    //     $grid = new Grid(new CoinGameUserArchive());

    //     $grid->model()
    //         ->select([
    //             'coin_game_users_archive.user_id',
    //             'u.uuid as user_uuid',
    //             'u.name as user_name',
    //             'up.avatar as user_avatar',
    //             DB::raw('SUM(coin_game_users_archive.coins) as total_played'),
    //             DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 0 THEN coin_game_users_archive.coins ELSE 0 END) as total_loss'),
    //             DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 1 THEN coin_game_users_archive.coins ELSE 0 END) as total_win'),
    //             DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 0 THEN coin_game_users_archive.coins ELSE 0 END) - SUM(CASE WHEN coin_game_users_archive.type = 1 THEN coin_game_users_archive.coins ELSE 0 END) as app_profit'),
    //             DB::raw('MIN(coin_game_users_archive.created_at) as first_played'),
    //             DB::raw('MAX(coin_game_users_archive.created_at) as last_played'),
    //         ])
    //         ->from('coin_game_users_archive')
    //         ->leftJoin('users as u', 'u.id', '=', 'coin_game_users_archive.user_id')
    //         ->leftJoin('profiles as up', 'up.user_id', '=', 'u.id')
    //         ->whereNotNull('coin_game_users_archive.game_id')
    //         ->groupBy(
    //             'coin_game_users_archive.user_id',
    //             'u.uuid', 'u.name', 'up.avatar'
    //         )
    //         ->orderByDesc(DB::raw('SUM(coin_game_users_archive.coins)'));


    //     $grid->filter(function (Grid\Filter $filter) {
    //         $filter->expand();
    //         $filter->disableIdFilter();

    //         $filter->where(function ($query) {
    //             if (!empty($this->input)) {
    //                 $query->whereHas('user', function ($q) {
    //                     $q->where('uuid', 'LIKE', "%{$this->input}%");
    //                 });
    //             }
    //         }, 'User UUID', 'user_uuid')->placeholder('UUID');

    //         $filter->where(function ($query) {
    //             if ($value = $this->input) {
    //                 $query->where('coin_game_users_archive.created_at', '>=', $value);
    //             }
    //         }, 'From', 'from_date')->datetime();

    //         $filter->where(function ($query) {
    //             if ($value = $this->input) {
    //                 $query->where('coin_game_users_archive.created_at', '<=', $value);
    //             }
    //         }, 'To', 'to_date')->datetime();
    //     });

    //     $grid->header(function () {
    //         return '<div class="alert alert-warning" style="margin-bottom:15px;">
    //             <i class="fa fa-exclamation-triangle"></i>
    //             <strong>' . __('Note') . ':</strong> ' . __('Today\'s data does not appear in this report. Only archived data is displayed.') . '
    //         </div>';
    //     });

    //     $userService = $this->userService;

    //     $grid->column('user_uuid', __('User'))->display(function () use ($userService) {
    //         return $userService->adminUserAvatar((object)[
    //             'id'     => $this->user_id,
    //             'uuid'   => $this->user_uuid,
    //             'name'   => $this->user_name,
    //             'avatar' => $this->user_avatar,
    //         ], withoutLevels: true);
    //     });

    //     $grid->column('total_loss', __('Total Loss'))->display(function ($v) {
    //         return "<span style='color:red; font-weight:bold;'>" . number_format($v) . "</span>";
    //     })->sortable();

    //     $grid->column('total_win', __('Total Win'))->display(function ($v) {
    //         return "<span style='color:green; font-weight:bold;'>" . number_format($v) . "</span>";
    //     })->sortable();

    //     $grid->column('app_profit', __('App Profit'))->display(function ($v) {
    //         $color = $v >= 0 ? 'green' : 'red';
    //         return "<span style='color:{$color}; font-weight:bold;'>" . number_format($v) . "</span>";
    //     })->sortable();

    //     $grid->column('first_played', __('Start Date'))->display(fn($v) => $v)->sortable();
    //     $grid->column('last_played', __('End Date'))->display(fn($v) => $v)->sortable();

    //     $grid->column('details', __('Details'))->display(function () {
    //         $url = admin_url("coin-game-users/details?user_id={$this->user_id}");
    //         return "<a href='{$url}' class='btn btn-sm btn-primary'>
    //             <i class='fa fa-eye'></i> " . __('Details') . "
    //         </a>";
    //     });

    //     $grid->disableCreateButton();
    //     $grid->disableActions();
    //     $grid->disableExport();

    //     return $grid;
    // }

    public function buildGrid0(): Grid
    {
        $grid = new Grid(new CoinGameUserArchive());

        $grid->model()
            ->select([
                'coin_game_users_archive.user_id',
                'u.id',
                'u.uuid as user_uuid',
                'u.name as user_name',
                'up.avatar as avatar',

                DB::raw('SUM(coin_game_users_archive.coins) as total_played'),
                DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 0 THEN coin_game_users_archive.coins ELSE 0 END) as total_loss'),
                DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 1 THEN coin_game_users_archive.coins ELSE 0 END) as total_win'),
                DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 0 THEN coin_game_users_archive.coins ELSE 0 END)
                    - SUM(CASE WHEN coin_game_users_archive.type = 1 THEN coin_game_users_archive.coins ELSE 0 END) as app_profit'),
                DB::raw('MIN(coin_game_users_archive.created_at) as first_played'),
                DB::raw('MAX(coin_game_users_archive.created_at) as last_played'),
            ])
            ->from('coin_game_users_archive')
            ->leftJoin('users as u', 'u.id', '=', 'coin_game_users_archive.user_id')
            ->leftJoin('profiles as up', 'up.user_id', '=', 'u.id')
            ->whereNotNull('coin_game_users_archive.game_id')
            ->groupBy(
                'coin_game_users_archive.user_id',
                'u.id',
                'u.uuid',
                'u.name',
                'up.avatar'
            )
            ->orderByDesc('total_played'); // 🔥 faster than raw

        // ✅ Filters (optimized - no whereHas)
        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->like('u.uuid', 'User UUID')->placeholder('UUID');

            $filter->where(function ($query) {
                if ($value = $this->input) {
                    $query->where('coin_game_users_archive.created_at', '>=', $value);
                }
            }, 'From', 'from_date')->datetime();

            $filter->where(function ($query) {
                if ($value = $this->input) {
                    $query->where('coin_game_users_archive.created_at', '<=', $value);
                }
            }, 'To', 'to_date')->datetime();
        });

        // ✅ Header
        $grid->header(function () {
            return '<div class="alert alert-warning" style="margin-bottom:15px;">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>' . __('Note') . ':</strong> ' . __('Today\'s data does not appear in this report. Only archived data is displayed.') . '
        </div>';
        });

        $userService = $this->userService;

        // ✅ User column (NO N+1)
        $grid->column('user_uuid', __('User'))->display(function () use ($userService) {
            return $userService->adminUserAvatarII((object)[
                'id'     => $this->id,
                'uuid'   => $this->user_uuid,
                'name'   => $this->user_name,
                'avatar' => $this->avatar,
            ], true); // 🚀 disable levels for speed
        });

        // ✅ Stats columns
        $grid->column('total_loss', __('Total Loss'))->display(
            fn($v) =>
            "<span style='color:red;font-weight:bold;'>" . number_format($v) . "</span>"
        )->sortable();

        $grid->column('total_win', __('Total Win'))->display(
            fn($v) =>
            "<span style='color:green;font-weight:bold;'>" . number_format($v) . "</span>"
        )->sortable();

        $grid->column('app_profit', __('App Profit'))->display(function ($v) {
            $color = $v >= 0 ? 'green' : 'red';
            return "<span style='color:{$color};font-weight:bold;'>" . number_format($v) . "</span>";
        })->sortable();

        $grid->column('first_played', __('Start Date'))->sortable();
        $grid->column('last_played', __('End Date'))->sortable();

        // ✅ Details button
        $grid->column('details', __('Details'))->display(function () {
            $url = admin_url("coin-game-users/details?user_id={$this->user_id}");
            return "<a href='{$url}' class='btn btn-sm btn-primary'>
            <i class='fa fa-eye'></i> " . __('Details') . "
        </a>";
        });

        // ✅ Disable extras for performance
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableExport();

        $grid->paginate(10);
        return $grid;
    }


    /**
     * Apply grid filters.
     */
    public function applyGridFilters0(Grid $grid)
    {
        $grid->filter(function (Grid\Filter $filter) {

            $filter->expand();
            $filter->disableIdFilter();

            $filter->where(function ($query) {
                if (!empty($this->input)) {
                    $query->whereHas('user', function ($q) {
                        $q->where('uuid', 'like', "%{$this->input}%");
                    });
                }
            }, 'User', 'user_uuid')->placeholder('UUID');

            $filter->where(function ($query) {
                if (!empty($this->input)) {
                    $query->where('game_id', $this->input);
                }
            }, 'Game', 'game_id')->placeholder(' ID');

            $filter->between('date', __('Created At'))->datetime([
                'format' => 'YYYY-MM-DD HH:mm:ss',
                'locale' => 'en'
            ]);
        });
    }



    public function buildGrid(): Grid
    {
        $grid = new Grid(new CoinGameUserDailyAggregated());
        $grid->model()
            ->select([
                'coin_game_users_daily_aggregated.user_id',
                'u.uuid as user_uuid',
                'u.name as user_name',
                'up.avatar as user_avatar',
                DB::raw('SUM(coin_game_users_daily_aggregated.total_played) as total_played'),
                DB::raw('SUM(coin_game_users_daily_aggregated.total_loss) as total_loss'),
                DB::raw('SUM(coin_game_users_daily_aggregated.total_win) as total_win'),
                DB::raw('SUM(coin_game_users_daily_aggregated.app_profit) as app_profit'),
            ])
            ->from('coin_game_users_daily_aggregated')
            ->leftJoin('users as u', 'u.id', '=', 'coin_game_users_daily_aggregated.user_id')
            // profiles is 1:1 with users on user_id, so a plain indexed join is
            // correct here. (Previously this used a correlated "up.id = (SELECT id
            // FROM profiles WHERE user_id = u.id LIMIT 1)" subquery that the planner
            // re-evaluated per row and could not index.)
            ->leftJoin('profiles as up', 'up.user_id', '=', 'u.id')
            ->groupBy('coin_game_users_daily_aggregated.user_id', 'u.uuid', 'u.name', 'up.avatar')
            ->orderByDesc(DB::raw('SUM(coin_game_users_daily_aggregated.total_played)'));


        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->where(function ($query) {
                if (!empty($this->input)) {
                    $query->whereHas('user', function ($q) {
                        $q->where('uuid', 'like', "%{$this->input}%");
                    });
                }
            }, 'User UUID', 'user_uuid')->placeholder('UUID');

            $filter->where(function ($query) {
                if (!empty($this->input)) {
                    $query->where('coin_game_users_daily_aggregated.game_id', $this->input);
                }
            }, 'Game', 'game_id');

            $filter->between('date', __('Created At'))
                ->date();
        });

        // Clarifying heading: this page is the per-user GAME coin economy report
        // (wins/losses/profit). It is unrelated to the user-abuse "report_users"
        // page. Display-only — route name and permission are unchanged.
        $grid->header(function () {
            return '<div class="alert alert-info" style="margin-bottom:15px;">'
                . '<i class="fa fa-gamepad"></i> '
                . '<strong>' . __('Games User Reports') . ':</strong> '
                . __('Per-user games coin activity (win / loss / app profit).')
                . '</div>';
        });

        $userService = $this->userService;


        $grid->column('nameUser', __('user'))->display(function () {
            return app(UserService::class)->adminUserCard($this->user);
        });
        Admin::style(UserService::adminUserCardStyles() . gridStyles());


        foreach (['total_loss', 'total_win', 'app_profit'] as $field) {
            $grid->column($field, __(ucwords(str_replace('_', ' ', $field))))
                ->display(fn($v) => number_format($v))->sortable();
        }

        $grid->column('details', __('Details'))->display(function () {
            $url = admin_url("coin-game-users/details?user_id={$this->user_id}");
            return "<a href='{$url}' class='btn btn-sm btn-primary'>
                <i class='fa fa-eye'></i> " . __('Details') . "
            </a>";
        });

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableExport();
        $grid->paginate(10);

        return $grid;
    }



    public function buildGrid_details($user_id): Grid
    {
        $grid = new Grid(new CoinGameUserArchive());

        $grid->model()
            ->select([
                'coin_game_users_archive.user_id',
                'coin_game_users_archive.game_id',
                'u.uuid as user_uuid',
                'u.name as user_name',
                'up.avatar as user_avatar',
                DB::raw('COALESCE(g.name, cg.name) as game_name'),
                DB::raw('COALESCE(g.image, cg.image) as game_image'),
                DB::raw('SUM(coin_game_users_archive.coins) as total_played'),
                DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 0 THEN coin_game_users_archive.coins ELSE 0 END) as total_loss'),
                DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 1 THEN coin_game_users_archive.coins ELSE 0 END) as total_win'),
                DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 0 THEN coin_game_users_archive.coins ELSE 0 END)
                    - SUM(CASE WHEN coin_game_users_archive.type = 1 THEN coin_game_users_archive.coins ELSE 0 END) as app_profit'),
            ])
            ->from('coin_game_users_archive')
            ->leftJoin('users as u', 'u.id', '=', 'coin_game_users_archive.user_id')
            ->leftJoin('profiles as up', 'up.user_id', '=', 'u.id')
            // coin_game_users_archive.game_id is copied verbatim from the live
            // table and may hold EITHER all_games.id (internal games) OR
            // all_games.custom_id (provider games). Canonical resolution:
            // match on the primary id first (g), fall back to custom_id (cg);
            // COALESCE on the selected columns picks the matched row. Both joins
            // are required while the two storage conventions coexist.
            ->leftJoin('all_games as g', 'g.id', '=', 'coin_game_users_archive.game_id')
            ->leftJoin('all_games as cg', 'cg.custom_id', '=', 'coin_game_users_archive.game_id')
            ->where('coin_game_users_archive.user_id', $user_id)
            ->whereNotNull('coin_game_users_archive.game_id')
            ->groupBy(
                'coin_game_users_archive.user_id',
                'coin_game_users_archive.game_id',
                'u.uuid',
                'u.name',
                'up.avatar',
                'g.name',
                'g.image',
                'cg.name',
                'cg.image'
            )
            ->orderByDesc('total_played');

        $grid->filter(function (Grid\Filter $filter) {
            $filter->expand();
            $filter->disableIdFilter();

            $filter->where(function ($query) {
                if (!empty($this->input)) {
                    $query->where('coin_game_users_archive.game_id', $this->input);
                }
            }, 'Game', 'game_id')->placeholder('ID');

            $this->archiveDateRangeFilters($filter, 'coin_game_users_archive.created_at', columnWidth: 1 / 2);
        });

        $userService = $this->userService;

        $grid->column('user_uuid', __('User'))->display(function () use ($userService) {
            return $userService->adminUserAvatarII((object)[
                'id'     => $this->user_id,
                'uuid'   => $this->user_uuid,
                'name'   => $this->user_name,
                'avatar' => $this->user_avatar,
            ], true);
        });

        $grid->column('game_id', __('Game'))->display(function () {
            $defaultImage = asset('images/businessman-icon.jpg');
            $url = getImagePath($this->game_image ?? $defaultImage) ?? $defaultImage;
            if (!isImageExists($url)) $url = $defaultImage;

            $uniqueId = $this->game_id ?? 'game-unknown';
            $imageTag = handleShowImageWithTypes((string) $uniqueId, $url, 50, 50, 0);
            $gameIdHtml = "game-{$this->game_id}";
            $urlLink = admin_url("all-games/{$this->game_id}");
            $name = $this->game_name ?? $this->game_id;

            return <<<HTML
            <a href="{$urlLink}" style="display:flex;align-items:center;gap:10px;padding:10px;text-decoration:none;color:inherit;transition:background-color 0.2s;">
                $imageTag
                <div>
                    <strong style="font-size:16px;">{$name}</strong><br>
                    <span style="font-size:13px;">
                        ID: <span id="{$gameIdHtml}">{$this->game_id}</span>
                        <button onclick="event.preventDefault();event.stopPropagation();copyToClipboard('{$gameIdHtml}')" style="background:none;border:none;cursor:pointer;margin-left:5px;font-size:13px;color:#007bff;" title="Copy ID">📝</button>
                    </span>
                </div>
            </a>
        HTML;
        });

        $grid->column('total_loss', __('Total Loss'))->display(
            fn($v) => "<span style='color:red;font-weight:bold;'>" . number_format($v) . "</span>"
        )->sortable();
        $grid->column('total_win', __('Total Win'))->display(
            fn($v) => "<span style='color:green;font-weight:bold;'>" . number_format($v) . "</span>"
        )->sortable();
        $grid->column('app_profit', __('App Profit'))->display(function ($v) {
            $color = $v >= 0 ? 'green' : 'red';
            return "<span style='color:{$color};font-weight:bold;'>" . number_format($v) . "</span>";
        })->sortable();

        $grid->column('details', __('Details'))->display(function () {
            $url = admin_url("coin-game-users/show?user_id={$this->user_id}&game_id={$this->game_id}");
            return "<a href='{$url}' class='btn btn-sm btn-primary'>
                    <i class='fa fa-eye'></i> " . __('round_details') . "
                </a>";
        });
        $grid->tools(function ($tools) {
            $tools->append('<a href="' . admin_url('coin-game-users-reports') . '" class="btn btn-sm btn-default">
                <i class="fa fa-arrow-left"></i> ' . __('Back') . '</a>');
        });
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableExport();
        $grid->paginate(10);

        return $grid;
    }



    public function buildShowAllGrid($userId, $gameId): Grid
    {
        $grid = new Grid(new CoinGameUserArchive());

        $grid->model()
            ->select([
                'coin_game_users_archive.round_id',
                'coin_game_users_archive.game_id',
                DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 0 THEN coin_game_users_archive.coins ELSE 0 END) as total_loss'),
                DB::raw('SUM(CASE WHEN coin_game_users_archive.type = 1 THEN coin_game_users_archive.coins ELSE 0 END) as total_win'),
                DB::raw('MIN(coin_game_users_archive.created_at) as first_played'),
                DB::raw('MAX(coin_game_users_archive.created_at) as last_played'),
                DB::raw('COALESCE(ag.name, cg.name) as game_name'),
                DB::raw('COALESCE(ag.image, cg.image) as game_image'),
            ])
            // game_id may be all_games.id (ag) or all_games.custom_id (cg); see the
            // canonical-resolution note in buildGrid_details(). Both joins kept.
            ->leftJoin('all_games as ag', 'ag.id', '=', 'coin_game_users_archive.game_id')
            ->leftJoin('all_games as cg', 'cg.custom_id', '=', 'coin_game_users_archive.game_id')
            ->where('coin_game_users_archive.user_id', $userId)
            ->where('coin_game_users_archive.game_id', $gameId)
            ->whereNotNull('coin_game_users_archive.round_id')
            ->groupBy('coin_game_users_archive.round_id', 'coin_game_users_archive.game_id', 'ag.name', 'ag.image', 'cg.name', 'cg.image')
            ->orderByDesc('last_played');

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->expand();

            $filter->column(1 / 3, function ($filter) {
                $filter->where(function ($q) {
                    if (!empty($this->input)) {
                        $q->where('round_id', 'like', "%{$this->input}%");
                    }
                }, __('Round ID'), 'round_id')->placeholder(__('Round ID'));
            });

            $this->archiveDateRangeFilters($filter, 'coin_game_users_archive.created_at', columnWidth: 1 / 3);
        });

        $grid->header(function () {
            return '<div class="alert alert-warning" style="margin-bottom:15px;">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>' . __('Note') . ':</strong> ' . __('Today\'s data does not appear in this report. Only archived data is displayed.') . '
            </div>';
        });

        $grid->column('game_name', __('Game'))->display(function () {
            $defaultImage = asset('images/businessman-icon.jpg');
            $url = getImagePath($this->game_image) ?? $defaultImage;
            if (!isImageExists($url)) $url = $defaultImage;

            $uniqueId = $this->game_id ?? 'game-unknown';
            $imageTag = handleShowImageWithTypes((string) $uniqueId, $url, 50, 50, 0);
            $gameIdHtml = "game-{$this->game_id}";
            $urlLink = admin_url("all-games/{$this->game_id}");
            $name = $this->game_name ?? 'Unknown Game';

            return <<<HTML
            <a href="{$urlLink}" style="display:flex;align-items:center;gap:10px;padding:10px;text-decoration:none;color:inherit;transition:background-color 0.2s;">
                $imageTag
                <div>
                    <strong style="font-size:16px;">{$name}</strong><br>
                    <span style="font-size:13px;">
                        ID: <span id="{$gameIdHtml}">{$this->game_id}</span>
                        <button onclick="event.preventDefault();event.stopPropagation();copyToClipboard('{$gameIdHtml}')" style="background:none;border:none;cursor:pointer;margin-left:5px;font-size:13px;color:#007bff;" title="Copy ID">📝</button>
                    </span>
                </div>
            </a>
        HTML;
        });
        $grid->column('round_id', __('Round ID'))->sortable();
        $grid->column('total_loss', __('Total Loss'))->display(function ($v) {
            return "<span style='color:red; font-weight:bold;'>" . number_format($v) . "</span>";
        })->sortable();

        $grid->column('total_win', __('Total Win'))->display(function ($v) {
            return "<span style='color:green; font-weight:bold;'>" . number_format($v) . "</span>";
        })->sortable();
        $grid->column('first_played', __('Start Date'))->display(fn($v) => $v)->sortable();
        $grid->column('last_played', __('End Date'))->display(fn($v) => $v)->sortable();
        $grid->column('orders', __('Orders'))->display(function () use ($userId) {

            $url = admin_url("coin-game-users/round-orders?user_id={$userId}&round_id={$this->round_id}");
            return "<a href='{$url}' class='btn btn-sm btn-primary'>
                    <i class='fa fa-eye'></i> " . __('Orders') . "
                </a>";
        });

        $grid->tools(function ($tools) use ($userId) {
            $tools->append('<a href="' . admin_url("coin-game-users/details?user_id={$userId}") . '" class="btn btn-sm btn-default">
                <i class="fa fa-arrow-left"></i> ' . __('Back') . '</a>');
        });
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->paginate(10);

        return $grid;
    }

    public function buildRoundOrdersGrid($roundId, $userId): Grid
    {
        $grid = new Grid(new CoinGameUserAll());

        $grid->model()
            ->with(['user', 'game', 'customGame'])
            ->where('round_id', $roundId)
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('order_id', __('Order ID'))->sortable();
        $grid->column('user_id', __('User ID'))->display(function () {
            return $this->user->name ?? $this->user_id;
        });
        $grid->column('game_name', __('Game'));
        $grid->column('coins', __('Coins'))->display(function ($v) {
            return number_format($v);
        })->sortable();
        $grid->column('type', __('Type'))->display(function ($v) {
            return $v == 0
                ? "<span style='color:red; font-weight:bold;'>" . __('Loss') . "</span>"
                : "<span style='color:green; font-weight:bold;'>" . __('Win') . "</span>";
        });
        $grid->column('app_profit_coins', __('App Profit Coins'))->display(function ($v) {
            return number_format($v ?? 0);
        })->sortable();
        $grid->column('created_at', __('Created At'))->sortable();

        $grid->tools(function ($tools) {
            $tools->append('<a href="javascript:history.back()" class="btn btn-sm btn-default">
                <i class="fa fa-arrow-left"></i> ' . __('Back') . '</a>');
        });
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableActions();
        $grid->paginate(10);

        return $grid;
    }
}
