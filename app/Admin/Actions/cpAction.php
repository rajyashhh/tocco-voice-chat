<?php

namespace App\Admin\Actions;


use App\Models\Gift;
use App\Models\User;
use App\Models\Ware;
use App\Models\Agency;
use App\Models\Config;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Facades\CustomNotification;
use App\Notifications\AcceptAgency;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\AgencyApp\Entities\AdditionalInfo;

class cpAction extends RowAction
{
    public $name;
    
    public $id;

    public function __construct($id = 0)
    {
        $this->id = $id;
        $this->name = __("dashboard.cp");
        parent::__construct();
    }
    /**
     * @throws ValidationException
     */
    public function handle(Model $model, Request $request)
    {
       $gift = Gift::find($request->id);

       $config =  Config::where('name','cp')->first();
       if($config)
       {
        $config->value = $gift->id;
            $config->save();
       }else{
        Config::create(['name'=>'cp',
        'value' => $gift->id,
    ]);
       }

        return $this->response()->success('success')->refresh();
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