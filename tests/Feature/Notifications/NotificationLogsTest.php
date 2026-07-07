<?php

namespace Tests\Feature\Notifications;

use App\Enums\UserRole;
use App\Livewire\Notifications\NotificationLogs;
use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationLogsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(array $overrides = [], ?Student $student = null): NotificationLog
    {
        $student ??= Student::factory()->create();
        $createdAt = $overrides['created_at'] ?? null;
        unset($overrides['created_at']);

        $log = NotificationLog::create(array_merge([
            'notifiable_type' => Student::class,
            'notifiable_id' => $student->id,
            'notification_type' => \App\Notifications\StudentSurveyReminder::class,
            'channel' => 'mail',
            'status' => 'sent',
            'sent_at' => now(),
        ], $overrides));

        if ($createdAt) {
            // created_at isn't mass-assignable, so back-date it directly
            // to control ordering in tests.
            $log->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $log;
    }

    public function test_it_lists_notification_logs_newest_first(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $older = $this->makeLog(['created_at' => now()->subDay()]);
        $newer = $this->makeLog(['created_at' => now()]);

        Livewire::actingAs($admin)
            ->test(NotificationLogs::class)
            ->assertSeeInOrder([
                $newer->recipient_label,
                $older->recipient_label,
            ]);
    }

    public function test_it_filters_by_channel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $mailLog = $this->makeLog(['channel' => 'mail']);
        $lineLog = $this->makeLog(['channel' => 'line']);

        Livewire::actingAs($admin)
            ->test(NotificationLogs::class)
            ->set('channel', 'line')
            ->assertSee($lineLog->recipient_label)
            ->assertDontSee($mailLog->recipient_label);
    }

    public function test_it_filters_by_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $sent = $this->makeLog(['status' => 'sent']);
        $failed = $this->makeLog(['status' => 'failed', 'error_message' => 'boom']);

        Livewire::actingAs($admin)
            ->test(NotificationLogs::class)
            ->set('status', 'failed')
            ->assertSee($failed->recipient_label)
            ->assertDontSee($sent->recipient_label);
    }

    public function test_it_paginates_results(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $student = Student::factory()->create();

        for ($i = 0; $i < 25; $i++) {
            $this->makeLog(student: $student);
        }

        $component = Livewire::actingAs($admin)->test(NotificationLogs::class);

        $this->assertCount(20, $component->viewData('logs')->items());
        $this->assertSame(25, $component->viewData('logs')->total());
    }
}
