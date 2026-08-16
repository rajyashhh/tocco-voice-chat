<?php

namespace Modules\Public\Http\Controllers\web;

use App\Models\Config;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Services\LevelService;
use Encore\Admin\Facades\Admin;
use App\Services\LevelCurveService;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\Facades\Cache;
use App\Admin\Controllers\MainController;
use App\Models\Setting;

class UpgradeLevelController extends MainController
{
    protected const EXP_KEYS = [
        'exp_sender_percentage',
        'exp_received_percentage',
        'exp_cp_percentage',
        'exp_room_percentage',
        'exp_charge_percentage',
    ];

    protected $levelService;
    protected $curveService;

    public function __construct(LevelService $levelService, LevelCurveService $curveService)
    {
        $this->levelService = $levelService;
        $this->curveService = $curveService;
    }

    public function ovipConfig(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-ovip-settings');
        }

        $data = $request->except('_token', 'test_calco', 'current_tab', 'inner_tab_type');

        foreach ($data as $key => $value) {
            Config::updateOrCreate(['name' => $key], ['value' => $value]);
            Cache::forget($key);
        }

        // Targeted invalidation only — configs live in the all_configs map and
        // the exp keys additionally in the exp_percentages snapshot. A full
        // Cache::flush() here used to wipe every hot cache in production.
        Cache::forget('all_configs');
        if (array_intersect(array_keys($data), self::EXP_KEYS)) {
            Cache::forget('exp_percentages');
            $this->rebuildExpPercentages();
        }

        admin_toastr(__('Settings updated successfully!'), 'success');
        return redirect($this->settingsRedirectUrl($request));
    }

    public function exchange(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-exchange');
        }

        $data = $request->except('_token', 'test_calco', 'current_tab', 'inner_tab_type');

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            Cache::forget($key);
            Cache::put($key, $value, now()->addYear());
        }

        admin_toastr(__('Settings updated successfully!'), 'success');
        return redirect(url(config('admin.route.prefix') . '/charges-settings?firsttab=coinExchange'));
    }

    public function curvePreview(Request $request)
    {
        $validated = $this->validateCurveInput($request);

        $curve = $this->curveService->generate(
            (int) $validated['levels_count'],
            (int) $validated['first_exp'],
            (float) $validated['growth_pct'],
            isset($validated['late_growth_pct']) ? (float) $validated['late_growth_pct'] : null,
        );

        return response()->json(['status' => true, 'curve' => $curve]);
    }

    public function curveApply(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-level');
        }

        $validated = $this->validateCurveInput($request);

        $curve = $this->curveService->generate(
            (int) $validated['levels_count'],
            (int) $validated['first_exp'],
            (float) $validated['growth_pct'],
            isset($validated['late_growth_pct']) ? (float) $validated['late_growth_pct'] : null,
        );

        $result = $this->curveService->apply((int) $validated['level_type'], $curve);

        admin_toastr(__(':updated levels updated, :created levels created.', [
            'updated' => $result['updated'],
            'created' => $result['created'],
        ]), 'success');

        return redirect($this->settingsRedirectUrl($request));
    }

    protected function validateCurveInput(Request $request): array
    {
        return $request->validate([
            'level_type'      => 'required|integer|in:1,2,3,4,5',
            'levels_count'    => 'required|integer|min:1|max:500',
            'first_exp'       => 'required|integer|min:1',
            'growth_pct'      => 'required|numeric|min:0|max:1000',
            'late_growth_pct' => 'nullable|numeric|min:0|max:1000',
        ]);
    }

    protected function rebuildExpPercentages(): void
    {
        $collection = Common::getConfFromKey(self::EXP_KEYS);
        $values = [];
        foreach (self::EXP_KEYS as $key) {
            $config = $collection->where('name', $key)->first();
            $values[$key] = $config ? $config->value : 1;
        }
        Cache::put('exp_percentages', $values, now()->addMinutes(60));
    }

    protected function settingsRedirectUrl(Request $request): string
    {
        $redirectUrl = url(config('admin.route.prefix') . '/settings');
        if ($request->has('current_tab')) {
            $redirectUrl .= '?tab=' . $request->current_tab;
            if ($request->has('inner_tab_type')) {
                $redirectUrl .= '&type=' . $request->inner_tab_type;
            }
        }

        return $redirectUrl;
    }

    public function group_chat_config(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-group-chat');
        }

        $conf = Config::where('name', 'send_world_chat')->first();
        if (!$conf) {
            config::create([
                'name'  => 'send_world_chat',
                'value' => $request->number,
            ]);
        } else {
            $conf->value = $request->number;
            $conf->save();
        }

        return redirect()->back()->with('message', __('dashboard.update'));
    }

    public function reelConfig(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-reel-settings');
        }

        $conf = Config::where('name', 'upload_reel')->first();
        if (!$conf) {
            config::create([
                'name'  => 'upload_reel',
                'value' => $request->number,
            ]);
        } else {
            $conf->value = $request->number;
            $conf->save();
        }

        return redirect()->back()->with('message', __('dashboard.update'));
    }

    public function momentConfig(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-moment-settings');
        }

        $conf = Config::where('name', 'upload_moment')->first();
        if (!$conf) {
            config::create([
                'name'  => 'upload_moment',
                'value' => $request->number,
            ]);
        } else {
            $conf->value = $request->number;
            $conf->save();
        }

        return redirect()->back()->with('message', __('dashboard.update'));
    }

    public function getLevelsRange()
    {
        $data = $this->levelService->getAllLevelsRanges();
        return Common::apiResponse(true, 'success', $data);
    }
}