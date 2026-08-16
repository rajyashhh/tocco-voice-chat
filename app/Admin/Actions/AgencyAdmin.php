<?php

namespace App\Admin\Actions;



use App\Models\User;
use App\Models\AgencyUserJob;
use Encore\Admin\Actions\Action;



class AgencyAdmin extends Action
{

    public $id;

    protected $selector = '.salary_action';


    public function __construct($id = 0)
    {

        $this->id = $id;
        parent::__construct();
    }
    public function handle(\Illuminate\Http\Request $request)
    {

        $user = User::Find($request->id);
        $admin =  AgencyUserJob::where('user_id', $user->id)->where('agency_id', $user->agency_id)->where('type', 'requestManger')->exists();
        if ($admin) return $this->response()->error(__(' this user admin in  this agency'))->refresh();
        $data = [
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
            'type' => "requestManger",
        ];
        return $this->response()->success('success')->refresh();
    }


    public function form()
    {
        $this->hidden('id', __('id'))->attribute('id', 'vid');

        $this->confirm(__('messages.confirm_delete'), __('messages.refuse'), [
            'icon' => 'warning',
            'showCancelButton' => true,
            'confirmButtonText' => __('messages.yes'),
            'cancelButtonText' => __('messages.cancel'),
        ]);
    }


    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')" class="btn btn-sm btn-info salary_action ">' . __('refuse') . '</a>
<script>
function pu(val) {

  $("#vid").val(val)
}
</script>
';
    }
}
