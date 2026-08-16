<?php

namespace App\Tik\DTO;

class RoomJob extends DataTransferObject
{

    public function __construct(array $parameters = []) { parent::__construct($parameters); }

    public static function fromRequest($request): DataTransferObject
    {
        // TODO: Implement fromRequest() method.
    }
}
