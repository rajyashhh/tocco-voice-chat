<?php

namespace App\Http\Resources\Dashboard\Banners;
use App\Helpers\StorageHelper;

use App\Models\User;
use App\Traits\Dashboard\DashBoardTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCuarselResource extends JsonResource
{
    use DashBoardTrait;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    function get_user($id) {
        $user = User::where('id',$id)->first();
        if($user)
        {
            return [
                'id'   =>$user->id ,
                'uuid'   =>$user->uuid ,
                'name' =>$user->name ?? '',
                'img'  =>$user->profile?->avatar ?? null,
            ];
        }
        else{
            return null;
        }
    }

    public function toArray(Request $request): array
    {
        return [
            'sort' => $this->sort,
            'id' => $this->id,
            'img' => $this->img,
            'type_id' => $this->type,
            'from_id' => $this->form,
            'contents' => $this->contents,
            'url' => $this->url,
            'enable' => $this->enable,
            'input' => $this->input, //
            'duration' => Carbon::parse($this->duration)->format('Y-m-d h:m:i A'),
            'from' => $this->cuarsel_from_type( $this->form),
            'type' => $this->cuarsel_type( $this->type),
            'user' =>$this->get_user($this->owner_id),
        ];
    }
}
