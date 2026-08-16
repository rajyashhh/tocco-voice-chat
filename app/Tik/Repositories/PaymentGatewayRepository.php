<?php

namespace App\Tik\Repositories;

use App\Models\PaymentGateway;

class PaymentGatewayRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new PaymentGateway());
    }

    public function all()
    {
        return $this->model->get();
    }
}
