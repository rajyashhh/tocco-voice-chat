<?php

use App\Models\Room;
use App\Models\User;
use App\Models\Background;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Route;
use App\Admin\Controllers\ColorController;
use App\Admin\Controllers\OfferController;
use App\Admin\Controllers\CustomController;
use App\Admin\Controllers\ExportController;
use App\Admin\Controllers\PoliceController;
use App\Admin\Controllers\AllGameController;
use App\Admin\Controllers\BanTypeController;
use App\Admin\Controllers\RoomVipController;
use App\Admin\Controllers\SettingController;
use App\Admin\Controllers\WareVipController;
use App\Admin\Controllers\QuestionController;
use App\Admin\Controllers\WithdrawController;
use App\Admin\Controllers\AdminAuthController;
use App\Admin\Controllers\GroupChatController;
use App\Admin\Controllers\AdminUsersController;
use App\Admin\Controllers\AppFeatureController;
use App\Admin\Controllers\ImageColorController;
use App\Admin\Controllers\ReportUserController;
use App\Admin\Controllers\RoomTargetController;
use App\Admin\Controllers\CoreWalletsController;
use App\Admin\Controllers\ParentUsersController;
use App\Admin\Controllers\RoomSettingsController;
use App\Admin\Controllers\MangerSettingController;
use App\Admin\Controllers\MultiLanguageController;
use App\Admin\Controllers\PaymentGetWayController;
use App\Admin\Controllers\AgencySettingsController;
use App\Admin\Controllers\BlackListUsersController;
use App\Admin\Controllers\ChargesSettingController;
use App\Admin\Controllers\RoomGiftTargetController;
use App\Admin\Controllers\chargUsersSleemController;
use App\Admin\Controllers\AppSitiingCOnfigController;
use App\Admin\Controllers\GroupChatSettingController;
use App\Admin\Controllers\TargetPercentageController;
use App\Admin\Controllers\UserOnlineHistoryController;
use App\Admin\Controllers\TrashedUserAccountController;
use App\Admin\Controllers\AppearChargerAgencyController;


Admin::routes();
Route::group(['prefix' => config('admin.route.prefix'), 'namespace' => config('admin.route.namespace'), 'middleware' => [
    'web',
    'admin',
    'prevent-delete',
    'adminIp',
    'multiLanguage',
], 'as' => config('admin.route.prefix') . '.',], function () {
    // Route::post('_handle_form_', 'HandleController@handleForm')->name('admin.handle-form');
    // Route::post('_handle_action_', 'HandleController@handleAction')->name('admin.handle-action');
    // Route::get('_handle_selectable_', 'HandleController@handleSelectable')->name('admin.handle-selectable');
    // Route::get('_handle_renderable_', 'HandleController@handleRenderable')->name('admin.handle-renderable');
});


Route::group(['prefix' => config('admin.route.prefix'), 'namespace' => '', 'middleware' => [
    'web',
   // 'admin',
    'multiLanguage',
], 'as' => config('admin.route.prefix') . '.',], function () {
    Route::post('login', App\Admin\Controllers\Preview\AuthController::class . '@postLogin');
    Route::get('login', [App\Admin\Controllers\Preview\AuthController::class, 'getLogin']);
});


Route::group(['prefix' => config('admin.route.prefix'), 'namespace' => config('admin.route.namespace'), 'middleware' => [
    'web',
    'admin',
    'prevent-delete',
    'adminIp',
    'multiLanguage',
    'admin.rbac',
], 'as' => config('admin.route.prefix') . '.',], function () {
    Route::post('/locale', MultiLanguageController::class . '@locale');

    Route::resource('questions', QuestionController::class);
    Route::resource('user-online-history', UserOnlineHistoryController::class);

    Route::get('btats', function () {
        return response()->json(Admin::menu());
    });
    Route::get('agency-user-job/{agency_id}', 'AgencyUserJobController@index');
    Route::get('agency-user-job/{agency_id}/create', 'AgencyUserJobController@create');
    Route::get('agency-user-job/{agency_id}', 'AgencyUserJobController@index');
    Route::post('agency-user-job/{agency_id}', 'AgencyUserJobController@store');
    Route::get('agency-user-job/{agency_id}/{id}/edit', 'AgencyUserJobController@edit');
    Route::get('agency-statistic', 'AgencyStatisticController@index');
    Route::get('agency-settings', 'AgencySettingController@index');
    Route::resource('test-test', 'TestTestController');

    Route::resource('auth/users', 'AdminUserController')->names([
        'index' => 'auth.users.index',
        'create' => 'auth.users.create',
        'store' => 'auth.users.store',
        'show' => 'auth.users.show',
        'edit' => 'auth.users.edit',
        'update' => 'auth.users.update',
        'destroy' => 'auth.users.destroy',
    ]);
    Route::resource('auth/roles', 'RoleController');
    Route::resource('colors', ColorController::class);
    Route::resource('agency-settings', AgencySettingsController::class);
    Route::get('agency-settings', 'AgencySettingController@index');
    Route::get('chat-settings', [GroupChatController::class, 'chat_settings']);
    Route::resource('custom-settings', CustomController::class);
    Route::get('/setting-group-char', [GroupChatSettingController::class, 'index']);
    Route::get('/agency-setting-manger', [MangerSettingController::class, 'index']);
    Route::resource('settings', SettingController::class);
    Route::resource('room-settings', RoomSettingsController::class)->only('index', 'store');
    Route::resource('charges-settings', ChargesSettingController::class);
    Route::get('app-features-preview', [AppFeatureController::class, 'preview'])->name('app-features.preview');
    Route::get('app-features-list', [AppFeatureController::class, 'listPage'])->name('app-features.list');
    Route::post('app-features/toggle-status', [AppFeatureController::class, 'toggleFeatureStatus'])->name('app-features.toggle-status');
    Route::post('app-features/toggle-setting', [AppFeatureController::class, 'toggleSetting'])->name('app-features.toggle-setting');
    Route::resource('app-features', AppFeatureController::class);
    //resources
    Route::resource('users', 'UserController', ['names' => ['index' => 'users', 'show' => 'users.show']]);
    Route::post('send-request-invite-code', 'UserController@request_invite_code');
    Route::resource('user-statistics', 'UserStatisticsController')->only('index');
    Route::get('profile', [AdminAuthController::class, 'index']);
    Route::resource('vips', 'VipController');
    Route::resource('rooms', 'RoomController', ['names' => ['index' => 'rooms']]);
    Route::resource('all-games', AllGameController::class);
    Route::resource('blacks', 'BlackListController');
    Route::prefix('black-lists')->group(function () {
        Route::get('/', [BlackListUsersController::class, 'index']);
    });
    Route::resource('codes', 'CodeController');
    Route::resource('gifts', 'GiftController', ['names' => ['index' => 'gifts']]);
    Route::resource('wares', 'WareController', ['names' => ['index' => 'wares']]);
    Route::resource('report_user', ReportUserController::class);
    // Route::resource('coupons', 'CouponController');
    Route::resource('configs', 'ConfigController');
    Route::resource('categories', 'RoomCategoryController');
    Route::resource('countries', 'CountryController');
    Route::resource('backgrounds', 'BackgroundController');
    Route::resource('official_msgs', 'OfficialMessageController');
    Route::resource('emojis', 'EmojiController');
    Route::resource('home_carousels', 'HomeCarouselController');
    Route::resource('vip_prev', 'VipAuthController');
    Route::resource('agencies', 'AgencyController');
    Route::resource('families', 'FamilyController');
    Route::resource('targets', 'TargetController');
    Route::resource('polices', PoliceController::class);
    Route::resource('offers', OfferController::class);
    Route::resource('payment-gateways', PaymentGetWayController::class);
    Route::resource('charges', 'ChargeController', [

        'names' => ['index' => 'charges', 'show' => 'charges.show']
    ]);
    Route::resource('charges-details', 'ChargesDetailsController', [

        'names' => [
            'index' => 'charges-details',
            'show' => 'charges-details.show'
        ]
    ]);
    Route::resource('commissions', 'CommissionController', [

        'names' => ['index' => 'commissions', 'show' => 'commission.show']
    ]);
    Route::resource('charge_values', 'ChargeValueController');
    Route::resource('userTarget', 'UserTargetController', ['names' => ['index' => 'user_targets']]);
    // Route::get('/', 'HomeController@infoBox')->name('home');
    Route::get('/', 'AllStatisticController@index')->name('home');
    Route::get('app-earned', 'AppEarnedController@index')->name('app-earned');
    Route::get('/custom-export-users', [ExportController::class, 'usersSallaryTargets'])->name('custom-export-users');
    Route::get('/agency-export-report', [ExportController::class, 'usersAgencyTargets'])->name('agency-export-report');
    Route::get('/dev', 'HomeController@devindex')->name('dev-home');
    Route::get('/agency_home', 'HomeController@infoBox')->name('agency2.home');
    Route::resource('wares-vips', WareVipController::class);
    Route::resource('room-gift-targets', RoomGiftTargetController::class);

    //--------------------
    // Route::get('/', 'HomeController@infoBox')->name('home');
    Route::get('/dev', 'HomeController@devindex')->name('dev-home');
    Route::resource('manger-types', 'MangerTypeController');
    // Route::resource('chat-letters', ChatLetterController::class);
    Route::resource('userscharg', chargUsersSleemController::class);
    Route::resource('image-colors', ImageColorController::class);
    Route::resource('agency_join_requests', 'AgencyJoinRequestController');
    Route::resource('requests-for-get-salary', 'GetSalaryRequestController');
    Route::resource('requests-for-get-salary-history', 'GetSalaryRequestFilterationController');
    Route::resource('family_levels', 'FamilyLevelController');
    Route::resource('silver', 'SilverController');
    Route::resource('coins', 'CoinController');
    Route::resource('tickets', 'TicketController');
    Route::resource('pages', 'PageController');
    Route::resource('exchanges', 'ExchangeController');
    Route::resource('reports', 'ReportController');
    Route::resource('charges-reports', 'ChargeReportController');
    Route::resource('sallaries', 'SallariesController')->name('index', 'sallaries');
    Route::resource('total-statistics', 'AllStatisticController');
    Route::resource('coin-reports', 'CoinReportController');
    Route::resource('ban-types', BanTypeController::class);
    Route::resource('sallaries_history', 'SallariesHistoryController');
    // Route::resource ('export-excel','ImportExcelReportController');
    Route::resource('report_users', ReportFromUsersController::class);
    // REMOVED (2026-08-16): ReportController has no cashing() method (same as admin).
    Route::resource('trxs', 'CoinLogController');
    Route::resource('images', 'ImageController');
    Route::resource('trashed-users', TrashedUserAccountController::class);
    Route::resource('withdraw-types', WithdrawController::class);
    Route::resource('room-vips', RoomVipController::class);
    Route::resource('room-target', RoomTargetController::class);

    // Route::resource('agencyMangLink', AgencyMangerLinkController::class);

    Route::prefix('ag')->name('agency.')->namespace('AgencyControllers')->group(function () {
        Route::get('/', 'HomeController@infoBox')->name('home');
        Route::get('/users', 'UserController@index')->name('users');
        Route::get('/userTarget', 'UserTargetController@index')->name('userTarget');
        Route::get('/target', 'AgencyTargetController@index')->name('targets');
        Route::get('/charges', 'ChargeController@index')->name('charges');
        Route::resource('/ag-req', 'AgencyJoinRequestController');
    });

    Route::prefix('ch')->name('charger.')->namespace('ChargerControllers')->group(function () {
        Route::get('/', 'HomeController@infoBox')->name('home');
        Route::get('/charges', 'ChargeController@index')->name('charges');
    });

    Route::resource('/wares_dedicate', 'DedicateWareController')->only('index', 'create', 'store');
    Route::get('/vips_dedicate', 'DedicateVipController@index');
    Route::resource('/bans', 'BanController');
    Route::resource('/request-background-image', 'RequestBackgroundImageController');
    Route::resource('/group-chat', 'GroupChatController');
    Route::get('/custom-page', [AppSitiingCOnfigController::class, 'index'])->name('admin.AppSitiingCOnfigController');
    Route::resource('core-wallets', CoreWalletsController::class);
    Route::resource('appear-charger-agency', AppearChargerAgencyController::class);

    //    dd( Admin::menu(function ($menu) {
    //         $menu->add('Custom Page', ['route' => 'admin.AppSitiingCOnfigController'])
    //             ->icon('fa-file');
    //     }));

    Route::get('/custom-page', [AppSitiingCOnfigController::class, 'index'])->name('admin.AppSitiingCOnfigController');
    Route::resource('admin-users', AdminUsersController::class);
    Route::resource('parent-users', ParentUsersController::class);
    Route::get('admin-users/{id}/{agency}', 'AdminUsersController@show2');
    Route::get('percentage-target', [TargetPercentageController::class, 'index'])->name('percentage-target');
    Route::get('convert-is_gold', function () {
        $users = User::where("is_gold_id", 1)->get();
        foreach ($users as $user) {
            $user->image_color_id = 1;
            $user->save();
        }
        dD("goold");
    });
    Route::get('background-count', function () {
        $backgrounds = Background::get();
        if ($backgrounds) {
            foreach ($backgrounds as $background) {
                $background_count = Room::where("room_background", $background->id)->count();
                $background->use_count = $background_count;
                $background->save();
            }
            //dd("done");
        }
        //dd("note found data");
    });


    Route::resource('banners', BannerController::class);

    // module achievement

    /*  Route::resource('achievements', AchievementsController::class);
    Route::post('/store-user-achievement', [AchievementLevelsModuleController::class, 'store'])->name('store-user-achievement');
    Route::get('/get-achievement-levels/{achievementId}', [AchievementLevelsModuleController::class,'getAchievementLevels'])->name('get-achievement-levels');
    Route::get('/get-view-page', [AchievementLevelsModuleController::class,'viewPage'])->name('get-view-page');
   Route::resource('user-achievement-levels', UserAchievementLevelController::class);
  // Route::post('postAddGiftAchievementLevel', [GiftAchievemntController::class,'postAddGiftAchievementLevel'])->name('postAddGiftAchievementLevel');
   Route::get('achievement-levels/create/{id}', 'AchievementsLevelsController@create')->name('achievement-levels.create');
    Route::resource('gift-achievements','UserGiftAchController');
    Route::resource('gift-achievment', 'GiftAchiementController');
    Route::post('postAddGiftAchievement', [GiftAchievemntController::class,'postAddGiftAchievemnt'])->name('postAddGiftAchievement');
    Route::post('postAddGiftAchievementLevel', [GiftAchievemntController::class,'postAddGiftAchievementLevel'])->name('postAddGiftAchievementLevel');
    Route::post('posteditGiftAchievementLevel', [GiftAchievemntController::class,'posteditGiftAchievementLevel'])->name('posteditGiftAchievementLevel');
    Route::resource('achievement-levels', AchievementsLevelsController::class,['name'=>['create'=>'create2']]);*/
});
