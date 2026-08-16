<?php

namespace Modules\Public\Http\Controllers\Api;


use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Public\Entities\LevelInterval;
use Modules\Public\Transformers\LevelIntervalResource;


class PublicController extends Controller
{
    public function getLevelInterval(Request $request)
    {
      $class = $request->class ??2;
        $type = $request->type;
        if ($type == 1) {
            $closure = $this->achievementClosure();
            $data    = LevelInterval::whereHas('rewards', $closure);
        } elseif ($type == 2) {
            $closure = $this->entroClauser();
            $data    = LevelInterval::whereHas('rewards', $closure);
        } else {
            $closure = $this->frameAndBubbleClauser();
            $data    = LevelInterval::query()
                                    ->whereHas('rewards', $closure);
        }
        $data = $data->where('type', $class)->with(['rewards' => $closure])->get();
        return Common::apiResponse(1, '', LevelIntervalResource::collection($data), 200);
    }

    /**
     * @return \Closure
     */
    public function achievementClosure(): \Closure
    {
        return fn($query) => $query->where('type', 'achievement');
    }

    /**
     * @return \Closure
     */
    public function entroClauser(): \Closure
    {
        return fn($q) => $q->whereHas('ware', fn($query) => $query->where('type', 6))->where('type', 'ware');
    }

    /**
     * @return \Closure
     */
    public function frameAndBubbleClauser(): \Closure
    {
        return fn($q) => $q->whereHas('ware', fn($query) => $query->whereIn('type', [4, 5]))->where('type', 'ware');
    }
}
