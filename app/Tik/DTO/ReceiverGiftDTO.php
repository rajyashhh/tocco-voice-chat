<?php

namespace App\Tik\DTO;

use App\Models\Room;

class ReceiverGiftDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $avatar,
        public int $vipLevel,
        public int $senderLevel,
        public int $receiverLevel,
    ) {}

    public static function fromUser($user): self
    {
        return new self(
            id: (int) ($user->id ?? 0),
            name: (string) ($user->name ?? ''),
            avatar: (string) ($user->profile->avatar ?? ''),
            vipLevel: (int) ($user->userVip->level ?? 0),
            senderLevel: (int) ($user->total_sender_level ?? 0),
            receiverLevel: (int) ($user->total_received_level ?? 0),
        );
    }
    public static function fromRoom(Room $room): self
    {
        return new self(
            id: (int) ($room->id ?? 0),
            name: (string) ($room->room_name?? ''),
            avatar: (string) ($room->room_cover ?? ''),
            vipLevel: 0,
            senderLevel: 0,
            receiverLevel: 0,
        );
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'avatar'         => $this->avatar,
            'vip_level'      => $this->vipLevel,
            'sender_level'   => $this->senderLevel,
            'receiver_level' => $this->receiverLevel,
            'coins'          => $this->coins,
        ];
    }
}
