<?php


namespace App\Admin\Controllers;

use App\Models\CoinGameUserAggregated;
use App\Models\CoinGameUserArchive;
use App\Models\CoinGameUserDailyAggregated;
use App\Models\User;
use App\Models\AllGame;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\HasResourceActions;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\InfoBox;
use Encore\Admin\Layout\Row;
use Illuminate\Support\Facades\DB;
use App\Admin\Services\UserService;
use Encore\Admin\Facades\Admin;

use App\Services\Admin\CoinGameUserService;
use Illuminate\Support\Facades\Log;

class CoinGameUserAllController extends AdminController
{
    protected $permission_name = 'coin-game-users-report';
    protected $service;

    public function __construct(CoinGameUserService $service)
    {
        $this->service = $service;
    }

    /**
     * Main index page with totals and grid.
     */




    public function index(Content $content)
    {

        Admin::script($this->ajaxScript());

        return $content
            ->title(__('coin_game_users'))
            ->description(__('coin_game_users_description'))
            ->row(fn($row) => $row->column(12, '<div id="info-boxes"></div>')) 
            ->row(fn($row) => $row->column(12, $this->service->buildGrid()));
    }


    public function index_details(Content $content)
    {
        $user_id = request('user_id');
        if (!$user_id) {
            return redirect(admin_url("coin-game-users-reports"));
        }

        Admin::script($this->ajaxDetailsScript($user_id));

        return $content
            ->title(__('coin_game_users'))
            ->description(__('coin_game_users_description'))
            ->row(fn($row) => $row->column(12, '<div id="info-boxes-details"></div>'))
            ->row(fn($row) => $row->column(12, $this->service->buildGrid_details($user_id)));
    }

    /**
     * Show all rounds for a specific user and game.
     */
    public function showAll(Content $content, Request $request)
    {
        $userId = $request->get('user_id');
        $gameId = $request->get('game_id');
        if(!$userId  || !$gameId){
            return redirect(admin_url("coin-game-users/details"));
        }
        $grid = $this->service->buildShowAllGrid($userId, $gameId);

        return $content
            ->title(__('round_details'))
            ->description(__('round_details') . " | User: {$userId} | Game: {$gameId}")
            ->body($grid);
    }


    public function ajaxDetailsTotals(Request $request)
    {
        $userId = $request->get('user_id');
        if (!$userId) {
            return response()->json(['html' => '']);
        }

        $query = CoinGameUserArchive::query()
            ->where('user_id', $userId)
            ->whereNotNull('game_id');

        $from = $request->get('from_date');
        $to = $request->get('to_date');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $totals = $query->selectRaw("
            SUM(coins) as total_played,
            SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) as total_loss,
            SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as total_win,
            SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) - SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) as app_profit
        ")->first();

        $html = view('admin.info_boxes', compact('totals'))->render();
        return response()->json(['html' => $html]);
    }

    public function roundOrders(Content $content, Request $request)
    {
        $roundId = $request->get('round_id');
        $userId = $request->get('user_id');
     
        if (!$roundId) {
            return redirect(admin_url('coin-game-users-reports'));
        }
        $grid = $this->service->buildRoundOrdersGrid($roundId,$userId);

        return $content
            ->title(__('round_orders'))
            ->description(__('round_orders') . " | Round: {$roundId}")
            ->body($grid);
    }

    public function ajaxTotals(Request $request)
    {
        $filters = $request->all();

        $query = CoinGameUserDailyAggregated::query()
            ->whereNotNull('game_id');

        // Apply filters using readable keys from request
        // The AJAX request passes the same query string from the URL,
        // which contains the hashed filter keys from Laravel-Admin.
        // We use applyFilters which handles both readable and hashed keys.
        $query = $this->service->applyFilters($query, $filters);

        $totals = $this->service->calculateTotals($query, $filters);

        $html = view('admin.info_boxes', compact('totals'))->render();
        return response()->json(['html' => $html]);
    }

    protected function ajaxDetailsScript($userId)
    {
        $url = admin_url('coin-game-users/details-ajax');

        return <<<JS
    function loadDetailsInfoBoxes() {
        let filters = window.location.search;

        $.ajax({
            url: "$url" + filters,
            type: "GET",
            success: function(res) {
                $("#info-boxes-details").html(res.html);
            },
            error: function() {
                alert("Failed to load totals");
            }
        });
    }

    $(function() {
        loadDetailsInfoBoxes();

        $(document).on("pjax:end", function() {
            loadDetailsInfoBoxes();
        });
    });
    JS;
    }

    protected function ajaxScript()
    {
        $url = admin_url('coin-game-users/ajax'); 

        return <<<JS
    function loadInfoBoxes() {
        let filters = window.location.search; 

        $.ajax({
            url: "$url" + filters, 
            type: "GET",
            success: function(res) {
                $("#info-boxes").html(res.html);
            },
            error: function() {
                alert("Failed to load totals");
            }
        });
    }

    // auto-load on page load
    $(function() {
        loadInfoBoxes();

        $(document).on("pjax:end", function() {
            loadInfoBoxes();
        });
    });
    JS;
    }
}
