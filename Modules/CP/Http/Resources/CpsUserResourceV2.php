<?php

namespace Modules\CP\Http\Resources;



use Illuminate\Http\Resources\Json\JsonResource;


class CpsUserResourceV2 extends JsonResource
{
    protected $relationType;

    public function __construct($resource, $relationType = null)
    {
        parent::__construct($resource);
        $this->relationType = $relationType;
    }

    public function toArray($request)
    {
        // Find CP directly from eager-loaded relations
        $cp = $this->cpsAsOne
            ->merge($this->cpsAsTwo)
            ->first(fn($cp) =>
                in_array($cp->status, [1,4]) &&
                $cp->cpRelation?->type === $this->relationType
            );

        $otherUser = null;
        if ($cp) {
            $otherUser = $cp->user_one_id == $this->id
                ? $cp->toUser
                : $cp->fromUser;
        }



        return [
            'id'   => $this->id,
            'name' => $this->name ?? '',
            'uuid' => $this->uuid,
            'image'=> $this->profile?->avatar ?? '',
            'exp'  => $cp?->di ?? 0,
            'reciver_level_img' => @$this->receiverLevel?->img ?? '',
            'sender_level_img'  => @$this->senderLevel?->img ?? '',
            'other' => [
                'id'    => $otherUser?->id ?? 0,
                'name'  => $otherUser?->name ?? '',
                'uuid'  => $otherUser?->uuid ?? 0,
                'image' => $otherUser?->profile?->avatar ?? '',
            ],
        ];
    }
}
