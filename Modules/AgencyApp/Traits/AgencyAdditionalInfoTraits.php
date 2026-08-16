<?php

namespace Modules\AgencyApp\Traits;

use Modules\AgencyApp\Entities\AdditionalInfo;




trait AgencyAdditionalInfoTraits
{
    public function additionalInfo(){
        return $this->hasOne(AdditionalInfo::class,'agency_id');
    }
    

}