<?php

namespace App\Tik\DTO;
use Illuminate\Support\Facades\Auth;

class RoomJobClass extends DataTransferObject
{

    /**
     * @var string
     */
    public int $user_id;

    /**
     * @var ?string
     */
    public ?int $room_id;
    public ?string $data;
    public ?string $type;
    public ?float $coins;



    /**
     * @inheritDoc
     */
    public static function fromRequest($request): DataTransferObject
    {
        return new self([
             'name' => optional($request)['name'],
             'email_parser_ref' => optional($request)['email_parser'],
             'is_default' => (int) optional($request)['is_default'],
             'created_by' => Auth::user()?->id,
        ]);
    }


}
