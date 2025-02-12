<?php

namespace App\Notifications;

use App\Models\Part\PartRelease;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordChannel;
use NotificationChannels\Discord\DiscordMessage;

class PartsUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(
        public PartRelease $release
    ) {
    }

    public function via(object $notifiable): array
    {
        return [
            DiscordChannel::class,
        ];
    }

    public function toDiscord($notifiable)
    {
        return DiscordMessage::create($this->socialMessage());
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
                    ->greeting('')
                    ->line('')
                    ->lineIf($this->amount > 0, "")
                    ->action('', $url)
                    ->line('');
    }

    protected function socialMessage(): string
    {
        return '';
    }
}
