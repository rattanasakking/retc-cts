<?php

namespace App\Notifications;

use App\Models\CareerStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCareerStatusSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CareerStatus $careerStatus)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', \App\Notifications\Channels\LineChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->careerStatus->student;

        return (new MailMessage)
            ->subject('มีข้อมูลภาวะการมีงานทำใหม่')
            ->greeting('เรียน คุณ'.$notifiable->name)
            ->line("นักศึกษา {$student->first_name} {$student->last_name} (รหัส {$student->student_code}) ได้บันทึกสถานะภาวะการมีงานทำ")
            ->line('สถานะ: '.$this->careerStatus->status->label())
            ->action('เปิดดูแดชบอร์ด', route('dashboard'));
    }

    public function toLine(object $notifiable): string
    {
        $student = $this->careerStatus->student;

        return "มีข้อมูลใหม่: {$student->first_name} {$student->last_name} ({$student->student_code}) บันทึกสถานะ: {$this->careerStatus->status->label()}";
    }
}
