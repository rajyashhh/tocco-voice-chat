<?php

namespace Modules\Tasks\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Exception;
use Modules\Tasks\Repositories\DailyTaskRepository;
use Modules\Tasks\Repositories\TaskProgressRepository;
use Modules\Tasks\Services\DayTasksService;
use Request;

class DayTasksController extends Controller
{
    protected $dayTasksService;

    public function __construct(DayTasksService $dayTasksService)
    {
        $this->dayTasksService=$dayTasksService;
    }

    public function getDayTasks(Request $request,$dayId)
    {
        $userId=auth()->id();
        $response=$this->dayTasksService->getDayTasks($userId,$dayId);
        return $response;
    }
}