<?php

namespace Modules\Events\Transformers;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWeeklyStar extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */



     public $data;

    // Modify the constructor to accept gift IDs
    public function __construct($resource, $data )
    {
        parent::__construct($resource);
        $this->data = $data;
    }
    public function toArray($request)
    {
        return [
            // 'totalGiftNum' => strval(intval(@$this->data->totalGiftNum)) ?? "0",
            // 'user_id'   => $this->id,
            // 'uuid'      => $this->uuid ?? 0,
            // 'name'      => $this->name ?? '',
            // 'avatar'    => $this->profile->avatar ?? '',
          
                'totalGiftNum' => strval(intval(@$this->data->totalGiftNum) ?? 0),
                'user_id'      => $this->id,
                'uuid'         => $this->uuid ?? 0,
                'name'         => $this->name ?? '',
                'avatar'       => $this->profile->avatar ?? '',
           
        ];
    }
}
