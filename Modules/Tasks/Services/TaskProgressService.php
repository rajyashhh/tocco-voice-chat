<?php

namespace Modules\Tasks\Services;

use App\Helpers\Common;
use App\Models\User;
use App\Repositories\User\UserRepository;
use Modules\Tasks\Entities\Day;
use Modules\Tasks\Entities\DailyTask;
use Modules\Tasks\Repositories\DailyTaskRepository;
use Modules\Tasks\Repositories\DayRepository;
use Modules\Tasks\Repositories\TaskProgressRepository;
use Modules\Tasks\Repositories\UserDayProgressRepository;

class TaskProgressService
{
    protected $dayRepository;
    protected $userRepository;
    protected $dailyTaskRepository;
    protected $taskProgressRepository;
    protected $userDayProgressRepository;
    

    public function __construct(UserDayProgressRepository $userDayProgressRepository, DayRepository $dayRepository,UserRepository $userRepository,DailyTaskRepository $dailyTaskRepository,TaskProgressRepository $taskProgressRepository)
    {
        $this->dayRepository = $dayRepository;
        $this->userRepository= $userRepository;
        $this->dailyTaskRepository=$dailyTaskRepository;
        $this->taskProgressRepository=$taskProgressRepository;
        $this->userDayProgressRepository=$userDayProgressRepository;
        
    }

    /*public function getDays($userId)
    {
        try {
            $user = $this->userRepository->findUserById($userId);//User::findOrFail($userId);
            $totalPoints = $user->total_points;

            $days = $this->dayRepository->getAll(orderBy:['day_number' => 'asc']);//Day::orderBy('day_number')->get();

            //$userProgress = $this->taskProgressRepository->getAll(['user_id' => $userId]);

            $lastUnlockedDay = $days->where('is_unlocked', 1)->last();

            //$tasks = [];
            //if ($lastUnlockedDay) {
            //    $tasks = $this->dailyTaskRepository->getAll(['day_id' => $lastUnlockedDay->id]);//DailyTask::where('day_id', $lastUnlockedDay->id)->get();
            //}

            $tasks = [];
            if ($lastUnlockedDay) {
                $tasks = $this->dailyTaskRepository->getAll(['day_id' => $lastUnlockedDay->id])
                    ->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'day_id' => $task->day_id,
                            'type' => $task->type,
                            'sub_type' => $task->sub_type,
                            'count' => $task->count,
                            'total_points'=>$task->total_points,
                            'created_at'=>$task->created_at
                        ];
                    });
            }



            $days = $this->dayRepository->getAll(orderBy: ['day_number' => 'asc']);
            $userProgress = $this->taskProgressRepository->getAll(['user_id' => $userId]);

            $daysWithProgress = $days->map(function ($day) use ($userProgress) {
                $progress = $userProgress->firstWhere('day_id', $day->id);
                return [
                    'day_number' => $day->day_number,
                    'title' => $day->title,
                    'is_unlocked' => $day->is_unlocked,
                    'is_completed' => $progress ? $progress->is_completed : false,
                    'created_at' => $day->created_at
                ];
            });

            return Common::apiResponse(true, 'User progress fetched successfully.', [
                'total_points' => $totalPoints,
                'days' => $daysWithProgress,
                'tasks' => $tasks,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching user progress: ' . $e->getMessage());
            return Common::apiResponse(false, $e->getMessage(), null, 500);
        }
    }*/





    public function getDays($userId)
    {
        try {
            $user = $this->userRepository->findUserById($userId);
            $totalPoints = $user->total_points;

            $days = $this->dayRepository->getAll(orderBy: ['day_number' => 'asc']);

            $userProgress = $this->userDayProgressRepository->getAll(['user_id' => $userId]);

            $targetDay = null;

            $incompleteDay = $userProgress->firstWhere('is_completed', false);

            if ($incompleteDay) {
                $targetDay = $days->firstWhere('id', $incompleteDay->day_id);
            } else {
                $progressDayIds = $userProgress->pluck('day_id')->toArray();
                $targetDay = $days->firstWhere(function ($day) use ($progressDayIds) {
                    return !in_array($day->id, $progressDayIds);
                });
                if (!$targetDay) {
                    $targetDay = $days->first();
                }
            }

            $tasks = $this->dailyTaskRepository->getAll(['day_id' => $targetDay->id], orderBy: ['id' => 'asc'])
                ->map(function ($task) use ($userId) {//->map(function ($task) {
                    $progress = $this->taskProgressRepository->getByUserIdAndTaskId($userId, $task->id);
//$progress = $this->userDayTaskProgressRepository->getByUserIdAndTaskId($userId, $task->id);
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'day_id' => $task->day_id,
                        'type' => $task->type,
                        'sub_type' => $task->sub_type,
                        'count' => $task->count,
                        'total_points' => $task->total_points,
                        'is_completed' => $progress ? $progress->is_completed : false,
                        'is_collect'=> $progress ? $progress->is_collect : false,
                        'created_at' => $task->created_at
                    ];
                });

            $daysWithProgress = $days->map(function ($day) use ($userProgress) {
                $progress = $userProgress->firstWhere('day_id', $day->id);
                return [
                    'id'=>$day->id,
                    'day_number' => $day->day_number,
                    'title' => $day->title,
                    'is_unlocked' => $day->is_unlocked,
                    'is_completed' => $progress ? $progress->is_completed : false,
                    'get_rewards'=> $progress ? $progress->get_rewards : false,
                    'created_at' => $day->created_at,
                ];
            });

            return Common::apiResponse(true, 'User progress fetched successfully.', [
                'total_points' => $totalPoints,
                'days' => $daysWithProgress,
                'tasks' => $tasks, 
            ], 200);
        } catch (\Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 500);
        }
    }





    
    


}
