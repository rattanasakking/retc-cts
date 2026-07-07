<?php

namespace Tests\Feature\Notifications;

use App\Models\AcademicYear;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Notifications\StudentSurveyReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LineChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_skips_and_logs_when_recipient_has_no_line_user_id(): void
    {
        config(['services.line.channel_access_token' => 'fake-token']);
        Http::fake();

        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['line_user_id' => null]);

        $student->notify(new StudentSurveyReminder($year));

        Http::assertNothingSent();

        $log = NotificationLog::where('channel', 'line')->firstOrFail();
        $this->assertSame('skipped', $log->status);
        $this->assertSame(Student::class, $log->notifiable_type);
        $this->assertSame($student->id, $log->notifiable_id);
    }

    public function test_skips_and_logs_when_no_channel_access_token_is_configured(): void
    {
        config(['services.line.channel_access_token' => null]);
        Http::fake();

        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['line_user_id' => 'U1234567890']);

        $student->notify(new StudentSurveyReminder($year));

        Http::assertNothingSent();

        $log = NotificationLog::where('channel', 'line')->firstOrFail();
        $this->assertSame('skipped', $log->status);
        $this->assertStringContainsString('LINE_CHANNEL_ACCESS_TOKEN', $log->error_message);
    }

    public function test_sends_a_push_message_and_logs_success(): void
    {
        config(['services.line.channel_access_token' => 'fake-token']);
        Http::fake([
            'api.line.me/*' => Http::response(['sentMessages' => []], 200),
        ]);

        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['line_user_id' => 'U1234567890']);

        $student->notify(new StudentSurveyReminder($year));

        Http::assertSent(function ($request) use ($student, $year) {
            return $request->url() === 'https://api.line.me/v2/bot/message/push'
                && $request['to'] === 'U1234567890'
                && $request->hasHeader('Authorization', 'Bearer fake-token')
                && str_contains($request['messages'][0]['text'], (string) $year->year);
        });

        $log = NotificationLog::where('channel', 'line')->firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertSame($student->id, $log->notifiable_id);
        $this->assertNotNull($log->sent_at);
    }

    public function test_a_failed_line_api_response_is_logged_as_failed_and_does_not_throw(): void
    {
        config(['services.line.channel_access_token' => 'fake-token']);
        Http::fake([
            'api.line.me/*' => Http::response(['message' => 'invalid reply token'], 400),
        ]);

        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['line_user_id' => 'U1234567890']);

        // Should not throw even though the LINE API call fails.
        $student->notify(new StudentSurveyReminder($year));

        $log = NotificationLog::where('channel', 'line')->firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('LINE API error (400)', $log->error_message);
    }
}
