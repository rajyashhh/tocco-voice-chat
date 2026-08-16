<?php

namespace App\Http\Resources\Dashboard\Reports;
use App\Helpers\StorageHelper;

use App\Http\Resources\Dashboard\Posts\AdminMomentResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMomentReports extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    function get_user($id ){
        $user = User::withTrashed()->find($id);
        if($user)
        {
            return [
                'id'   =>$user->id  ?? '',
                'name' =>$user->name ?? '',
                'img'  =>$user->profile->avatar ?? null,
            ];
        }
        else{
            return null;
        }
    }

    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'reporter_id'    => $this->Reporter_id,
            'reported_id'    => $this->Reported_id,
            'moment'    => new  AdminMomentResource($this->moment),
            'reporter'  => $this->get_user($this->Reporter_id),
            'reported'  => $this->get_user($this->Reported_id),
        ];
    }
}
