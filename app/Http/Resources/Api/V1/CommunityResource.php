<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;


class CommunityResource extends JsonResource
{
    public function toArray($request)
{
    $title = app()->getLocale() === 'ar' ? $this->title_ar ?? $this->title : $this->title;

    return [
        'id' => $this->id,
        'title' => $title,
        'img' => $this->img,
        'user_id' => $this->user_id,
        'content' => $this->content,
        'type' => $this->type,
        'sub_type' => $this->sub_type,
        'url' => $this->url,
        'from_user_id' =>$this->from_user_id,
        'created_at' => Carbon::parse($this->created_at)->setTimezone($request->hasHeader('tz') ? $request->header()['tz'][0] : 'UTC')->format('Y-m-d H:i:s') ?? '',
        
        'updated_at' => $this->updated_at,
    ];
}
}
