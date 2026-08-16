<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class BoxUseResource extends JsonResource
{
    protected static bool $followMapPrimed = false;
    protected static ?int $viewerId = null;
    protected static array $followedMap = [];

    /**
     * Seed the batched is_follow lookup: $followedIds are the box owner ids the
     * viewer ($viewerId) already follows (status=1), fetched in ONE query by the
     * caller. Avoids the per-box Common::IsFollow query (N+1). Callers that do
     * NOT prime fall back transparently to the original per-item lookup, so
     * other render paths keep identical behavior.
     */
    public static function initializeFollowMap(?int $viewerId, array $followedIds): void
    {
        self::$followMapPrimed = true;
        self::$viewerId = $viewerId;
        self::$followedMap = array_fill_keys($followedIds, true);
    }

    /**
     * Mirrors Common::IsFollow semantics exactly. Uses the batched map only when
     * it was primed for THIS request's viewer; otherwise defers to
     * Common::IsFollow. Binding to the live viewer id self-invalidates a map left
     * over from a previous request on the same (Octane) worker, so untouched
     * callers and cross-request reuse both keep identical behavior.
     */
    protected function resolveIsFollow($request, int $targetId): bool
    {
        $viewerId = @$request->user()->id;

        if (!self::$followMapPrimed || self::$viewerId !== $viewerId) {
            return (bool) Common::IsFollow($viewerId, $targetId);
        }
        if (!self::$viewerId) return false;
        if (self::$viewerId == $targetId) return true;
        return isset(self::$followedMap[$targetId]);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // $startTime = Carbon::createFromTimestamp($this->start_at);
        // $currentTime = Carbon::now();
        // if ($startTime >= $currentTime) {
        //     $rem_time = $startTime->diffInSeconds($currentTime);
        // } else {
        //     $rem_time = 0;
        // }


        // $rem_time = Carbon::createFromTimestamp($this->start_at)->diffInSeconds(now());


        if (!$this->user instanceof User) return [];
        return [
            'id' => $this->id,
            'user' => [
                'id'        => $this->user->id,
                'uuid'      => $this->user->uuid,
                'image'     => $this->user?->profile?->avatar ?? '',
                'name'      => $this->user->name,
                'is_follow'            => $this->resolveIsFollow($request, $this->user->id), // user data  ----
            ],
            'coins' => $this->box?->coins ?? $this->coins ?? 0,
            //            'end_at'=>$this->end_at,
            //            'room_uid'=>$this->room_uid,
            //            'room_id'=>$this->room_id,
            'users_num' => $this->users_num,
            //            'not_used_num'=>$this->not_used_num,
            'type' => $this->type == 1 ? 'super' : 'normal',
            //            'label'=>$this->label,
            //            'image'=>$this->image,
            //            'rem_time'=>$this->type == 1 ? $rem_time : 0
            // 'rem_time' => $rem_time,
            "end_time" => Carbon::createFromTimestamp($this->end_at)->toDateTimeString(),
        ];
    }
}
