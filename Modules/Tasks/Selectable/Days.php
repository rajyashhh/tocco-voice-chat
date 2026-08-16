<?php

namespace Modules\Tasks\Selectable;

use Modules\Tasks\Entities\Day;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;

class Days extends Selectable
{
    public $model = Day::class;

    public function make()
    {
        $this->column('id', 'ID');
        $this->column('title', 'Title');
        //$this->column('description', 'Description');

        $this->filter(function (Filter $filter) {
            $filter->like('title', 'title');
        });
    }
}
