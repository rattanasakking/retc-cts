<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'notification_type',
        'channel',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-readable recipient label regardless of notifiable type.
     */
    public function getRecipientLabelAttribute(): string
    {
        $notifiable = $this->notifiable;

        if (! $notifiable) {
            return '(ผู้รับถูกลบแล้ว)';
        }

        return match ($this->notifiable_type) {
            Student::class => "{$notifiable->first_name} {$notifiable->last_name} ({$notifiable->student_code})",
            User::class => "{$notifiable->name} ({$notifiable->email})",
            default => (string) $notifiable->getKey(),
        };
    }

    /**
     * Human-readable label for the notification_type class name.
     */
    public function getNotificationLabelAttribute(): string
    {
        return match ($this->notification_type) {
            \App\Notifications\StudentSurveyReminder::class => 'แจ้งเตือนกรอกแบบสำรวจ',
            \App\Notifications\NewCareerStatusSubmitted::class => 'แจ้งเตือนข้อมูลใหม่',
            default => class_basename($this->notification_type),
        };
    }
}
