<?php

namespace App\Notifications;

use App\Models\AcademicYear;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentSurveyReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AcademicYear $academicYear)
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
        return (new MailMessage)
            ->subject('แจ้งเตือน: กรุณากรอกแบบสำรวจภาวะการมีงานทำ')
            ->greeting('เรียน คุณ'.$notifiable->first_name.' '.$notifiable->last_name)
            ->line("ระบบยังไม่ได้รับข้อมูลภาวะการมีงานทำของท่านสำหรับปีการศึกษา {$this->academicYear->year}")
            ->action('แจ้งข้อมูลตอนนี้', route('public.career-status-self-report'))
            ->line('หรือติดต่อเจ้าหน้าที่แนะแนวของวิทยาลัยเพื่อกรอกข้อมูลแทนก็ได้')
            ->line('ขอบคุณที่ให้ความร่วมมือกับทางวิทยาลัย');
    }

    public function toLine(object $notifiable): string
    {
        $url = route('public.career-status-self-report');

        return "เรียน คุณ{$notifiable->first_name} ระบบยังไม่ได้รับข้อมูลภาวะการมีงานทำของท่าน ปีการศึกษา {$this->academicYear->year} กรุณาแจ้งข้อมูลได้ที่ {$url} หรือติดต่อเจ้าหน้าที่แนะแนว ขอบคุณครับ/ค่ะ";
    }
}
