<?php

namespace App\Notifications\Channels;

use App\Services\WhatsAppService;
use Illuminate\Notifications\Notification;

class WhatsAppChannel
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->routeNotificationFor('whatsapp');

        if (! $phone) return;

        $data = $notification->toWhatsApp($notifiable);

        $this->whatsapp->sendTemplate(
            to:       $phone,
            template: $data['template'],
            params:   $data['params'] ?? [],
            header:   $data['header'] ?? [],
        );
    }
}
