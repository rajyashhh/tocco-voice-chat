<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class OfficialMessageAdmin extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'official_messages';

    protected $guarded = [];
    protected $casts = [
        'multi_feature' => 'array',
    ];

    public function getMultiFeatureAttribute($value)
    {
        $decoded = json_decode($value, true);

        if (is_array($decoded) && isset($decoded[0])) {
            return array_filter(explode(',', $decoded[0]));
        }

        return [];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userOfficialMessages()
    {
        return $this->hasMany(UserOfficialMessage::class);
    }

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            // Determine which request field exists (priority order)
            $featureIdsText = request('feature_ids')
                ?? request('agency_ids')
                ?? request('shipping_agency_ids') ?? request('Bds_id');

            if (is_array($featureIdsText)) {
                $featureIds = array_filter($featureIdsText); // remove nulls
                $featureIdsText = implode(',', $featureIds);
            }
            $adminRoleId = request('region_id') ?? request('country_id');

            // Assign to feature_ids column
            $model->feature_ids = $featureIdsText;
            $model->admin_role_id =  $adminRoleId;
            // ✅ Remove the raw arrays from the request before save
            unset($model->agency_ids);
            unset($model->shipping_agency_ids);
            unset($model->region_id);
            unset($model->country_id);
            unset($model->Bds_id);
        });
    }
}
