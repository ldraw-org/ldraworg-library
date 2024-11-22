<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class PartsUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message
    ) {}

    public function via(object $notifiable): array
    {
        return [
            DiscordChannel::class,
        ];
    }

    public function toDiscord($notifiable)
    {
        return DiscordMessage::create($this->message);
    }
}
