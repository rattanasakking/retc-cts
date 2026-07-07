<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use Illuminate\Notifications\Events\NotificationFailed;

class LogNotificationFailed
{
    /**
     * Fires when a channel reports failure via the NotificationFailed event.
     * Our LineChannel dispatches this manually after catching its own
     * exceptions; it also logs itself directly, so skip duplicating here.
     */
    public function handle(NotificationFailed $event): void
    {
        if ($event->channel === 'line') {
            return;
        }

        NotificationLog::create([
            'notifiable_type' => get_class($event->notifiable),
            'notifiable_id' => $event->notifiable->getKey(),
            'notification_type' => get_class($event->notification),
            'channel' => $event->channel,
            'status' => 'failed',
            'error_message' => $event->data['message'] ?? 'Unknown error',
        ]);
    }
}
