<?php

namespace Modules\AgencyApp\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AgencyMail extends Notification
{
    use Queueable;

    protected $agencyWithAdditionalInfo;

    public function __construct($agencyWithAdditionalInfo)
    {
        $this->agencyWithAdditionalInfo = $agencyWithAdditionalInfo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $agency = $this->agencyWithAdditionalInfo;
        return (new MailMessage)
            ->subject('اضافه وكالة جديدة')
            ->greeting('تحياتى!')
            ->line('A new agency has been created.')
            ->line('Agency name: ' . $agency->name) 
            ->line('Additional info:')
            ->line('Gmail: ' . $agency->additionalInfo->gmail)
            ->line('Face image national ID: ' . $agency->additionalInfo->face_image_nationalId)
            ->line('Back image national ID: ' . $agency->additionalInfo->back_image_nationalId)
            ->line('Country name: ' . $agency->additionalInfo->country->name)
            ->line('History app info: ' . $agency->additionalInfo->history_app_info)
            ->line('Salary: ' . $agency->additionalInfo->salary)
            ->line('Host: ' . $agency->additionalInfo->host)
            ->action('Notification Action', 'https://laravel.com')
            ->line('شكرا لاستخدامك تيك شات !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
