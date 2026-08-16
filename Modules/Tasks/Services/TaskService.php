<?php

namespace Modules\Tasks\Services;

use Illuminate\Support\Facades\DB;
use Modules\DailyPrize\Http\Controllers\Api\DailyGiftController;
use App\Helpers\Common;
use App\Repositories\User\UserRepository;
use Modules\Tasks\Repositories\DailyTaskRepository;
use Modules\Tasks\Repositories\DayRepository;
use Modules\Tasks\Repositories\TaskProgressRepository;
use Modules\Tasks\Repositories\TaskRewardRepository;
use Modules\Tasks\Repositories\UserDayProgressRepository;
use Modules\Tasks\Repositories\UserTaskRewardRepository;

class TaskService
{
    protected $dailyTaskRepo;
    protected $taskProgressRepo;
    protected $taskRewardRepo;
    protected $dayRepo;
    protected $dailyGiftController;
    protected $userTaskRewardRepo;
    protected $userRepository;
    protected $userDayProgressRepository;

    public function __construct(
        DailyTaskRepository $dailyTaskRepo,
        TaskProgressRepository $taskProgressRepo,
        TaskRewardRepository $taskRewardRepo,
        DayRepository $dayRepo,
        DailyGiftController $dailyGiftController,
        UserTaskRewardRepository $userTaskRewardRepo,
        UserRepository $userRepository,
        UserDayProgressRepository $userDayProgressRepository
    ) {
        $this->dailyTaskRepo = $dailyTaskRepo;
        $this->taskProgressRepo = $taskProgressRepo;
        $this->taskRewardRepo = $taskRewardRepo;
        $this->dayRepo = $dayRepo;
        $this->dailyGiftController = $dailyGiftController;
        $this->userTaskRewardRepo= $userTaskRewardRepo;
        $this->userRepository=$userRepository;
        $this->userDayProgressRepository=$userDayProgressRepository;
    }

    public function collectTaskPoints($taskId, $userId)
    {
        DB::beginTransaction();
        try {
            //$response = DB::transaction(function () use ($taskId, $userId) {
                $task = $this->dailyTaskRepo->findOrFail($taskId);
                $taskProgress = $this->taskProgressRepo->getAll(['user_id' => $userId,'task_id' => $taskId])->first(); //findUserTaskProgress($userId, $taskId);
                //$day = $this->dayRepo->findOrFail($task->day_id);
                $userDayProgressRow=$this->userDayProgressRepository->getAll(['user_id' => $userId,'day_id' => $task->day_id])->first();

                if (!$taskProgress) {
                    DB::rollBack();
                    return Common::apiResponse(false, 'Task progress not found', null, 404);
                }

                if ($taskProgress->is_completed||$taskProgress->is_collect) {
                    DB::rollBack();
                    return Common::apiResponse(true, 'Task progress already collected', null, 200);
                }

                if ($taskProgress->count == $task->count) {
                    $taskProgress->is_completed = true;
                    $taskProgress->is_collect = true;
                    
                    $this->taskProgressRepo->save($taskProgress);

                    $user = $this->userRepository->findOrFail($userId);
                    $user->total_points += $task->total_points;
                    $user->save();
                    if($userDayProgressRow)
                    {
                        $userDayProgressRow->points+=$task->total_points;
                        $this->userDayProgressRepository->save($userDayProgressRow);
                    }
                    else 
                    {
                        $this->userDayProgressRepository->create([
                            'user_id'=>$userId,
                            'day_id'=>$task->day_id,
                            'points'=>$task->total_points,
                            'is_completed'=>false,
                            'get_rewards'=>false,
                            'created_at'=>now()
                        ]);
                        //$this->userDayProgressRepository->save($userDayProgressRow);
                    }
                }

                //$dayTasks = $this->dailyTaskRepo->getAll(['day_id' => $task->day_id]);//findByDayId($task->day_id);
                $allTasksCompleted = $this->areAllTasksCompleted($task->day_id, $userId);

                
                /*if($day->is_unlocked)
                {
                    DB::rollBack();
                    return Common::apiResponse(true,'day already unlocked and rewards assigned to user',null,200);
                }*/

                if ($allTasksCompleted) {
                    $userDayProgressRow->is_completed=true;
                    $this->userDayProgressRepository->save($userDayProgressRow);
                    $this->unlockDayAndAssignRewards($task->day_id, $userId);
                }
                if($allTasksCompleted)
                {
                    $rewards = $this->taskRewardRepo->getAll(['day_id' => $task->day_id]);//findByDayId($task->day_id);//TaskReward::where('day_id', $task->day_id)->get();
                    DB::commit();
                    return Common::apiResponse(true, 'Points collected successfully and the day is completed successfully', [
                        'total_points' => $user->total_points,
                        'rewards' => $rewards
                    ], 200);
                }
                else 
                {
                    DB::commit();
                    return Common::apiResponse(true, 'Points collected successfully', [
                        'total_points' => $user->total_points,
                    ], 200);
                }
            //});

            //return $response;
        } catch (\Exception $e) {
            DB::rollBack();
            return Common::apiResponse(false, $e->getMessage(), null, 500);
        }
    }

    /*private function areAllTasksCompleted($dayTasks, $userId)
    {
        foreach ($dayTasks as $dayTask) {
            $userTaskProgress = $this->taskProgressRepo->getAll(['user_id' => $userId,'task_id' => $dayTask->id])->first();//->findUserTaskProgress($userId, $dayTask->id);
            if (!$userTaskProgress || !$userTaskProgress->is_completed) {
                return false;
            }
        }
        return true;
    }*/
    private function areAllTasksCompleted($dayId, $userId)
    {
        $tasks = $this->dailyTaskRepo->getAll(['day_id' => $dayId]);
        foreach ($tasks as $task) {
            $progress = $this->taskProgressRepo->getAll(['user_id' => $userId, 'task_id' => $task->id])->first();
            if (!$progress || !$progress->is_completed) {
                return false;
            }
        }
        return true;
    }


    private function unlockDayAndAssignRewards($dayId, $userId)
    {
        $day = $this->dayRepo->findOrFail($dayId);
        if($day)
        {
            $day->get_rewards = true;
            $this->dayRepo->save($day);
        }
        $nextDay = $this->dayRepo->getNextDay($dayId);
        if ($nextDay) {
            $nextDay->is_unlocked = true;
            $this->dayRepo->save($nextDay);
        }
        /*if ($day && !$day->is_unlocked) {
            $day->is_unlocked = true;
            $this->dayRepo->save($day);
        }*/
        $user = $this->userRepository->findOrFail($userId);
        $rewards = $this->taskRewardRepo->getAll(['day_id' => $dayId]);//->findByDayId($dayId);
        foreach ($rewards as $reward) {
            if (!$this->userTaskRewardRepo->getAll(['user_id' => $userId,'task_reward_id' => $reward->id])->first()){//userHasReward($userId, $reward->id)) {
                $this->dailyGiftController->assignGiftToUser(
                    $reward->type, 
                    $user,
                    $reward->target, 
                    $reward->expire
                );

                $this->userTaskRewardRepo->create([//->createUserReward([
                    'user_id' => $userId,
                    'task_reward_id' => $reward->id,
                    'created_at' => now(),
                ]);
            }
        }
    }
}