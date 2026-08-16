<?php

namespace App\Admin\Actions;


use App\Models\User;
use App\Models\Ware;
use App\Models\Agency;
use Illuminate\Http\Request;
use App\Facades\CustomNotification;
use App\Notifications\RefuseAgency;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\AgencyApp\Entities\AdditionalInfo;

class RefuseAgencyAction extends RowAction
{
    public $name = 'رفض الوكاله';
    
    public $id;

    public function __construct($id = 0)
    {
        $this->id = $id;
        parent::__construct();
    }
    /**
     * @throws ValidationException
     */
    public function handle(Model $model, Request $request)
    {
       $agency= Agency::find( $request->id);
       $user = User::find($agency->app_owner_id);
       $additionalInfo = AdditionalInfo::where('agency_id', $agency->id)->first();
       $additionalInfo->status = 2;
       $additionalInfo->save();
       if($agency->additionalInfo->gmail)
            {
                Notification::route('mail',  $agency->additionalInfo->gmail)->notify(new RefuseAgency());
            }
       $agency->delete();
       CustomNotification::refuseRequestAgency($user);
        return $this->response()->success('refused')->refresh();
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
