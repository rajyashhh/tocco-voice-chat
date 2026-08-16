<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Tik\Services\GroupChatService;
use App\Jobs\SendNotificationsToAllUsers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\GroupChatResource;
use Modules\Public\Http\Services\UpgradeLevelServices;

class GroupChatController extends Controller
{
    protected $groupChatService;

    public function __construct(GroupChatService $groupChatService)
    {
        $this->groupChatService = $groupChatService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $costGroupChat = Common::getConfig('group_chat');
        $user = $request->user();
        $data = $this->groupChatService->index($user);
        return response()->json(['price_message' => $costGroupChat, 'data' => GroupChatResource::collection($data, 200)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'sometimes|image|mimes:jpeg,png,gif,bmp,tiff,webp',
            'image_url' => 'sometimes|string|max:255',
            'text' => 'required',
            'message_id' => 'nullable|integer|exists:group_chat,id'
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }

        $costGroupChat = Common::getConfig('group_chat') ?? 11;
        if (!$costGroupChat) {
            return Common::apiResponse(0, 'not found params (group_chat) in config dashboard', null, 404);
        }

        $user = $request->user();

        if ($user->di < $costGroupChat) {
            return Common::apiResponse(0, 'Insufficient balance, please go to recharge!', null, 407);
        }

        $groupChat = $this->groupChatService->create($user, $request, $costGroupChat);

        (new UpgradeLevelServices())->sendWorldChat($user);
        // pusher notification
        $groupChatResource = new GroupChatResource($groupChat);
        $resourceData = $groupChatResource->toArray($request);
        try {
            event(new \Modules\Chat\Events\GroupChat($resourceData));
        } catch (\Throwable $th) {
           Log::error('GroupChatController: Failed to fire GroupChat event', [
               'error' => $th->getMessage(),
               'user_id' => $user->id
           ]);
        }

        try {


            switch ($request->message_type) {
                case 'reel':
                case 'share_room':
                case 'room':
                    dispatchJobToQueue(new \App\Jobs\SendShareGroupChatNotificationJob($user, $request->text, $resourceData), queueName: 'heavyProcessing');
 
                    break;
            
                default:
                    dispatchJobToQueue(new SendNotificationsToAllUsers($user, $request->text, $resourceData), queueName: 'heavyProcessing');
                    break;
            }
        } catch (\Throwable $th) {
            Log::error('GroupChatController: Failed to dispatch notification job', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
                'user_id' => $user->id,
                'message_type' => $request->message_type
            ]);
        }

        return Common::apiResponse(1, 'created done', $resourceData, 201);
    }
}
