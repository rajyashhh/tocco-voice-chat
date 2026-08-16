<?php

namespace Modules\CP\Transformers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CP\Entities\UserRelationAvilable;

class CpRelationResource extends JsonResource
{
    public function toArray($request)
    {
        $count = UserRelationAvilable::where(["user_id"=>auth()->user()->id ,"cp_relation_id"=>$this->id])->first()?->count;
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'image'        => $this->image,
            'price'        => $this->price,   
            'user_count'   => $count ?? 0,   
        ];
    }
}
