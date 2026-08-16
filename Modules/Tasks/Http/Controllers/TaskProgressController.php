<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Day;
use App\Models\DailyTask;
use Illuminate\Http\Request;
use Modules\Tasks\Entities\DailyTask as EntitiesDailyTask;
use Modules\Tasks\Entities\Day as EntitiesDay;
use Modules\Tasks\Services\TaskProgressService;

class TaskProgressController extends Controller
{
    protected $taskProgressService;

    public function __construct(TaskProgressService $taskProgressService)
    {
        $this->taskProgressService = $taskProgressService;
    }

    public function getDays(Request $request)
    {
        $userId = auth()->id();
        $response = $this->taskProgressService->getDays($userId);
        return $response;
    }
}




