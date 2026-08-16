<?php

namespace App\Observers;

use Modules\Vip\Entities\OVip;

class OVipObserver
{
    /**
     * Handle the OVip "created" event.
     *
     * @return void
     */
    public function created(OVip $oVip)
    {
        settings()->set('extra_updated_at', time());
    }

    /**
     * Handle the OVip "updated" event.
     *
     * @return void
     */
    public function updated(OVip $oVip)
    {
        if ($oVip->isDirty('img')) {
            settings()->set('extra_updated_at', time());
        }
    }

    /**
     * Handle the OVip "deleted" event.
     *
     * @return void
     */
    public function deleted(OVip $oVip)
    {
        settings()->set('extra_updated_at', time());
    }
}
