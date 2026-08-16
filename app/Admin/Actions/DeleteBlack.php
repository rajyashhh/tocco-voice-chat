<?php

namespace App\Admin\Actions;

use App\Models\BlackList;
use Illuminate\Http\Request;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;


class DeleteBlack extends RowAction
{
    public $name;
    
    public $id;

    public function __construct($id = 0)
    {
        $this->name = __("dashboard.removeBlackList");
        $this->id = $id;
        parent::__construct();
    }
    /**
     * @throws ValidationException
     */
    public function handle(Model $model, Request $request)
    {
       $blackList= BlackList::find( $request->id);
       $blackList->delete();
        return $this->response()->success(__('dashboard.successful'))->refresh();
    }

    public function form()
    {
        $this->hidden('id', __('id'))->value($this->id);
    
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu('.$this->id.')"  ></a>
<script>
function pu(val) {

  $("#vid").val(val)
}
</script>
';
    }
}