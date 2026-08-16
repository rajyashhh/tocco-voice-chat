<?php

namespace Modules\TaskStream\Http\Controllers;

use App\Helpers\Common;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\TaskStream\Services\TaskStreamService;
use Modules\TaskStream\Transformers\LiveFriendsResource;
use Modules\TaskStream\Transformers\TaskStreamResource;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TaskStreamController extends Controller
{
    public function __construct(private readonly TaskStreamService $taskStreamService)
    {
    }

    public function index(): JsonResponse
    {
        $result = $this->taskStreamService->index();

        return Common::apiResponse(true, '', TaskStreamResource::collection($result));
    }

    public function liveFriends(): JsonResponse
    {
        $result = $this->taskStreamService->liveFriends();

        return Common::apiResponse(true, 'done', LiveFriendsResource::collection($result));
    }

    /**
     * @throws \Exception
     */
    public function store(): JsonResponse
    {
        $result = $this->taskStreamService->store();

        return Common::apiResponse(true, '', TaskStreamResource::make($result), ResponseAlias::HTTP_CREATED);
    }

    /**
     * @throws \Exception
     */
    public function join(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_stream_id' => ['required', 'integer', Rule::exists('task_streams', 'id')],
        ]);

        $result = $this->taskStreamService->join($data);

        return Common::apiResponse(true, '', TaskStreamResource::make($result));
    }

    /**
     * @throws \Exception
     */
    public function leave(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_stream_id' => ['required', 'integer', Rule::exists('task_streams', 'id')],
        ]);

        $result = $this->taskStreamService->leave($data);

        return Common::apiResponse(true, '', TaskStreamResource::make($result));
    }

    /**
     * @throws \Exception
     */
    public function sendInvitation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_stream_id' => ['required', 'integer', Rule::exists('task_streams', 'id')],
            'invitee_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'battle_data' => ['sometimes', 'array'],
        ]);

        $result = $this->taskStreamService->sendInvitation($data);

        return Common::apiResponse(true, __('sent successfully'), [
            'invitee_room_id' => $result['room_id'],
            'invitee_user_id' => $data['invitee_user_id'],
            'invitee_user_name' => $result['user_name'],
            'invitee_user_image' => $result['user_image'],
        ]);
    }

    /**
     * @throws \Exception
     */
    public function respondInvitation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_stream_id' => ['required', 'integer', Rule::exists('task_streams', 'id')],
            'status' => ['required', 'string', Rule::in(['accept', 'reject'])],
        ]);

        $result = $this->taskStreamService->respondInvitation($data);

        return Common::apiResponse(true, '', TaskStreamResource::make($result));
    }
}
