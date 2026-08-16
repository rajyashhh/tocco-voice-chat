<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AcceptRequestToGetSalary extends Notification
{
    use Queueable;
    public $value;
    /**
     * Create a new notification instance.
     */
    public function __construct($value)
    {
        $this->value = $value;
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
            ->subject('سحب راتب')
            ->line(__('api.accept_request_sallary', ["value" => $this->value], 'ar'))
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
