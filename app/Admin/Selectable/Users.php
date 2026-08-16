<?php

namespace App\Admin\Selectable;

use App\Models\User;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;

class Users extends Selectable
{
    public $model = User::class;

    protected $perPage = 10; // Sets the number of records per page

    public function make()
    {
        $this->column('id', 'ID');
        $this->column('name', 'Name');
        $this->column('email', 'Email');
        //$this->column('avatar', 'Avatar')->image();
        $this->column('created_at', 'Created At');

        $this->filter(function (Filter $filter) {
            $filter->like('name', 'Name');
        });
    }
}
