<?php

namespace App\Admin\Actions;


use App\Models\Room;
use Encore\Admin\Actions\RowAction;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class RoomPinAction extends RowAction
{
    public $name;
    public $id;
    protected $selector = '.pin_room_action';

    public function __construct($id = 0,$pin = 0)
    {
        $this->id = $id;
         $this->name = $pin == 1 ? __('Unpin') : __('Pin'); // تغيير الاسم بناءً على القيمة الحالية لـ pin


        parent::__construct();
    }

    public function handle(Model $model, Request $request)
    {   
        $roomId = $model->id;
        $isPinned = $model->pin ? false : true; // عكس الحالة الحالية

        $model->update(['pin' => $isPinned]);

        return $this->response()->success('Pin status updated successfully')->refresh(); // إشعار بتحديث حالة الـ pin
    }

    public function form()
    {
        // هنا يمكنك إضافة الحقول التي تريد أن تظهر في النموذج الخاص بالإجراء
    }

    public function html()
    {
        $model = $this->row;

        // تأكد من أن pin هو 1 أو 0 وليس null أو قيمة أخرى غير متوقعة
        // $this->name = $model->pin == 1 ? __('Unpin') : __('Pin'); // تغيير الاسم بناءً على القيمة الحالية لـ pin

        // // تحقق من أن الـ name غير فارغ
        // if (empty($this->name)) {
            // $this->name = 'Pin'; // تعيين قيمة افتراضية في حال كانت فارغة
        // }

        return '<a href="javascript:void(0);" onclick="pinRoom(' . $this->id . ')" class="pin_room_action">' . $this->name . '</a>
        <script>
            function pinRoom(val) {
                $.post("/admin/rooms/pin", {id: val, _token: LA.token}, function(response) {
                    if(response.status === "success") {
                        location.reload(); 
                    }
                });
            }
        </script>';
    }
}
