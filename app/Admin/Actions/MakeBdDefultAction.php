<?php

namespace App\Admin\Actions;

use App\Models\Bd;
use Illuminate\Http\Request;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class MakeBdDefultAction extends RowAction
{

   
    public $name = 'set_bd_as_default';  

   
    public function handle(Model $model, Request $request)
    {
        $exists = Bd::where('default', 1)
            ->where('id', '!=', $model->id)
            ->exists();

        if ($exists) {
            return $this->response()->error(__('bd_already_default'))->refresh();
        }

        $model->default = 1;
        $model->save();

        return $this->response()->success(__('bd_set_default_success'))->refresh();
    }

   
    public function form()
    {
    }

   
    public function name()
    {
        return __( $this->name);
    }
}
