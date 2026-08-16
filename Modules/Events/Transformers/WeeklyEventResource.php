<?php

namespace Modules\Events\Transformers;

use Modules\Events\Entities\GeneralRole;
use Carbon\Carbon;
use App\Helpers\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyEventResource extends JsonResource
{
    public $type;
    public function __construct($resource, $type)
    {
        parent::__construct($resource);
        $this->type = $type;
    }
    public function toArray($request)
    {
        $endDate = $this->end_date_local;
        $time = Carbon::now()->copy()->diff($endDate);
        $timeComponents = [
            'day' => @$time->days ?? 0,
            'hour' => @$time->h ?? 0,
            'minute' => @$time->i ?? 0,
            'second' => @$time->s ?? 0,
        ];

        $rule = GeneralRole::query()->where("type", $this->type)->first();
        $locale = app()->getLocale();
        $description = $rule != null ? ($rule->{"desc_{$locale}"} ?? null) ?: ($rule->desc_en ?? '') : "";
        return [

            'description' => $description,
            'remainingTime' => $timeComponents,
            'gifts' => weeklyGiftResource::collection($this->gifts)
        ];
    }
}
