<?php

namespace App\Traits;

use Illuminate\Support\Facades\Request;

trait AutoReceiveType
{

    public static function bootAutoReceiveType()
    {
        static::creating(function ($model) {
            $model->setDefaultReceiveType();
        });

        // static::updating(function ($model) {
        //     $model->setDefaultReceiveType();
        // });
    }

 
    public function setDefaultReceiveType()
    {
        if (empty($this->receive_type)) {
            $this->receive_type = Request::url();
        }
    }
}
