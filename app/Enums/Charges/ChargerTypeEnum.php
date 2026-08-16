<?php

namespace App\Enums\Charges;

enum ChargerTypeEnum: string
{
    const DASH = 'dash';
    const AGENCY = 'agency';
    const HOST_AGENCY = 'host_agency';
    const BD = 'bd';
    const USER = 'user';
}
