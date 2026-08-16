<?php

namespace Modules\Country\Actions\SuperAdmin;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class CustomDeleteAction extends RowAction
{
    public function name(): string
    {
        return 'Delete';
    }

    public function handle(Model $model)
    {
        // هذا لن يتم استدعاؤه أبداً لأننا سنستخدم JavaScript
        return $this->response()->success('Handled by JavaScript');
    }

    public function html()
    {
        $key = $this->getKey();
        return '<a href="javascript:void(0);" onclick="customSuperAdminDelete(' . $key . ')" class="text-danger"><i class="fa fa-trash"></i>&nbsp;&nbsp;Delete</a>';
    }

    public function script()
    {
        return ''; // لا نحتاج script هنا لأنه موجود في tools
    }
}