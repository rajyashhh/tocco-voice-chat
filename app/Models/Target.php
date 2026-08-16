<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use TimestampsWithTimezone;

    protected $fillable = [
        'id',
        'level',
        'diamonds',
        'minuts',
        'days',
        'hours',
        'usd',
        'agency_share',
        'moment',
        'reel',
        'gold',
        'coin',
        'img',
        'app_profit_percentage',
        'db_percentage',
        'under_edit',
        'edit_id'
    ];

    protected static function boot()
    {
        parent::boot();

        self::saving(function ($model) {

            if (isset($model->usd) && isset($model->agency_share) && isset($model->db_percentage)) {
                $model->app_profit_percentage = 100 - (float) $model->usd - (float) $model->agency_share - (float) $model->db_percentage;
            }
        });
    }

    public function edit()
    {
        return $this->hasOne(TargetEdit::class, 'id', 'edit_id');
    }

    public  function displayOldNewValue($old, $new, $prefix = '', $isPercentage = false)
    {
        if ($old == $new) {
            $formatted = $isPercentage ? "% {$old}" : "{$prefix}{$old}";
            return "<span style='font-weight:bold;'>{$formatted}</span>";
        }
    
        $oldFormatted = $isPercentage ? "% {$old}" : "{$prefix}{$old}";
        $newFormatted = $isPercentage ? "% {$new}" : "{$prefix}{$new}";
    
        $color = $new > $old ? '#28a745' : '#dc3545';
        $arrow = $new > $old ? '↑' : '↓';
        $arrowIcon =  '🔁';

        return "
            <div style='align-items:center;gap:5px;'>
                <span style='color:#dc3545;text-decoration:line-through;'>{$oldFormatted}</span>
                <span style='display: block;font-size:1.2em;'>$arrowIcon</span>
                <strong style='color:{$color};'>{$newFormatted} {$arrow}</strong>
            </div>
        ";
    }
    
}
