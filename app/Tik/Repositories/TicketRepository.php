<?php

namespace App\Tik\Repositories;

use App\Models\Ticket;

class TicketRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new Ticket());
    }
}
