<?php

use KevinSoft\MultiLanguage\MultiLanguage;

use Modules\Region\Http\Controllers\BdController;
use Modules\Region\Http\Controllers\AuthController;
use Modules\Region\Http\Controllers\HomeController;
use Modules\Region\Http\Controllers\MultiLanguageController;
use Modules\Region\Http\Controllers\RoleController;
use Modules\Region\Http\Controllers\RoomController;
use Modules\Region\Http\Controllers\UserController;
use Modules\Region\Http\Controllers\AgencyController;
use Modules\Region\Http\Controllers\ChargeController;
use Modules\Region\Http\Controllers\WalletController;
use Modules\Region\Http\Controllers\LiveRoomController;
use Modules\Region\Http\Controllers\AdminUserController;
use Modules\Region\Http\Controllers\AgencyUserController;
use Modules\Region\Http\Controllers\BdSalariesController;
use Modules\Region\Http\Controllers\SuperAdminController;
use Modules\Region\Http\Controllers\AdminRewardController;
use Modules\Region\Http\Controllers\ProfessionalBdController;
use Modules\Region\Http\Controllers\OfficialMessageController;
use Modules\Region\Http\Controllers\AppearChargerAgencyController;
use Modules\Region\Http\Controllers\DedicateRewardHistoryController;
use Modules\Region\Http\Controllers\Admin\AreaManagerChargeController;
use Modules\Region\Http\Controllers\Admin\AreaManagerChargeReportController;
use Modules\Region\Http\Controllers\Admin\AreaManagerController as AdminAreaManagerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*============================= DASHBOARD ROUTE THAT SPECIAL owner DASH ==============================*/

Route::group(
    [
        'prefix' => config('admin.route.prefix'),
        'namespace' => 'Modules\\Region\\Http\\Controllers\\Admin',
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
        Route::resource('area-manager-users', AdminAreaManagerController::class);
        Route::get('show-sub-area-manager/{id}', [AdminUserController::class, 'showSubSuperAdmin']);
        Route::post('delete-sub-admin/{id}', [AdminUserController::class, 'deleteSubSuperAdmin']);
        Route::post('update-area-manager', [AdminUserController::class, 'updateSubSuperAdmin']);
        Route::get('area-manager-users/profile', [AdminAreaManagerController::class, 'showPreview']);
        Route::get('area-manager-charges', [AreaManagerChargeController::class, 'index']);
        Route::group(['prefix' => 'area-manager-charges-report'], function () {
            Route::get('/{id}', [AreaManagerChargeReportController::class, 'index']);
        });
    }
);

/*============================= DASHBOARD ROUTE THAT SPECIAL AREA MANGER DASH ==============================*/
Route::group(
    [
        'prefix' => 'areaManager',
        'namespace' => '',
        'middleware' => [
            'web',
            'multiLanguage',
        ],
        'as' => 'areaManager.',
    ],
    function () {
        if (MultiLanguage::config("show-login-page", true)) {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        }
        Route::post('/locale', MultiLanguageController::class . '@locale');
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
        'prefix' => 'areaManager',
        'namespace' => 'Modules\\Region\\Http\\Controllers',
        'middleware' => [
            'web',
            'admin.auth',
            'portal.type:region,sub_region',
            'admin.pjax',
            'admin.log',
            'admin.bootstrap',
            // 'adminIp',
            //            'adminGeneralBan',
            'multiLanguage',
        ],
        'as' => 'areaManager.',
    ],
    function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('area-manager-users/profile/{id}', [AdminAreaManagerController::class, 'showProfile']);
        Route::get('area-manager-users/{id}', [AdminAreaManagerController::class, 'showProfile']);
        Route::get('sub-area-manager-users/profile/{id}', [AdminUserController::class, 'showProfile']);
        Route::get('show-sub-area-manager', [AdminUserController::class, 'showSubSuperAdmin']);
        Route::post('update-area-manager', [AdminUserController::class, 'updateSubSuperAdmin']);
        Route::post('delete-sub-admin/{id}', [AdminUserController::class, 'deleteSubSuperAdmin']);
        Route::resource('superadmin-users', SuperAdminController::class);
        Route::get('superadmin-users-profile/{id}', [SuperAdminController::class, 'profile']);
        Route::get('users/profile/{id}', [UserController::class, 'show'])->name('user.profile');
        Route::resource('/bd-salaries', BdSalariesController::class);
        Route::resource('user-Bds', BdController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('auth-users', AdminUserController::class);

        //        //agencies
        Route::resource('/agencies', AgencyController::class);
        Route::resource('charge-agencies', AppearChargerAgencyController::class)->middleware('web-agency-feature');
        Route::get('profile-shipping-agency/{id}', [AppearChargerAgencyController::class, 'shippingProfile'])->name('shipping.agency.profile');
        Route::get('profile-agency/{id}', [AgencyController::class, 'profile'])->name('agency.profile');
        //        Route::resource('/request-agencies', RequestAgencyController::class);
        Route::prefix('ag')->name('agency.')->middleware('web-agency-feature')->group(function () {
            Route::resource('users', AgencyUserController::class);
            Route::get('professional/users', [AgencyUserController::class, 'indexProfessionals']);
        });
        Route::resource('live-rooms', LiveRoomController::class);
        Route::resource('official-message', OfficialMessageController::class);

        //        //users
        Route::resource('users', 'UserController', [
            'names' => [
                'index' => 'users',
                'show' => 'users.show'
            ]
        ]);
        Route::resource('rewards', AdminRewardController::class);
        Route::get('search/super-admin', [AdminRewardController::class, 'getSuperAdmins'])->name('super-admin');

        Route::resource('rewards-history', DedicateRewardHistoryController::class);
        //
        Route::resource('rooms', RoomController::class);

        Route::get('users/{id}/same-device-users-table', [UserController::class, 'ajaxSameDeviceUsersTable']);
        //
        Route::get('/charges', [ChargeController::class, 'index']);
        Route::post('wallet/charge', [WalletController::class, 'charge'])->name('wallet.charge');

        Route::get('rooms-activity', [HomeController::class, 'roomsActivity'])->name('admin.rooms-activity');
        Route::resource('professional-bd', ProfessionalBdController::class);
        //
        //        // ajax
        Route::get('peak-hours', [HomeController::class, 'peakHours'])->name('admin.peak-hours');
        Route::get('users-online-stats', [HomeController::class, 'onlineStats'])->name('users.online.stats');
        Route::get('top-users-visits', [HomeController::class, 'topUsersVisits'])->name('top-users-visits');
        Route::get('/sub-area-managers', [ChargeController::class, 'subAreaManagers'])->name('sub.admins');

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
    }
);

Route::prefix('areaManager')->name('areaManager.')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

/*============================= End DASHBOARD ROUTE THAT SPECIAL AREA MANGER DASH ==============================*/
