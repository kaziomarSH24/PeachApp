<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class InteractionNotification extends Notification
{
    use Queueable;

    public $interactionUser;
    public $status;
    public $userPreferences; // User preferences for notification

    /**
     * Create a new notification instance.
     */
    public function __construct(User $interactionUser, string $status, $userPreferences)
    {
        // dd($userPreferences);
        $this->interactionUser = $interactionUser;
        $this->status = $status;
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

        if ($this->userPreferences->is_email_notify) {
            $channels[] = 'mail';
        }

        if ($this->userPreferences->is_push_notify && !empty($notifiable->fcm_token)) {
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
            ->subject('New Interaction Notification')
            ->line($this->interactionUser->first_name . ' ' .
            $this->status .
            ($this->status == 'matched' ? " with" : ' ') .
            ' you.')
            ->action('View', url('/notifications'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the push notification representation (Firebase Cloud Messaging - FCM).
     */
    public function toFcm(object $notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->setNotification(FcmNotification::create()
                ->title('New Notification')
                ->setBody($this->interactionUser->first_name . ' ' .
                $this->status .
                ($this->status == 'matched' ? " with" : ' ') .
                ' you.')
                ->setSound('default')
            )
            ->setData([
                'matched_user_id' => $this->interactionUser->id,
                'status' => $this->status
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->interactionUser->first_name . ' ' .
            $this->status .
            ($this->status == 'matched' ? " with" : ' ') .
            ' you.',
            'avatar' => $this->interactionUser->avatar ? asset('storage/' . $this->interactionUser->avatar) : null,
            'matched_user_id' => $this->interactionUser->id,
            'status' => $this->status
        ];
    }
}


