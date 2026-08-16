<?php

namespace Modules\RoomBoom\Entities;

use App\Models\Gift;
use App\Models\Ware;
use App\Helpers\Common;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Achievement\Entities\CustomAchievement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomBoomReward extends Model
{
    protected $fillable = ['room_boom_level_id', 'target', 'target_type', 'priority', 'quantity', 'expire_days'];

    protected $guarded = ['ware_target_id', 'gift_target_id'];

    public function ware(): HasOne
    {
        return $this->hasOne(Ware::class, 'id', 'target');
    }

    public function gift(): HasOne
    {
        return $this->hasOne(Gift::class, 'id', 'target');
    }

    public function customAchievement()
    {
        return $this->hasOne(CustomAchievement::class, 'id', 'target');
    }

    public function ware_target(): BelongsTo
    {
        return $this->belongsTo(Ware::class, 'target');
    }

    public function gift_target(): BelongsTo
    {
        return $this->belongsTo(Gift::class, 'target');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            unset($model->ware_target_id, $model->gift_target_id);

            switch ($model->target_type) {
                case 'ware':
                    if (isset($model->ware_target_id)) {
                        $model->target = $model->ware_target_id;
                        unset($model->ware_target_id);
                    }
                    break;

                case 'gift':
                    if (isset($model->gift_target_id)) {
                        $model->target = $model->gift_target_id;
                        unset($model->gift_target_id);
                    }
                    break;

                case 'achievement':
                    if (isset($model->achievement_target)) {
                        $model->target = $model->achievement_target;
                        unset($model->achievement_target);
                    }


                    break;

                case 'coin':
                    if (isset($model->coin_target)) {
                        $model->target = $model->coin_target;
                        unset($model->coin_target);
                    }
                    break;
            }
        });
    }
}
