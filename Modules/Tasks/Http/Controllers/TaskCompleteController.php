<?php

namespace Modules\Tasks\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Day;
use App\Models\DailyTask;
use Illuminate\Http\Request;
use Modules\Tasks\Entities\DailyTask as EntitiesDailyTask;
use Modules\Tasks\Entities\Day as EntitiesDay;
use Modules\Tasks\Entities\TaskReward;
use Modules\Tasks\Entities\UserDayTaskProgress;
use Modules\Tasks\Entities\UserTaskReward;
use Modules\DailyPrize\Http\Controllers\Api\DailyGiftController;
use Illuminate\Support\Facades\DB;
use Modules\Tasks\Services\TaskService;

class TaskCompleteController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }
    public function collectTaskPoints($taskId, Request $request, DailyGiftController $dailyGiftController)
    {
        try {
            $userId = $request->user()->id;
            $result = $this->taskService->collectTaskPoints($taskId, $userId);
            return $result;
        } catch (\Exception $e) {
            return Common::apiResponse(false,$e->getMessage(),null,500);
        }
    }
}




