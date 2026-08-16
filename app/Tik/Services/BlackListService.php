<?php

namespace App\Tik\Services;

use App\Models\Pack;
use Modules\Vip\Entities\UserVip;
use App\Tik\Repositories\BlackLisRepository;
use Modules\Events\Entities\WinnerReward;
use App\Http\Resources\UserReportResource;
use App\Http\Resources\ReportEventResource;
use Modules\Events\Entities\RewardWinnerPk;
use App\Http\Resources\AgencyReportResource;
use Modules\Events\Services\LoseWinnerRewards;
use App\Http\Resources\AdminUserReportResource;
use Modules\Achievement\Entities\UserAchievementLevel;

class BlackListService
{
    public function __construct(
        private readonly BlackLisRepository $Repository,
   
    ) {}


    public function list($key,$perPage,$page){
        return $this->Repository->list($key,$perPage,$page);
    }

    // public function search($key){
    //     return $this->Repository->search($key);
    // }
    // public function blocked_search($user_id,$key){
    //     return $this->Repository->blocked_search($user_id,$key);
    // }
    
    public function store(array  $data){
        return $this->Repository->store($data);
    }

    public function black_lists($userid,$key,$perPage,$page){
        return $this->Repository->black_lists($userid,$key,$perPage,$page);
    }

    public function delete($id){
        return $this->Repository->delete($id);
    }
    
}
