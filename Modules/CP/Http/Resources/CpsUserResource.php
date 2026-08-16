<?php

namespace Modules\CP\Http\Resources;

use App\Models\User;
use App\Helpers\Common;
use Modules\CP\Entities\Cp;
use Illuminate\Http\Resources\Json\JsonResource;

class CpsUserResource extends JsonResource
{
    protected $relationType;


    public function __construct($resource, $relationType = null)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);

        // Store the additional parameter
        $this->relationType = $relationType;
    }


    public function toArray($request)
    {
  
        $cp = Cp::where(function ($query) {
            $query->where('user_one_id', $this->id)
                  ->orWhere('user_two_id', $this->id);
        })
        ->where(function ($query) {
            $query->where('status', 1)
                  ->orWhere('status', 4);
        })
        ->whereHas('cpRelation', function ($q) {
            $q->where('type', $this->relationType);

        })
        ->first();

        $otherUser = null;
        if ($cp && ($cp->user_one_id == $this->id)) {
            $otherUser = User::Find($cp->user_two_id);
        } elseif ($cp && ($cp->user_two_id == $this->id)) {
            $otherUser = User::Find($cp->user_one_id);
        }
        $total_received_level_img = Common::getImageTotalReceiverOrSender($this->total_received_level);
        $total_sender_level_img = Common::getImageTotalReceiverOrSender($this->total_sender_level);
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'uuid' => $this->uuid,
            'image' => $this->profile?->avatar ?? '',
            'exp'           => $cp?->di ?? 0,
            'reciver_level_img'  => $total_received_level_img?->img ?? '',
            'sender_level_img'  => $total_sender_level_img?->img ?? '',
            'other' => [
                'id' => $otherUser?->id ?? 0,
                'name' => $otherUser?->name ?? '',
                'uuid' => $otherUser?->uuid ?? 0,
                'image' => $otherUser?->profile?->avatar ?? '',
            ],
        ];
    
   
    }
}
