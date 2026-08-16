<?php

namespace Modules\SpecialId\Entities;

use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWare extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'user_ware';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ware()
    {
        return $this->belongsTo(Ware::class);
    }

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            $check = UserWare::query()->where(['ware_id' => $model->ware_id, 'disable' => 1])->first();
            if ($check) {
                $check2 = Pack::query()->where(['target_id' => $model->ware_id])->where('expire', '>', now()->timestamp)->first();
                if ($check2) {
                    throw new Exception('هناك مستخدم اخر يتمتع ب ال pacialId');

                    //                    Session::flash('error', 'لم يتم الحفظ بسبب وجود مشكلة ما.');
                    return false;
                }
            }
        });
    }
}
