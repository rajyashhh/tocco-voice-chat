<?php

namespace App\Enums;

enum AdminNotificationLink: string
{  case BANNER_SHOW = 'banner_show';

    public function url(array $data = []): string
    {
        return match($this) {
            self::BANNER_SHOW => route('admin.superadmin-banner-requests.index',['itemNotification' => $data['item_id'] ]),
   
        };
    }
}



