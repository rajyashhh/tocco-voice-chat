<?php

namespace Modules\Achievement\Tests\Unit;

use App\Models\Gift;
use App\Models\User;
use Modules\Achievement\Entities\Achievement;
use Modules\Achievement\Entities\AchievementLevel;
use Modules\Achievement\Entities\UserAchievement;
use Modules\Achievement\Enums\AchievementType;
use Modules\Achievement\Enums\TargetType;
use Modules\Achievement\Http\Services\AchievementLevelsService;
use Modules\Achievement\Http\Services\UserAchievementService;
use Tests\TestCase;

class AchievementLevelsServiceTest extends TestCase
{

    public function testInsertGiftData()
    {
        $collection = collect([
                                  ['id' => 1, 'name' => 'John'],
                                  ['id' => 2, 'name' => 'Alice'],
                                  ['id' => 3, 'name' => 'Bob'],
                              ]);

        $orderArray = [ 2, 3, 1];

        $sortedCollection = array_search(0, $orderArray);
        //dd($sortedCollection );
        $sortedCollection = $collection->sortBy(function ($item) use ($orderArray) {
            return array_search($item['id'], $orderArray);
        })->last();


        /*$collection1 = collect([
                                   ['id' => 1, 'name' => 'John'],
                                   ['id' => 3],
                               ]);

        $collection2 = collect([
                                   ['id' => 3, 'name' => 'Bob',  'age' => 12],
                                   ['id' => 4, 'name' => 'Eve'],
                               ]);

        // Merge and append keys
        $mergedCollection = $collection1->concat($collection2)->mapWithKeys(function ($item) {
            return [$item['id'] => $item];
        });

        // Resulting merged collection
        dd($mergedCollection);*/
//        $achievementLevelsService = new AchievementLevelsService();
        $achievementLevelsService = new AchievementLevelsService();
//        $achievementLevelsService->checkIfComingLevelGreaterThanExists(1, 1, [1, 2, 3]);
        $userAchievementService = new UserAchievementService();
        $user = User::first();

        $userAchievementService->insertCharging($user, 200);
        /*$gift = Gift::find(109);

//        $achievementLevelsService->insertGiftData($user, $gift, 22);
        $achievementLevel = AchievementLevel::query()->orderByDesc('id')->first();

//        $achievement = Achievement::query()->where('type', AchievementType::RECHARGE_TARGET)->first();
        $user = User::find(2160);

        $achievementLevelsService->insertCharging( $user, 200);
*/

      /*  Achievement::query()->create([
            'type' => AchievementType::GIFT_TARGET,
            'valid_image'=> 'images/test.png',
            'invalid_image' => 'images/test-invalid.png',
                                     ]);*/



    }
}
