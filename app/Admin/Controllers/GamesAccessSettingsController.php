<?php

namespace App\Admin\Controllers;

use App\Helpers\CacheHelper;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GamesAccessSettingsController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Game Conditions';
    public $permission_name = 'games-access-settings';

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        $configs = Config::pluck('value', 'name');

        $settings = [
            'games_min_level'     => (int) ($configs['games_min_level'] ?? 0),
            'games_min_recharge'  => (int) ($configs['games_min_recharge'] ?? 0),
            'games_allowed_roles' => (string) ($configs['games_allowed_roles'] ?? ''),
            // Absorbed from the retired standalone game-settings page (it held
            // only this one field). Read by LeaderCCgameController map rounds.
            'game_map_win_coins'  => (int) ($configs['game_map_win_coins'] ?? 10000),
        ];

        return $content
            ->header(__('Game Conditions'))
            ->description(__('Who Can Play'))
            ->body(view('admin.games_access_settings', compact('settings')));
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $validated = $request->validate([
            'games_min_level'     => ['nullable', 'integer', 'min:0'],
            'games_min_recharge'  => ['nullable', 'integer', 'min:0'],
            'games_allowed_roles' => ['nullable', 'string', 'regex:/^(\d+)(,\d+)*$/'],
            'game_map_win_coins'  => ['nullable', 'integer', 'min:1'],
        ]);

        $values = [
            'games_min_level'     => (int) ($validated['games_min_level'] ?? 0),
            'games_min_recharge'  => (int) ($validated['games_min_recharge'] ?? 0),
            'games_allowed_roles' => (string) ($validated['games_allowed_roles'] ?? ''),
            'game_map_win_coins'  => (int) ($validated['game_map_win_coins'] ?? 10000),
        ];

        foreach ($values as $name => $value) {
            Config::updateOrCreate(['name' => $name], ['value' => $value]);
            Cache::forget($name);
        }

        // UserHandling reads these via Common::getConfig(), which is backed by the
        // rememberForever 'all_configs' cache; per-key forgets never touch it, so
        // rebuild it here or the gate keeps evaluating the pre-save values.
        Cache::forget('all_configs');
        CacheHelper::cacheConfig();

        admin_toastr(__('Settings updated successfully!'), 'success');

        return back();
    }
}
