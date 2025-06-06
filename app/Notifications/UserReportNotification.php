<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserReportNotification extends Notification
{
    use Queueable;
    private $data;
    private $userPreferences; // User preferences for notification
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
        }elseif ($this->userPreferences->is_email_notify) {
            $channels[] = 'mail';
        }elseif ($this->userPreferences->is_push_notify && !empty($notifiable->fcm_token)) {
            $channels[] = 'fcm';
        }
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
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
