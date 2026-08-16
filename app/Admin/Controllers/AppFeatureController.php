<?php

namespace App\Admin\Controllers;


use App\Jobs\ChangeCinemaModeJob;
use App\Models\Setting;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\AppFeature;
use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppFeatureController extends MainController
{
    public $permission_name = 'app-feature';

    /**
     * app_features slugs whose switch gates something the MOBILE user sees
     * (API routes / send paths). Everything else (whatsapp, config, commission,
     * event_report, event_role, salary_transaction, login…) locks panel/system
     * modules and lands in the second card group.
     */
    private const IN_APP_SLUGS = [
        'game', 'lucky', 'weekly_star', 'period_event', 'pk_event',
        'target_events', 'achievement', 'cp', 'room_target', 'room_gift_target',
        'charizma', 'chat', 'moment', 'reel',
        'vips', 'agencies', 'families', 'mall', 'pk',
    ];

    /**
     * Settings-table switches the mobile app actually reads (delivered by
     * VersionController::getSettingsArray from the all_settings cache).
     * Same keys + defaults the retired app-feature page (FeatureAppController)
     * used. Labels are existing translation keys; icons are FA4 (admin layout).
     */
    private const SETTING_SWITCHES = [
        'live_status'             => ['label' => 'Live Settings', 'icon' => 'fa-video-camera', 'default' => true],
        'audio_room'              => ['label' => 'Audio Room', 'icon' => 'fa-microphone', 'default' => true],
        'reel_status'             => ['label' => 'Reel Settings', 'icon' => 'fa-film', 'default' => true],
        'youtube_status'          => ['label' => 'YouTube Settings', 'icon' => 'fa-youtube-play', 'default' => true],
        'moment_status'           => ['label' => 'Moment Status', 'icon' => 'fa-camera', 'default' => true],
        'host_agency'             => ['label' => 'Agency Feature', 'icon' => 'fa-building', 'default' => false],
        'host_level_enabled'      => ['label' => 'host level', 'icon' => 'fa-star', 'default' => true],
        'share_room_with_friends' => ['label' => 'share room', 'icon' => 'fa-share-alt', 'default' => true],
        'enable_room_boom'        => ['label' => 'Room Boom', 'icon' => 'fa-bomb', 'default' => true],
        'room_cup_setting'        => ['label' => 'room cup setting', 'icon' => 'fa-trophy', 'default' => false],
    ];

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }

    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'AppFeature';

    /**
     * Unified features page (owner 2026-08-14): cards ARE the index. Three
     * kinds of switches on one page — settings keys the app reads directly,
     * in-app feature flags (app_features), and panel/system module flags.
     * The old grid stays reachable on app-features-list for full CRUD.
     */
    public function index(Content $content)
    {
        $features = AppFeature::orderBy('id')->get();
        [$inAppFeatures, $systemFeatures] = $features->partition(
            fn ($feature) => in_array($feature->slug, self::IN_APP_SLUGS, true)
        );

        $values = Setting::whereIn('key', array_keys(self::SETTING_SWITCHES))
            ->pluck('value', 'key');

        $switches = [];
        foreach (self::SETTING_SWITCHES as $key => $meta) {
            $switches[$key] = [
                'label'   => $meta['label'],
                'icon'    => $meta['icon'],
                'enabled' => isset($values[$key]) ? $values[$key] == 1 : $meta['default'],
            ];
        }

        return parent::index($content
            ->title(trans('AppFeature'))
            ->body(view('admin.app_features_cards', compact('inAppFeatures', 'systemFeatures', 'switches'))));
    }

    /**
     * Legacy cards URL — the cards are the index now, keep old bookmarks alive.
     */
    public function preview()
    {
        return redirect(admin_url('app-features'));
    }

    /**
     * The old grid, kept as the full-CRUD management surface behind the small
     * "Manage List" button on the cards page.
     */
    public function listPage(Content $content)
    {
        Permission::check('browse-' . $this->permission_name);

        return $content
            ->title(trans('AppFeature'))
            ->description(trans('admin.list'))
            ->body($this->grid());
    }

    /**
     * AJAX: flip an app_features row status. Cache app_feature_status_{slug}
     * is flushed by the AppFeature model saved() hook — without that flush the
     * switch would be a no-op (AppFeatureService caches rememberForever).
     */
    public function toggleFeatureStatus(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $data = $request->validate([
            'id'     => 'required|integer|exists:app_features,id',
            'status' => 'required|boolean',
        ]);

        $feature = AppFeature::findOrFail($data['id']);
        $feature->status = $data['status'] ? 1 : 0;
        $feature->save();

        return response()->json(['status' => true, 'message' => __('Settings updated successfully!')]);
    }

    /**
     * AJAX: flip one of the settings-table switches the app reads. Persists
     * through the Setting model so SettingObserver::saved flushes all_settings
     * (the cache VersionController::getSettingsArray serves the app from) plus
     * the per-key caches; the per-key put mirrors SettingsController::update.
     */
    public function toggleSetting(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-settings');
        }

        $data = $request->validate([
            'key'   => 'required|string|in:' . implode(',', array_keys(self::SETTING_SWITCHES)),
            'value' => 'required|boolean',
        ]);

        $value = $data['value'] ? '1' : '0';

        Setting::updateOrCreate(['key' => $data['key']], ['value' => $value]);
        Cache::forget($data['key']);
        Cache::put($data['key'], $value);

        // Parity with SettingsController::update — closing YouTube kicks every
        // cinema-mode room back to party mode.
        if ($data['key'] === 'youtube_status' && $value === '0') {
            dispatch(new ChangeCinemaModeJob());
        }

        return response()->json(['status' => true, 'message' => __('Settings updated successfully!')]);
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return parent::show($id, $content
            ->title(trans('AppFeature'))
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
            ->title(trans('AppFeature'))
            ->body($this->form()->edit($id)));
    }

    public function create(Content $content)
    {
        return parent::create($content
            ->title(trans('AppFeature'))
            ->body($this->form()));
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AppFeature());

        $grid->tools(function (Grid\Tools $tools) {
            $tools->append(
                '<a href="' . url('admin/app-features') . '" class="btn btn-sm btn-primary" style="margin-inline-start:8px;">'
                . '<i class="fa fa-th-large"></i> ' . __('Cards View')
                . '</a>'
            );
        });

        $grid->column('id', __('Id'));
        $grid->column('image', __('image'))->display(function ($image) {
            $path = getImagePath($image);
            if (!isImageExists(@$path)) {
                return '<span style="color:#94a3b8;"><i class="fa fa-image"></i></span>';
            }

            return "<img src='{$path}' style='width:44px;height:44px;object-fit:cover;border-radius:8px;' />";
        });
        $grid->column('name_ar', __('name'));
        $grid->column('name', __('name_en'));
        $grid->column('slug', __('slug'));
        $grid->column('status', __('status'));
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
        $show = new Show(AppFeature::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('image', __('image'))->image();
        $show->field('name', __('Name'));
        $show->field('name_ar', __('Name ar'));
        $show->field('description', __('Description'));
        $show->field('description_ar', __('Description ar'));
        $show->field('slug', __('Slug'));
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new AppFeature());


        $form->text('name_ar', __('name '));
        $form->text('name', __('name_en'));
        $form->textarea('description_ar', __('Description ar'))->rows(4);
        $form->textarea('description', __('Description'))->rows(4);
        $form->image('image', __('image'))->removable();
        $form->text('slug', __('Slug'));
        $state = [
            'on' => ['value' => 1, 'text' => 'open', 'color' => 'primary'],
            'off' => ['value' => 0, 'text' => 'close', 'color' => 'default'],
        ];

        $form->switch('status', __("status"))->states($state);

        return $form;
    }
}
