<?php

namespace Modules\Events\Transformers;

use App\Models\User;
use Modules\Events\Entities\UserChargeEvent;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TargetsResource extends JsonResource
{
    public $chargesSumAmount;
    public $obtainedCoinsSum;


    public function __construct($resource, $chargesSumAmount = 0, $obtainedCoinsSum = 0,)
    {
        parent::__construct($resource);
        $this->chargesSumAmount = ($chargesSumAmount < 0 ? 0 : $chargesSumAmount);
        $this->obtainedCoinsSum = ($obtainedCoinsSum < 0 ? 0 : $obtainedCoinsSum);
    }

    public function toArray($request): array
    {
        return $this->resource->transform(function ($item) {
            $userCharges = $this->chargesSumAmount + $this->obtainedCoinsSum;
            if (!$item->value) {
                $remaining = 0;
            } else {
                $remaining = $userCharges / $item->value;
            }
            $checkChargeEvent = UserChargeEvent::query()->where(["user_id" => auth()->user()->id, 'charge_event_id' => $item->id])->first();
            if ($remaining < 0) {
                $remaining = 0;
            }
            $users = $item->users->pluck('user_id')->toArray();
            $exist = in_array(Auth::id(), $users);
            $user = User::find(auth()->user()->id);
            return [
                'id'            => $item->id,
                'value'         => $item->value,
                'rewards'       => WeeklyStarGift::collection($item->rewards),
                'remaining'     => $user->type_user == 3 ? 0 : ($remaining >= 1 ? 1 : $remaining ?? 0),
                'can_received'  => $user->type_user == 3 ? false : ($remaining >= 1 ? ($checkChargeEvent == null ? true : false) : false),
                'user_coins'    => $user->type_user == 3 ? 0 :  $userCharges,
                'received' => $exist
            ];
        })->all();
    }
}
