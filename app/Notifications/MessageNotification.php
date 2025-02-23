<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class MessageNotification extends Notification
{
    use Queueable;
    public $data;
    public $userPreferences; // User preferences for notification
    /**
     * Create a new notification instance.
     */
    public function __construct($data, $userPreferences)
    {
        $this->data = $data;
        $this->userPreferences = $userPreferences;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->userPreferences->is_app_notify) {
            $channels[] = 'database';
        }

        // if ($this->userPreferences->is_email_notify) {
        //     $channels[] = 'mail';
        // }

        if ($this->userPreferences->is_push_notify && !empty($notifiable->fcm_token)) {
            $channels[] = 'fcm';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line($this->data['message'])
    //         ->action('View Message', url('/'))
    //         ->line('Thank you for using our application!');
    // }


    /**
     * Get the FCM representation of the notification.
     *
     * @return FcmMessage
     */

     public function toFcm(object $notifiable): FcmMessage
     {
         return FcmMessage::create()
             ->setData($this->data)
             ->setNotification(FcmNotification::create()
                 ->setTitle('New Message')
                 ->setBody($this->data['message'])
                 ->setClickAction('FLUTTER_NOTIFICATION_CLICK')
             )
             ->setData($this->data);
     }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->data;

    }
}
