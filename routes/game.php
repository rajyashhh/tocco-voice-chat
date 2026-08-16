<?php

use App\Http\Controllers\Api\V1\InteractiveGameController;
use App\Http\Controllers\Api\V1\LeaderCCgameController;
use App\Http\Controllers\Api\V1\NewLeaderCCGameController;

Route::middleware(['auth:sanctum', 'checkLatestToken', 'userBan', 'ip', 'generalBan', 'update.last.seen'])
    ->group(function () {

        // App -> Game control for INTERACTIVE (mic-seat) games. The room owner
        // lists/opens/ends games from inside the room (Games room mode). All
        // provider config is panel-driven (GameProviderSetting / QuantumNexusClient).
        Route::prefix('interactive-game')->group(function () {
            Route::get('list', [InteractiveGameController::class, 'gameList']);
            Route::post('open', [InteractiveGameController::class, 'open']);
            Route::post('end', [InteractiveGameController::class, 'end']);
            Route::post('close', [InteractiveGameController::class, 'close']);
            Route::post('restart', [InteractiveGameController::class, 'restart']);
            Route::post('room-info', [InteractiveGameController::class, 'roomInfo']);
        });
    });

Route::post('update-room-count-zego', [\App\Http\Controllers\Api\V1\Room\EnteranceController::class, 'updateRoomCountFromStream2']);
//Route::post('update-room-count-zego-2', [\App\Http\Controllers\Api\V1\Room\EnteranceController::class, 'updateRoomCountFromStream2']);


Route::prefix('leader-cc-game')
    ->withoutMiddleware([\App\Http\Middleware\LogApiRequestResponse::class])
    ->middleware(['verify.game.signature', \App\Http\Middleware\MeasureRequestTimeMiddleware::class])
    ->group(function () {

        Route::post('get-user-info', [LeaderCCgameController::class, 'userInformation']);
        Route::post('change-balance', [LeaderCCgameController::class, 'updateGameCoin']);
        Route::post('make-up-orders', [LeaderCCgameController::class, 'makeUpOrders']);
    });


Route::prefix('webhook')
    ->group(function () {

        // Section 1: Generate game launch URL (requires authenticated user)
        Route::post('url-games', [NewLeaderCCGameController::class, 'urlGames'])->middleware(['auth:sanctum']);


        // Provider-to-server game webhooks. Signature is enforced per-endpoint
        // inside the controller (verifySign), and this group adds a panel-driven
        // IP allowlist + optional timestamp/nonce replay gate on top.
        Route::middleware('verify.quantum.webhook')->group(function () {
            Route::post('mic-seats', [NewLeaderCCGameController::class, 'usersUpMic']);
            Route::post('user-info', [NewLeaderCCGameController::class, 'userInfo']);
            Route::post('sit-down', [NewLeaderCCGameController::class, 'sitDown']);
            Route::post('stand-up', [NewLeaderCCGameController::class, 'standUp']);
            Route::post('game-start', [NewLeaderCCGameController::class, 'gameStart']);
            Route::post('game-end', [NewLeaderCCGameController::class, 'gameEnd']);
        });
    });
