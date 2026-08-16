<?php

use App\Helpers\Common;
use App\Models\Room;
use Illuminate\Http\Request;
use Modules\LuckyBox\Http\Controllers\BoxController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware(['auth:sanctum', 'checkLatestToken', 'generalBan', 'userBan' ,'update.last.seen'])->group(
    function () {
        Route::prefix('box')->group(function () {
            Route::get('list', [BoxController::class, 'index']);
            Route::post('send', [BoxController::class, 'send']);
            Route::post('send_test', [BoxController::class, 'testSendSuperBoxes']);
            Route::post('pickup', [BoxController::class, 'pickBox']);
        });
    }
);





// Debug/test tool that pushes stream custom commands into any room. Loaded under
// the stateless 'api' group, so it needs the full web+admin stack (session +
// admin.auth + admin.rbac) rather than the bare 'admin' alias used inside web.php.
Route::middleware(['web', 'admin'])->get('/test-room-stream', function (Request $request) {

    $roomId = $request->query('room_id');

    if (!$roomId) {
        return response()->json([
            'status' => 0,
            'message' => 'room_id is required',
        ], 422);
    }
    $room =Room::find($roomId);

    $payload = [
        'messageContent' => [
            'message' => 'hideluckybox',
            'ownerBoxId' => $room->uid,
            'ownerBoxName' => 'test',
            'boxCoins' => 'test',
            'boxId' => $roomId,
            'boxType' =>  'normal',
            'numOfBoxes' => 'remaining',
        ],
    ];

    $json = json_encode($payload);

    $payload2 = [
        'messageContent' => [
            'message' => 'winnerLuckyBox',
            'boxUId' => $room->uid,
            'ownerId' => $room->uid,
            'ownerName' =>  '',
            'ownerImage' =>  '',
            'ownerUuId' => 1,
            'winners' =>[],
        ],
    ];
    $json2 = json_encode($payload2);

    try {


        Common::sendToStream('SendCustomCommand', $roomId, 1, $json);
        Common::sendToStream('SendCustomCommand', $roomId, 1, $json2);



        return response()->json([
            'status' => 1,
            'message' => 'Stream test message sent successfully',
            'room_id' => $roomId,
            'payload' => $payload,
            'payload2' => $payload2,
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => 0,
            'message' => 'Failed to send test message',
            'error' => $th->getMessage(),
        ], 500);
    }
});
