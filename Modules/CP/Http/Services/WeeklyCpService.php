<?php

namespace Modules\CP\Http\Services;

use Exception;
use Modules\CP\Repositories\WeeklyCpRepository;
use Modules\Events\Entities\WeeklyStar;

class WeeklyCpService
{
    public function __construct(private readonly WeeklyCpRepository $weeklyCpRepository) {}

    public function perviousCpWinners()
    {

        $perviousWeeklyCpWinners = $this->weeklyCpRepository->perviousWeeklyCpWinners(limit: 5);

        if (! $perviousWeeklyCpWinners) {
            throw new Exception('there is not weekly cp');
        }

        return $perviousWeeklyCpWinners;
    }

    public function weeklyCpDetails()
    {
        $weeklyCp = $this->getCurrentWeeklyCp();
        if (! $weeklyCp) {
            throw new Exception('there is not weekly cp now');
        }
        $rule = $this->weeklyCpRepository->role();

        return [$weeklyCp, $rule];
    }

    public function topUsers()
    {
        $weeklyCp = $this->getCurrentWeeklyCp();
        if (! $weeklyCp) {
            throw new Exception('there is not weekly cp now');
        }

        $giftIds = $weeklyCp->gifts->pluck('id')->toArray();

        return $this->weeklyCpRepository->topUsers($giftIds, $weeklyCp);
    }

    public function topGiftedInEvent(WeeklyStar $weeklyCp)
    {
        if (! $weeklyCp->isCp()) {
            throw new Exception('This now cp event');
        }

        $giftIds = $weeklyCp->gifts->pluck('id')->toArray();
       
        return $this->weeklyCpRepository->topUser($giftIds, $weeklyCp);
    }

    public function topOnePreviousWeeklyCpd()
    {
        $perviousWeeklyCp = $this->weeklyCpRepository->perviousWeeklyCpTopWinner();
        if (! $perviousWeeklyCp) {
            throw new Exception('there is not weekly cp ');
        }

        return $perviousWeeklyCp->WeeklyCpWinners->first();
    }

    public function topOneCurrentWeeklyCp()
    {
        $currentWeeklyCp = $this->getCurrentWeeklyCp();
        if (! $currentWeeklyCp) {
            throw new Exception('there is not weekly cp ');
        }

        return $this->topGiftedInEvent($currentWeeklyCp);
    }

    public function userDetails($user)
    {
        $weeklyCp = $this->getCurrentWeeklyCp();
        if (! $weeklyCp) {
            throw new Exception('there is not weekly cp now');
        }
        $giftIds = $weeklyCp->gifts->pluck('id')->toArray();

        return [
            'total_price' => $this->weeklyCpRepository->userDetails($giftIds, $weeklyCp, $user->id),
            'cp_relation' => $this->weeklyCpRepository->userCP($user->id),

        ];
    }

    /**
     * @return mixed
     */
    public function getCurrentWeeklyCp()
    {
        return $this->weeklyCpRepository->currentWeeklyCp();
    }
}
