<?php

namespace Modules\Country\Enums;

enum SuperAdminNotificationLink: string
{  
    
      case BANNER_APPROVED = 'banner_approved';
      case BANNER_REGECTED = 'banner_rejected';

    public function url(array $data = []): string
    {
        return match($this) {
            self::BANNER_APPROVED => route('superadmin.home-carousel.index',['itemNotification' => $data['item_id'] ]),
            self::BANNER_REGECTED => route('superadmin.home-carousel.index',['itemNotification' => $data['item_id'] ]),
   
        };
    }
}



