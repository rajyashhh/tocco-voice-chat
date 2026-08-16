<?php

namespace App\Interfaces;

interface RoomJobInterface
{
    public function work($roomJob) : array;
    public function sendToStream($data, int $roomId,int $user_id): string;
    public function prepareDataToStream($data) : array;
    public function getVariables($data) : array;
}
