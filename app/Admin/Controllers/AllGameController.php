<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\MainController;
use App\Models\AllGame;
use App\Models\CoinGameUser;
use App\Models\GameProviderSetting;
use App\Services\AppFeatureService;
use DB;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Show;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\InfoBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AllGameController extends MainController
{

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }
    protected $title = 'games';
    public $permission_name = 'games';

    
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('Games'))
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
            ->title(trans('Games'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('Games'))
            ->body($this->form()));
    }
    public function index(Content $content)
    {
        if (request("from_date") != null && request("to_date") != null) {
            $from_date = request("from_date");
            $to_date = request("to_date");
        } else {
            $from_date  = now()->startOfMonth();
            $to_date    = now()->endOfMonth();
        }
        $results = CoinGameUser::select(DB::raw("
            SUM(CASE WHEN type = 0 THEN coins ELSE 0 END) AS total_lose,
            SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) AS total_earn
        "))
            ->whereDate('created_at', '>=', $from_date)
            ->whereDate('created_at', '<=', $to_date)
            ->first();


        $total_lose = $results->total_lose;
        $total_earn = $results->total_earn;
        $result = $total_lose - $total_earn;
        return parent::index($content
            ->title(trans('All Games'))
            ->description('')
            ->row(function (Row $row) use ($result) {
                $row->column(6, $this->grid2());
                $row->column(6, new InfoBox(__('Game profits'), 'gamepad', 'primary', null, $result));
            })

            ->row($this->grid()));
    }
    protected function grid2()
    {
        $form = new Box();
        $form->view('admin.grid.Form.allGameForm');

        return $form;
    }

   

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    
    protected function grid()
    {
        $grid = new Grid(new AllGame());

        $grid->column('id', __('Id'));
        $grid->column('is_enable', __('enable'))->switch();
        $grid->column('custom_id', __('custom_id'));
        $grid->column('name', __('name_ar'));
        $grid->column('name_en', __('name_en'));
        $grid->column('type', __('type'))->using([
            4 => __('UTD Game'),
        ]);
        $grid->column('url', __('Full Url'));
        $grid->column('mini_url', __('Mini Url'));
        $grid->column('image', __('Image'))->image('', 50);

        // Import JSON button + modal (rendered from Blade view)
        $importUrl = url(config('admin.route.prefix') . '/all-games/import-json');
        $grid->tools(function ($tools) use ($importUrl) {
            $modalHtml = view('admin.grid.Form.importGamesModal', compact('importUrl'))->render();
            $tools->append(
                '<div class="btn-group pull-right" style="margin-right: 10px;">'
                . '<button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#importJsonModal" style="border-radius:8px;font-weight:600;padding:7px 16px;box-shadow:0 2px 8px rgba(16,185,129,0.25);">'
                . '<i class="fa fa-cloud-download"></i>&nbsp; Import JSON'
                . '</button></div>'
                . $modalHtml
            );
        });

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
        $show = new Show(AllGame::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('custom_id', __('custom_id'));
        $show->field('name', __('name_ar'));
        $show->field('name_en', __('name_en'));
        $show->field('url', __('Url'));
        $show->field('image', __('Image'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AllGame());
        $this->disableFormTools($form);
        $form->text('custom_id', __('custom_id'));
        $form->textarea('name', __('name_ar'))->required();
        $form->textarea('name_en', __('name_en'))->required();
        $form->select('type', __('type'))->options(
            [
                4 => __('UTD Game'),
            ]
        )->default(4);
        $form->image('image', __('Image'));
        $form->switch('is_enable', __('enable'));

        // Display mode selector — picks WHICH of the three links below is served
        // to the app for this game (see help text on each link).
        $form->select('in_room', __('in_room'))->options(
            [
                0 => __('Half Screen'),
                1 => __('Full Screen'),
                2 => __('HD Half Screen'),
            ]
        )->default(0)
            ->help(__('Display mode used in the room. It selects which link below the app loads: Half Screen → Half Screen Link, Full Screen → Full Screen Link, HD Half Screen → HD Half Screen Link.'));

        $form->url('url', __('Full Screen Link'))
            ->help(__('Used when display mode = Full Screen.'));
        $form->url('mini_url', __('Half Screen Link'))
            ->help(__('Used when display mode = Half Screen.'));
        $form->url('hd_url', __('HD Half Screen Link'))
            ->help(__('Used when display mode = HD Half Screen.'));

        // Height tuning for the in-room web view (percentage of the room area).
        $form->text('height', __('height'))
            ->help(__('Web view height (%) applied when display mode = Full Screen.'));
        $form->text('height_image', __('height_image'))
            ->help(__('Safe-area / image height (%) used by the app layout.'));

        return $form;
    }


    /**
     * Import games from JSON URL or pasted JSON data.
     */
    public function importJson(Request $request)
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        $gameType = (int) $request->input('game_type', 4);
        $jsonUrl = $request->input('json_url');
        $jsonData = $request->input('json_data');
        $games = null;

        // Priority: pasted JSON > URL
        if (!empty(trim($jsonData ?? ''))) {
            $games = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                admin_toastr('Invalid JSON format: ' . json_last_error_msg(), 'error');
                return back();
            }
        } elseif (!empty(trim($jsonUrl ?? ''))) {
            try {
                $response = Http::withoutVerifying()->timeout(15)->get($jsonUrl);
                if (!$response->successful()) {
                    admin_toastr('Failed to fetch JSON from URL. HTTP Status: ' . $response->status(), 'error');
                    return back();
                }
                $games = $response->json();
            } catch (\Exception $e) {
                Log::error('[AllGameController] importJson fetch error: ' . $e->getMessage());
                admin_toastr('Error fetching URL: ' . $e->getMessage(), 'error');
                return back();
            }
        } else {
            admin_toastr('Please provide a JSON URL or paste JSON data', 'error');
            return back();
        }

        if (!is_array($games) || empty($games)) {
            admin_toastr('JSON must be a non-empty array of game objects', 'error');
            return back();
        }

        // Validate required fields
        foreach ($games as $index => $game) {
            if (!isset($game['gameId']) || !isset($game['name']) || !isset($game['full_url'])) {
                admin_toastr("Game at index {$index} is missing required fields (gameId, name, full_url)", 'error');
                return back();
            }
        }

        // Import games using updateOrCreate
        $imported = 0;
        $updated = 0;

        foreach ($games as $game) {
            $data = [
                'custom_id' => $game['gameId'],
                'name'      => $game['title'] ?? $game['name'],
                'name_en'   => $game['name'],
                'url'       => $game['full_url'] ?? null,
                'hd_url'    => $game['hd_url'] ?? null,
                'mini_url'  => $game['half_url'] ?? null,
                'type'      => $gameType ?? 4,
            ];

            $existing = AllGame::where('custom_id', $game['gameId'])->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                $data['is_enable'] = 1;
                AllGame::create($data);
                $imported++;
            }
        }

        admin_toastr("Import completed! {$imported} new games added, {$updated} games updated.", 'success');
        return redirect(url(config('admin.route.prefix') . '/all-games'));
    }

    public function gameSettings(Request $request)
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        $request->validate([
            'provider_code'           => 'required|string|max:255',
            'provider_name'           => 'nullable|string|max:255',
            'app_key'                 => 'nullable|string|max:255',
            'app_id'                  => 'nullable|string|max:255',
            'app_secret'              => 'nullable|string|max:255',
            'channel'                 => 'nullable|string|max:255',
            'gsp'                     => 'nullable|string|max:64',
            'base_url'                => 'nullable|string|max:255',
            'callback_url'            => 'nullable|string|max:255',
            'webhook_sign_key_source' => 'nullable|in:app_key,app_id,secret',
            'ip_allowlist'            => 'nullable|string|max:4000',
            // Optional JSON map of App->Game interface URLs (overrides base_url+path
            // per interface, since LeaderCC gives each interface its own URL):
            // {"create_room":"https://...","start_game":"https://...", ...}
            'app_game_endpoints'      => 'nullable|string|max:8000',
            'active'                  => 'nullable|boolean',
        ]);

        // Validate the App->Game endpoints JSON (if provided) before saving.
        $appGameEndpoints = null;
        if ($request->filled('app_game_endpoints')) {
            $appGameEndpoints = json_decode($request->app_game_endpoints, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($appGameEndpoints)) {
                return back()->withInput()->withErrors([
                    'app_game_endpoints' => 'Must be a valid JSON object of interface => URL.',
                ]);
            }
        }

        $attributes = [
            'provider_name'           => $request->provider_name,
            'app_key'                 => $request->app_key,
            'app_id'                  => $request->app_id,
            'base_url'                => $request->base_url,
            'callback_url'            => $request->callback_url,
            'webhook_sign_key_source' => $request->webhook_sign_key_source ?: 'app_key',
            // Empty = allow all (backward-compatible). Stored verbatim; parsing
            // (CIDR/bare IP, comma/newline) happens in VerifyQuantumWebhook.
            'ip_allowlist'            => $request->ip_allowlist,
            'is_active'               => $request->active,
        ];

        // Merge the App->Game interface URL overrides into extra_settings without
        // clobbering any other extra_settings keys.
        if ($request->filled('app_game_endpoints')) {
            $existing = GameProviderSetting::where('provider_code', $request->provider_code)->value('extra_settings');
            $existing = is_string($existing) ? (json_decode($existing, true) ?: []) : (is_array($existing) ? $existing : []);
            $existing['app_game_endpoints'] = $appGameEndpoints;
            $attributes['extra_settings'] = $existing;
        }

        // app_secret is encrypted at rest (model mutator). Only overwrite when a
        // value is submitted so leaving the masked field blank keeps the stored
        // secret intact instead of wiping it.
        if ($request->filled('app_secret')) {
            $attributes['app_secret'] = $request->app_secret;
        }

        // channel/gsp are only posted by providers that use them —
        // guard so the other provider forms don't null them out.
        if ($request->filled('channel')) {
            $attributes['channel'] = $request->channel;
        }
        if ($request->filled('gsp')) {
            $attributes['gsp'] = $request->gsp;
        }

        // ── Schema-adaptive persistence ─────────────────────────────────────
        // Legacy DBs still carry the generic key/value shape of this table
        // (`provider`,`key`,`type` NOT NULL, no default) and may lack the newer
        // base_url/callback_url/extra_settings columns. A create() there throws
        // "Field 'provider' doesn't have a default value" and silently blocks
        // activation for any provider without an existing row. Drop absent
        // columns and satisfy legacy NOT NULL columns on INSERT so activation
        // works on ANY schema with no migration required.
        $table  = (new GameProviderSetting)->getTable();
        $hasCol = fn (string $c) => \Illuminate\Support\Facades\Schema::hasColumn($table, $c);

        foreach (array_keys($attributes) as $col) {
            if (! $hasCol($col)) {
                unset($attributes[$col]);
            }
        }

        if (! GameProviderSetting::where('provider_code', $request->provider_code)->exists()) {
            if ($hasCol('provider') && ! array_key_exists('provider', $attributes)) {
                $attributes['provider'] = $request->provider_code;
            }
            if ($hasCol('key') && ! array_key_exists('key', $attributes)) {
                $attributes['key'] = $request->provider_code;
            }
            if ($hasCol('type') && ! array_key_exists('type', $attributes)) {
                $attributes['type'] = 'game_provider';
            }
        }

        $gameSetting = GameProviderSetting::updateOrCreate(
            ['provider_code' => $request->provider_code],
            $attributes
        );

        // 🔥 Clear old cache
        Cache::forget('game_provider_' . $request->provider_code);

        // 🔥 Store fresh data in cache (2h TTL mirrors Common::getByCode)
        Cache::put(
            'game_provider_' . $request->provider_code,
            $gameSetting,
            now()->addHours(2),
        );

        $redirectUrl = url(config('admin.route.prefix') . '/settings');

        if ($request->has('current_tab')) {
            $redirectUrl .= '?tab=' . $request->current_tab;
            if ($request->has('inner_tab_type')) {
                $redirectUrl .= '&type=' . $request->inner_tab_type;
            }
        } elseif ($request->has('redirect_to')) {
            return Redirect::to($request->redirect_to);
        }

        return redirect($redirectUrl);
    }
}
