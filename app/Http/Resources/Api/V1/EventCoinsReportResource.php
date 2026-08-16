<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class EventCoinsReportResource extends JsonResource
{
    protected $extraData;

    public function __construct($resource, $extraData = null)
    {
        parent::__construct($resource);
        $this->extraData = $extraData;
    }

    public function toArray($request)
    {
        if ($this->extraData  == "events") {
           $di = $this->reward?->target;
        }else{
            $di = $this->target;
        }
        return [
            'id'          => $this->user_id, 
            'type'        => $this->extraData,
            'diamonds'    => (int)$di,
            'operation_no'=> (int)$this->id,
            'created_at'  => Carbon::parse($this->created_at)->format('Y-m-d h:i:s A')
        ];
    }
}
