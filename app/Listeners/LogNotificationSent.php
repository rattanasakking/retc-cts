<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use App\Notifications\Channels\LineChannel;
use Illuminate\Notifications\Events\NotificationSent;

class LogNotificationSent
{
    /**
     * Fires after ANY notification channel (mail, our custom LineChannel,
     * etc.) completes successfully — for every notifiable, every channel.
     */
    public function handle(NotificationSent $event): void
    {
        // For a custom channel class, Laravel reports the channel's FQCN
        // here (not a short alias like 'mail'). LineChannel logs itself
        // directly with its own skip/sent/failed states — avoid a
        // duplicate "sent" row for it here.
        if ($event->channel === LineChannel::class) {
            return;
        }

        NotificationLog::create([
            'notifiable_type' => get_class($event->notifiable),
            'notifiable_id' => $event->notifiable->getKey(),
            'notification_type' => get_class($event->notification),
            'channel' => $event->channel,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
