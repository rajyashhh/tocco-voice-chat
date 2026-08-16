<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RefuseAgency extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
         $appNameEn = Setting::where('key', 'app_title_en')->value('value') ?? 'Default';

        $appNameAr = Setting::where('key', 'app_title_ar')->value('value') ?? 'Default';

        return (new MailMessage)
            ->subject('وكالة جديدة')
            ->line('تم رفض الوكاله')
            ->line($appNameAr . 'شكرا لاستخدامك  !')
            ->line('أطيب التمنيات لكم')
            ->salutation($appNameEn . " - " . $appNameAr);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
