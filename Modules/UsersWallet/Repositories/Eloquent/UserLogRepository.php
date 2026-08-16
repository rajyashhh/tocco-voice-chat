<?php

namespace Modules\UsersWallet\Repositories\Eloquent;

use App\Tik\Repositories\AbstractRepository;
use Carbon\Carbon;


use App\Models\AllUserLog;
use Modules\UsersWallet\Entities\WalletLog;


class UserLogRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new AllUserLog());
    }


    public function index($userId, $type = null, $startDate = null, $endDate = null, $page = 1, $perPage = 10)
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end   = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfDay();

        return $this->model
            ->where('user_id', $userId)
            ->when($type == 'coin', function ($q) {
                $q->where('feature_type', 'coin');
            })
            ->when($type == 'profits', function ($q) {
                $q->where('feature_type', 'wallet');
            })
            ->when($type == 'diamonds', function ($q) {
                $q->where('feature_type', 'diamond');
            })
            ->when($start !== null && $end !== null, function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
