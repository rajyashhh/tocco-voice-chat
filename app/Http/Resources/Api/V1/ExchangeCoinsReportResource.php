<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeCoinsReportResource extends JsonResource
{

    public function toArray($request)
    {
        $user = $request->user();
        return [
            'id'          => $this->user_id,
            'uuid'          => $user->uuid,
            'diamonds'    => numToStringNew($this->diamonds),
            'value'       => $this->value,
            'operation_no' => (int)$this->operation_no,
            'created_at'  => Carbon::parse($this->created_at)->format('Y-m-d h:i:s A'),
            'coins' => $this->value,
            
        ];
    }
}
