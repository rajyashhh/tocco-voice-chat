<?php

use KevinSoft\MultiLanguage\MultiLanguage;
use Modules\Country\Http\Controllers\Admin\SuperAdminController;
use Modules\Country\Http\Controllers\Admin\SuperadminBannerHistoryController;
use Modules\Country\Http\Controllers\Admin\SuperAdminStatisticController;
use Modules\Country\Http\Controllers\Admin\SuperAdminHomeCarouselController;
use Modules\Country\Http\Controllers\Admin\RestoreSuperAdminController;
use Modules\Country\Http\Controllers\Admin\SuperAdminSelectController;
use Modules\Country\Http\Controllers\Admin\SuperAdminRewardControllerHistory;
use Modules\Country\Http\Controllers\Admin\SuperadminBannerRequestController;
use Modules\Country\Http\Controllers\Admin\SuperAdminChargeController;
use Modules\Country\Http\Controllers\Admin\SuperAdminChargeReportController;
use Modules\Country\Http\Controllers\SuperAdminCountryController;
use Modules\Country\Http\Controllers\SuperAdminNotificationController;
use Modules\Country\Http\Controllers\SuperAdmin\AdminUserController;


/*
|--------------------------------------------------------------------------
| admin Routes
|--------------------------------------------------------------------------
|
*/


Route::get('/admin/superadmin-logout', [App\Admin\Controllers\AuthController::class, 'customSuperadminLogout'])->name('admin.superadmin.logout');
Route::get('/admin/superadmin-logout', [App\Admin\Controllers\AuthController::class, 'customSuperadminLogout'])->name('admin.superadmin.logout');
Route::post('/locale', [SuperAdminCountryController::class, 'locale'])->name('locale');

// Country preview + financial stats. These expose per-country financial data and
// must sit behind the same auth gate as the rest of this portal. Wrapped WITHOUT a
// prefix / 'as' override so the existing URLs and route names are preserved exactly.
Route::group(
    [
        'middleware' => [
            'web',
            'admin.auth',
            'portal.type:country,sub_country',
            'multiLanguage',
        ],
    ],
    function () {
        Route::get('/countries/{id}', [SuperAdminCountryController::class, 'index'])->name('countries.preview');
        // Main page route
        Route::get('/country/{id}', [SuperAdminCountryController::class, 'index2'])->name('country.show');
        // AJAX API route
        Route::get('country/{id}/stats', [SuperAdminCountryController::class, 'getStats'])->name('country.stats');
    }
);

Route::group(
    [
        'prefix' => config('admin.route.prefix'),
        'namespace' => 'Modules\\Country\\Http\\Controllers\\Admin',
        'middleware' => [
            'web',
            'admin',
            'adminIp',
            'multiLanguage',
            'admin.rbac',
        ],
        'as' => config('admin.route.prefix') . '.',
    ],
    function () {

        Route::prefix('superadmin')->name('superadmin.')->middleware('preview.superadmin')->group(function () {
            Route::get('/statistics', [SuperAdminStatisticController::class, 'index'])->name('statistic');
            Route::get('top-users-visits', [SuperAdminStatisticController::class, 'topUsersVisits'])->name('top-users-visits');
            Route::get('peak-hours', [SuperAdminStatisticController::class, 'peakHours'])->name('admin.peak-hours');
            Route::get('rooms-activity', [SuperAdminStatisticController::class, 'roomsActivity'])->name('admin.rooms-activity');
            Route::get('users-online-stats', [SuperAdminStatisticController::class, 'onlineStats'])->name('users.online.stats');
            Route::get('profile', [SuperAdminController::class, 'showPreview']);
            Route::get('home-carousel/history', [SuperadminBannerHistoryController::class, 'index'])->name('superadmin.home-carousel.history');
            Route::resource('home-carousel', SuperAdminHomeCarouselController::class);
            Route::post('home-carousel/resend-banner-request/{banner}', [SuperAdminHomeCarouselController::class, 'resendBannerRequest'])
                ->name('superadmin.banner.resend');
        });

        Route::resource('superadmin-users', SuperAdminController::class);
        Route::post('delete-sub-admin/{id}', [SuperAdminController::class, 'deleteSubSuperAdmin']);
        Route::resource('restore-super-admins', RestoreSuperAdminController::class);
        Route::resource('superadmin-users-settings', SuperAdminSelectController::class);

        Route::post('superadmin-users/make-default', [SuperAdminSelectController::class, 'makeDefault'])->name('make-superadmin-default');
        Route::get('superadmin-users/select', [SuperAdminSelectController::class, 'index'])->name('superadmin-users.select');

       Route::get('show-sub-super-admin/{id}', [AdminUserController::class,'showSubSuperAdmin']);
        Route::post('update-sub-super-admin', [AdminUserController::class,'updateSubSuperAdmin']);


        Route::post('/set-preview-superadmin', function () {
            session(['preview_superadmin' => true]);
        });

        Route::post('/unset-preview-superadmin', function () {
            session()->forget('preview_superadmin');
        });

        Route::resource('superadmin-banner-requests', SuperadminBannerRequestController::class);
        Route::post('superadmin-banner/{id}/approve', [SuperadminBannerRequestController::class, 'approve'])->name('superadmin-banner.approve');
        Route::post('superadmin-banner/{id}/reject', [SuperadminBannerRequestController::class, 'reject'])->name('superadmin-banner.reject');

        Route::get('superadmin-charges', [SuperAdminChargeController::class, 'index']);
        Route::group(['prefix' => 'superadmin-charges-report'], function () {
            Route::get('/{id}', [SuperAdminChargeReportController::class, 'index']);
        });
    }
);


/*
|--------------------------------------------------------------------------
| superadmin Routes
|--------------------------------------------------------------------------
|
*/


use Modules\Country\Http\Controllers\SuperAdmin\AgencyController;
use Modules\Country\Http\Controllers\SuperAdmin\AgencyUserController;
use Modules\Country\Http\Controllers\SuperAdmin\AppearChargerAgencyController;
use Modules\Country\Http\Controllers\SuperAdmin\AuthController;
use Modules\Country\Http\Controllers\SuperAdmin\BdController;
use Modules\Country\Http\Controllers\SuperAdmin\BdSalariesController;
use Modules\Country\Http\Controllers\SuperAdmin\ChargeController;
use Modules\Country\Http\Controllers\SuperAdmin\HandleController;
use Modules\Country\Http\Controllers\SuperAdmin\HomeCarouselController;
use Modules\Country\Http\Controllers\SuperAdmin\HomeController;
use Modules\Country\Http\Controllers\SuperAdmin\LiveRoomController;
use Modules\Country\Http\Controllers\SuperAdmin\MultiLanguageController;
use Modules\Country\Http\Controllers\SuperAdmin\NotificationController;
use Modules\Country\Http\Controllers\SuperAdmin\OfficialMessengerSuperAdminController;
use Modules\Country\Http\Controllers\SuperAdmin\ProfessionalBdController;
use Modules\Country\Http\Controllers\SuperAdmin\RequestAgencyController;
use Modules\Country\Http\Controllers\SuperAdmin\RoleController;
use Modules\Country\Http\Controllers\SuperAdmin\RoomController;
use Modules\Country\Http\Controllers\SuperAdmin\SuperadminBannerHistoryController as SuperadminBannerHistory;
use Modules\Country\Http\Controllers\SuperAdmin\SuperAdminRewardController;
use Modules\Country\Http\Controllers\SuperAdmin\UserController;
use Modules\Country\Http\Controllers\SuperAdmin\WalletController;


Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});


Route::group(
    [
        'prefix' => 'superadmin',
        'namespace' => '',
        'middleware' => [
            'web',
            'multiLanguage',
        ],
        'as' => 'superadmin.',
    ],
    function () {
        if (MultiLanguage::config("show-login-page", true)) {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        }
        Route::post('login', [AuthController::class, 'postLogin']);
        Route::get('logout', [AuthController::class, 'logout']);
        Route::Post('send-whatsapp-code', [AuthController::class, 'sendCodeWhatsapp']);
        Route::get('change-password-view', [AuthController::class, 'changePasswordView']);
        Route::get('verify-whatsapp-code', [AuthController::class, 'verifyWhatsappCode'])
            ->name('verify-whatsapp-code');

        Route::post('change-password', [AuthController::class, 'changePassword'])->name('superadmin.change-password');

        Route::post('send-whatsapp-code-preview', [AuthController::class, 'send_whatsapp_code_preview'])
            ->name('superadmin.send-whatsapp-code-preview');
    }
);

Route::group(
    [
        'prefix' => 'superadmin',
        'namespace' => 'Modules\\Country\\Http\\Controllers\\SuperAdmin',
        'middleware' => [
            'web',
            'admin.auth',
            'portal.type:country,sub_country',
            'admin.pjax',
            'admin.log',
            'admin.bootstrap',
            'multiLanguage',
        ],
        'as' => 'superadmin.',
    ],
    function () {
        Route::post('_handle_action_', [HandleController::class, 'handleAction'])->name('handle-action');
        
        Route::get('setting', [AuthController::class, 'getSetting']);
        Route::get('auth/setting', [AuthController::class, 'getSetting']);
        Route::put('update-setting', [AuthController::class, 'putSetting']);

        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::resource('/salaries', BdSalariesController::class);
        Route::resource('/charges', ChargeController::class);
        // Route::resource('/wallet', 'WalletController');
        Route::post('salary/transfer', [WalletController::class, 'transfer'])->name('salary.transfer');
        Route::post('/locale', MultiLanguageController::class . '@locale');

        Route::resource('usersBd', BdController::class);

        //agencies
        Route::resource('/agencies', AgencyController::class);
        Route::resource('charge-agencies', AppearChargerAgencyController::class)->middleware('web-agency-feature');
        Route::get('shipping-agencies/profile/{id}', [AppearChargerAgencyController::class, 'shippingProfile'])->name('shipping.agency.profile');
        Route::get('agencies/profile/{id}', [AgencyController::class, 'profile'])->name('agency.profile');
        Route::resource('/request-agencies', RequestAgencyController::class);
        Route::prefix('ag')->name('agency.')->middleware('web-agency-feature')->group(function () {
            Route::resource('users', AgencyUserController::class);
            Route::get('professional/users', [AgencyUserController::class, 'indexProfessionals']);
        });
        Route::resource('live-rooms', LiveRoomController::class);
        Route::resource('official-message', OfficialMessengerSuperAdminController::class);

        //users
        Route::resource('users', UserController::class, [
            'names' => [
                'index' => 'users',
                'show' => 'users.show'
            ]
        ]);


        Route::get('superadmin-profile/{id}', [SuperAdminController::class, 'profile']);
        Route::post('delete-sub-admin/{id}', [SuperAdminController::class, 'deleteSubSuperAdmin']);

        Route::resource('rooms', RoomController::class);
        Route::get('home-carousel/history', [SuperadminBannerHistory::class, 'index'])->name('home-carousel.history');

        Route::resource('home-carousel', HomeCarouselController::class);

        Route::get('users/profile/{id}', [UserController::class, 'show'])->name('user.profile');
        Route::get('users/{id}/same-device-users-table', [UserController::class, 'ajaxSameDeviceUsersTable']);

        Route::get('/charges', [ChargeController::class, 'index'])->name('charges');
        Route::post('wallet/charge', [WalletController::class, 'charge'])->name('wallet.charge');
        Route::get('/sub-admins', [ChargeController::class, 'getSubAdmins'])->name('sub.admins');

        Route::get('rooms-activity', [HomeController::class, 'roomsActivity'])->name('admin.rooms-activity');
        Route::resource('professional-bd', ProfessionalBdController::class);

        // ajax
        Route::get('peak-hours', [HomeController::class, 'peakHours'])->name('admin.peak-hours');
        Route::get('users-online-stats', [HomeController::class, 'onlineStats'])
            ->name('users.online.stats');
        Route::get('top-users-visits', [HomeController::class, 'topUsersVisits'])->name('top-users-visits');
        Route::resource('super-admin-rewards', SuperAdminRewardController::class);
        Route::post('banner-request/{banner}', [HomeCarouselController::class, 'storeBannerRequest']);
        Route::post('home-carousel/resend-banner-request/{banner}', [HomeCarouselController::class, 'resendBannerRequest'])
            ->name('banner.resend');

        Route::prefix('notifications')->group(function () {
            Route::get('count', [SuperAdminNotificationController::class, 'count']);
            Route::get('list', [SuperAdminNotificationController::class, 'list']);
            Route::post('mark-as-read/{id}', [SuperAdminNotificationController::class, 'markAsRead']);
            Route::post('mark-all-read', [SuperAdminNotificationController::class, 'markAllRead']);
            Route::get('grid', [NotificationController::class, 'index'])->name('notifications.grid');
        });

        Route::post('/save-fcm-token', function (Illuminate\Http\Request $request) {
            $user = auth()->user();
            $user->fcm_token = $request->token;
            $user->save();
            return response()->json(['status' => 'success']);
        });
        Route::resource('roles', RoleController::class);
        Route::resource('auth-users', AdminUserController::class);
        Route::get('show-sub-super-admin', [AdminUserController::class,'showSubSuperAdmin']);
        Route::post('update-sub-super-admin', [AdminUserController::class,'updateSubSuperAdmin']);
        Route::prefix('statistics')->name('statistics.')->group(function () {
            Route::get('top-users-data', [HomeController::class, 'topUsersData']);
            Route::get('comparison-user-signup', [HomeController::class, 'comparisonUserSignUp']);
            Route::get('distribution-rooms', [HomeController::class, 'distributionRooms']);
            Route::get('top-room-gifts', [HomeController::class, 'topRoomGifts']);
            Route::get('active-rooms', [HomeController::class, 'averageActiveRooms']);
            Route::get('agency-target', [HomeController::class, 'agencyTarget']);
            Route::get('top-sender', [HomeController::class, 'topSender']);
            Route::get('top-receiver', [HomeController::class, 'topReceiver']);
            Route::get('comparison-agencies-target', [HomeController::class, 'comparisonAgencyTarget']);
            Route::get('room-stats', [HomeController::class, 'roomStats']);
            Route::get('agency-stats', [HomeController::class, 'getStats']);
            Route::get('bd-stats', [HomeController::class, 'getBdStats']);
            Route::get('balance-data', [HomeController::class, 'getBalanceData']);
            Route::get('stats-data', [HomeController::class, 'getStatsData']);
            Route::get('top-followers', [HomeController::class, 'getTopFollowers']);
            Route::get('game-summary', [HomeController::class, 'gameSummary']);
            Route::get('game-top-games', [HomeController::class, 'gameTopGames']);
            Route::get('game-top-users', [HomeController::class, 'gameTopUsers']);
            Route::get('peak-hours', [HomeController::class, 'peakHours'])->name('owner.peak-hours');
            Route::get('rooms-activity', [HomeController::class, 'roomsActivity'])->name('owner.rooms-activity');
            Route::get('top-users-visits', [HomeController::class, 'topUsersVisits'])->name('top-users-visits');
            Route::get('users-online-stats', [HomeController::class, 'onlineStats'])->name('users.online.stats');
        });
        Route::prefix('dashboard')->group(function () {
            Route::get('/finance/cards', [HomeController::class, 'financeCards']);
            Route::get('/finance/tables', [HomeController::class, 'financeTables']);
            Route::get('/finance/chart', [HomeController::class, 'financeChartIndex']);
            Route::get('wallet-logs/ajax', [HomeController::class, 'ajaxWalletLogs'])->name('wallet-logs.ajax');
        });

        Route::get('/superadmin-logout', [AuthController::class, 'customSuperadminLogout'])->name('superadmin.logout');
        Route::get('/superadmin-logout', [AuthController::class, 'customSuperadminLogout'])->name('superadmin.logout');
    }
);
