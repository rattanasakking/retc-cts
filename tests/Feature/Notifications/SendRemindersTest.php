<?php

namespace Tests\Feature\Notifications;

use App\Enums\UserRole;
use App\Livewire\Notifications\SendReminders;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\User;
use App\Notifications\StudentSurveyReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class SendRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_academic_year_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SendReminders::class)
            ->set('academicYearId', null)
            ->call('sendReminders')
            ->assertHasErrors(['academicYearId' => 'required']);
    }

    public function test_reminders_go_only_to_graduated_non_responders_for_the_selected_year(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $otherYear = AcademicYear::factory()->create();

        $nonResponder = Student::factory()->graduated()->create(['academic_year_id' => $year->id]);

        $responder = Student::factory()->graduated()->create(['academic_year_id' => $year->id]);
        CareerStatus::factory()->create([
            'student_id' => $responder->id,
            'academic_year_id' => $year->id,
            'is_current' => true,
        ]);

        $stillStudying = Student::factory()->create(['academic_year_id' => $year->id, 'status' => 'studying']);

        $wrongYear = Student::factory()->graduated()->create(['academic_year_id' => $otherYear->id]);

        Livewire::actingAs($admin)
            ->test(SendReminders::class)
            ->set('academicYearId', $year->id)
            ->call('sendReminders')
            ->assertHasNoErrors();

        Notification::assertSentTo($nonResponder, StudentSurveyReminder::class);
        Notification::assertNotSentTo($responder, StudentSurveyReminder::class);
        Notification::assertNotSentTo($stillStudying, StudentSurveyReminder::class);
        Notification::assertNotSentTo($wrongYear, StudentSurveyReminder::class);
    }

    public function test_program_filter_narrows_the_non_responder_count_and_recipients(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();

        $it = Student::factory()->graduated()->create(['academic_year_id' => $year->id, 'program' => 'เทคโนโลยีสารสนเทศ']);
        $accounting = Student::factory()->graduated()->create(['academic_year_id' => $year->id, 'program' => 'การบัญชี']);

        $component = Livewire::actingAs($admin)
            ->test(SendReminders::class)
            ->set('academicYearId', $year->id)
            ->set('program', 'เทคโนโลยีสารสนเทศ');

        $this->assertSame(1, $component->viewData('nonResponderCount'));

        $component->call('sendReminders');

        Notification::assertSentTo($it, StudentSurveyReminder::class);
        Notification::assertNotSentTo($accounting, StudentSurveyReminder::class);
    }

    public function test_success_message_reports_the_number_of_students_notified(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        Student::factory()->count(3)->graduated()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($admin)
            ->test(SendReminders::class)
            ->set('academicYearId', $year->id)
            ->call('sendReminders')
            ->assertSee('3 คน');
    }
}
