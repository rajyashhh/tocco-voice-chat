<?php

namespace App\Admin\Actions;


use App\Facades\CustomNotification;
use App\Models\Agency;
use App\Models\User;
use App\Notifications\AcceptAgency;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\AgencyApp\Entities\AdditionalInfo;

class AcceptAgencyAction extends RowAction
{
    public $name;

    public $id;

    public function __construct($id = 0)
    {
        $this->name = __("dashboard.acceptAgency");
        $this->id = $id;
        parent::__construct();
    }
    /**
     * @throws ValidationException
     */
    public function handle(Model $model, Request $request)
    {
        $agency = Agency::find($request->id);
        $user = User::find($agency->app_owner_id);
        $agency->status = 1;
        $agency->save();
        $additionalInfo = AdditionalInfo::where('agency_id', $agency->id)->first();
        $additionalInfo->status = 1;
        $additionalInfo->save();
        $appOwnerId = $agency->app_owner_id;
        $user = User::find($appOwnerId);

        \App\Facades\UserHandling::changeUserAgency($user, $agency->id, 2);

        if ($agency->additionalInfo->gmail) {
            Notification::route('mail',  $agency->additionalInfo->gmail)->notify(new AcceptAgency());
        }
      //  Common::createUserAdmin($appOwnerId);
        CustomNotification::acceptRequestAgency($user);
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
