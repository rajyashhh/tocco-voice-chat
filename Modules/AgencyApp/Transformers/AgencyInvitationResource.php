<?php

namespace Modules\AgencyApp\Transformers;

use App\Models\AgencyJoinRequest;
use App\Models\UserSallary;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AgencyInvitationResource extends JsonResource
{
   
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_invite_name' => $this->userInvite->name,
            'user_invite_id' => $this->user_invite_id,
            'agency_name' => $this->agency->name,
            'agency_id' => $this->agency_id,
            'user_name' => $this->user->name,
            'user_id' => $this->user->id,
            'status' => $this->status,
        ];
    }
}
