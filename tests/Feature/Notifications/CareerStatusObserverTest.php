<?php

namespace Tests\Feature\Notifications;

use App\Enums\UserRole;
use App\Models\CareerStatus;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\NewCareerStatusSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CareerStatusObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // No LINE token is configured in this suite, so the LineChannel
        // will skip on its own — fake Http anyway to guarantee no real
        // network call could ever be made during the test.
        Http::fake();
    }

    public function test_creating_a_career_status_notifies_every_admin_by_mail(): void
    {
        $admin1 = User::factory()->create(['role' => UserRole::Admin]);
        $admin2 = User::factory()->create(['role' => UserRole::Admin]);
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $careerStatus = CareerStatus::factory()->create();

        $mailLogs = NotificationLog::where('channel', 'mail')
            ->where('notification_type', NewCareerStatusSubmitted::class)
            ->get();

        $this->assertCount(2, $mailLogs);
        $this->assertEqualsCanonicalizing(
            [$admin1->id, $admin2->id],
            $mailLogs->pluck('notifiable_id')->all()
        );
        $this->assertTrue($mailLogs->every(fn ($log) => $log->status === 'sent'));
        $this->assertFalse(
            NotificationLog::where('notifiable_id', $teacher->id)
                ->where('notifiable_type', User::class)
                ->exists()
        );
    }

    public function test_no_notification_is_attempted_when_there_are_no_admins(): void
    {
        User::factory()->create(['role' => UserRole::Teacher]);

        CareerStatus::factory()->create();

        $this->assertSame(0, NotificationLog::where('notification_type', NewCareerStatusSubmitted::class)->count());
    }
}
