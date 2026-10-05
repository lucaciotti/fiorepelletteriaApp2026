<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Notifica push di sistema (browser / PWA mobile) per gli eventi delle lavorazioni.
 */
class WorkOrderWebPushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon(asset('images/icons/icon-192x192.png'))
            ->badge(asset('images/icons/icon-72x72.png'))
            ->data(['action_url' => $this->actionUrl ?? url('/')])
            ->options(['TTL' => 3600]);
    }
}
