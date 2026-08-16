<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class LuckyGiftReportResource extends JsonResource
{
    protected $mode;

    public function __construct($resource, $mode = 'default')
    {
        parent::__construct($resource);
        $this->mode = $mode;
    }

    // Override the default collection method
    public static function collection($resources, $mode = 'default')
    {
        return $resources->map(function ($resource) use ($mode) {
            return new static($resource, $mode);
        });
    }

    public function toArray($request)
    {
        $value = $this->total_win;
        if ($this->mode == 'multiply') {
            $value = -$this->gift?->price * $this->number;
        }

        return [
            'id' => $this->id,
            'name' => $this->gift?->name,
            'img' => $this->gift?->img,
            'value' => $value,
            'created_at' => Carbon::parse($this->created_at)->toDateTimeString(),
        ];
    }
}
