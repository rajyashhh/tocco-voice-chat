<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait CreatedByTrait
{
    protected static function bootCreatedByTrait()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id(); 
            } elseif (\Admin::user()) {
                $model->created_by = \Admin::user()->id; 
            }
        });
    }
}
