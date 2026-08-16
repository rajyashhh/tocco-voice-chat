<?php

namespace Modules\CP\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserWeeklyCpResource extends JsonResource
{
    protected $data;

    public function __construct($resource, array $data)
    {
        parent::__construct($resource);
        $this->data = $data;
    }

    public function toArray($request)
    {
        $cp = $this->data['cp_relation'] ?? null;
        $loginUserId = $request->get('user_id') ?? Auth::id();

        $user = null;
        if ($cp) {
            $user = $cp->user_one_id === $loginUserId
                ? $cp->toUser
                : $cp->fromUser;
        }

        return [
            'totalGiftNum' => numToString((int) ($this->data['total_price'] ?? 0)) ?: '0',
            'user_id' => $this->id,
            'uuid' => $this->uuid ?? 0,
            'name' => $this->name ?? '',
            'avatar' => $this->profile?->avatar ?? '',
            'user' => $user ? [
                'id' => $user->id ?? null,
                'uuid' => $user->uuid ?? null,
                'name' => $user->name ?? '',
                'image' => $user->avatar ?? '',
                'gender' => (string) (($user->gender ?? '') === 'male' ? 1 : 0),
            ] : null,
        ];
    }
}
