<?php

namespace App\Notifications\Channels;

use App\Models\NotificationLog;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Sends notifications via the LINE Messaging API's push-message endpoint.
 *
 * LINE Notify (the simpler, token-per-user service most tutorials reference)
 * was discontinued by LINE Corporation, so this app integrates with the
 * still-supported LINE Messaging API instead. Sending requires:
 *   1. A LINE Official Account + Channel Access Token (config('services.line.channel_access_token'))
 *   2. The recipient's LINE user ID, returned by their routeNotificationForLine() method
 *
 * Either being absent is treated as a graceful "skip" (logged, not an error)
 * rather than a hard failure, since most recipients won't have LINE linked.
 */
class LineChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $lineUserId = $notifiable->routeNotificationForLine($notification);
        $token = config('services.line.channel_access_token');

        if (! $lineUserId || ! $token) {
            $this->log($notifiable, $notification, 'skipped', $token
                ? 'ผู้รับไม่ได้ผูกบัญชี LINE ไว้'
                : 'ยังไม่ได้ตั้งค่า LINE_CHANNEL_ACCESS_TOKEN');

            return;
        }

        if (! method_exists($notification, 'toLine')) {
            $this->log($notifiable, $notification, 'skipped', 'การแจ้งเตือนนี้ไม่รองรับช่องทาง LINE');

            return;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $lineUserId,
                    'messages' => [
                        ['type' => 'text', 'text' => $notification->toLine($notifiable)],
                    ],
                ]);

            if ($response->failed()) {
                throw new \RuntimeException('LINE API error ('.$response->status().'): '.$response->body());
            }

            $this->log($notifiable, $notification, 'sent');
        } catch (\Throwable $e) {
            // Caught (not re-thrown) so a LINE failure can't crash the queued
            // job or prevent the mail channel — which may have already
            // succeeded in the same dispatch — from completing normally.
            $this->log($notifiable, $notification, 'failed', $e->getMessage());

            event(new NotificationFailed($notifiable, $notification, 'line', ['message' => $e->getMessage()]));
        }
    }

    private function log(object $notifiable, Notification $notification, string $status, ?string $error = null): void
    {
        NotificationLog::create([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'notification_type' => get_class($notification),
            'channel' => 'line',
            'status' => $status,
            'error_message' => $error,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
