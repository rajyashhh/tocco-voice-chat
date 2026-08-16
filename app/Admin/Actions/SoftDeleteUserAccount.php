<?php

namespace App\Admin\Actions;


use App\Models\User;
use DB;
use Encore\Admin\Actions\Action;
use Encore\Admin\Actions\RowAction;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;



class SoftDeleteUserAccount extends RowAction
{
    public $name ;
    public $id;
    protected $selector = '.delete_user_account';
    public $permission_name = 'action-trashed';

    public function __construct($id = 0)
    {
        $this->id = $id;
        $this->name = __("dashboard.removeAccount");

        parent::__construct();
    }

    public function handle(Model $model,Request $request)
    {
        if (!Admin::user()->can('*')){
            Permission::check('edit-'.$this->permission_name);
        }
        $user = User::query()->onlyTrashed()->find($request->id);
        DB::table('reports')->where('Reporter_id', $user->id)->delete();
        $user->forceDelete();
        return $this->response()->success('success')->refresh();
    }


    public function form()
    {
        $this->hidden('id', __('id'))->value($this->id);
    }



    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')"  ></a>
<script>
function pu(val) {

  $("#vid").val(val)
}
</script>
';
    }
}
