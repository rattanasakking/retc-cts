<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Public\CareerStatusSelfReport;
use App\Livewire\Reports\SelfReportUsage;
use App\Models\AcademicYear;
use App\Models\SelfReportEvent;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SelfReportUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_teachers_cannot_view_the_usage_statistics(): void
    {
        $this->get('/reports/self-report-usage')->assertRedirect('/login');

        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $this->actingAs($teacher)->get('/reports/self-report-usage')->assertForbidden();
    }

    public function test_admin_executive_and_department_head_can_view_the_usage_statistics(): void
    {
        foreach ([UserRole::Admin, UserRole::Executive, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get('/reports/self-report-usage')
                ->assertOk()
                ->assertSee('สถิติการใช้งานหน้าแจ้งข้อมูล');
        }
    }

    public function test_opening_the_public_form_records_a_visit(): void
    {
        AcademicYear::factory()->create(['is_active' => true]);

        Livewire::test(CareerStatusSelfReport::class);

        $this->assertSame(1, SelfReportEvent::where('event', SelfReportEvent::VISIT)->count());
    }

    public function test_the_whole_journey_of_one_student_is_recorded(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2000-01-01')   // ผิด
            ->call('verify')
            ->set('birthDateInput', '2007-10-02')   // ถูก
            ->call('verify')
            ->set('academic_year_id', $year->id)
            ->set('status', 'unemployed')
            ->set('effective_date', now()->toDateString())
            ->call('submit')
            ->assertSet('step', 'done');

        $this->assertDatabaseHas('self_report_events', ['event' => SelfReportEvent::VISIT]);
        $this->assertDatabaseHas('self_report_events', ['event' => SelfReportEvent::VERIFY_FAILED, 'student_id' => $student->id]);
        $this->assertDatabaseHas('self_report_events', ['event' => SelfReportEvent::VERIFY_SUCCESS, 'student_id' => $student->id]);
        $this->assertDatabaseHas('self_report_events', ['event' => SelfReportEvent::SUBMITTED, 'student_id' => $student->id]);
    }

    public function test_the_funnel_and_rates_are_computed_from_the_recorded_events(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'status' => 'graduated']);

        // ผู้เข้าชม 2 คน เปิดหน้ารวม 4 ครั้ง
        SelfReportEvent::factory()->count(3)->visitor('visitor-a')->create();
        SelfReportEvent::factory()->visitor('visitor-b')->create();

        SelfReportEvent::factory()->event(SelfReportEvent::VERIFY_FAILED)->create();
        SelfReportEvent::factory()->event(SelfReportEvent::VERIFY_SUCCESS)->create(['student_id' => $student->id]);
        SelfReportEvent::factory()->event(SelfReportEvent::SUBMITTED)->create(['student_id' => $student->id]);

        $component = Livewire::actingAs($admin)->test(SelfReportUsage::class);

        $this->assertSame(4, $component->viewData('visits'));
        $this->assertSame(2, $component->viewData('uniqueVisitors'));
        $this->assertSame(1, $component->viewData('submitted'));
        $this->assertSame(25, $component->viewData('completionRate'));   // 1 จาก 4
        $this->assertSame(50, $component->viewData('verifyFailureRate')); // 1 จาก 2
        $this->assertSame(100, $component->viewData('graduateCoverage')); // 1 จากผู้จบ 1 คน
    }

    public function test_the_period_filter_excludes_older_events(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        SelfReportEvent::factory()->create(['created_at' => now()->subDays(2)]);
        SelfReportEvent::factory()->create(['created_at' => now()->subDays(60)]);

        $component = Livewire::actingAs($admin)->test(SelfReportUsage::class);

        $this->assertSame(1, $component->viewData('visits')); // ค่าเริ่มต้น 30 วัน

        $component->set('days', 0);
        $this->assertSame(2, $component->viewData('visits'));
    }

    public function test_no_ip_address_or_user_agent_is_stored(): void
    {
        AcademicYear::factory()->create(['is_active' => true]);

        Livewire::test(CareerStatusSelfReport::class);

        $columns = array_keys(SelfReportEvent::first()->getAttributes());

        $this->assertNotContains('ip_address', $columns);
        $this->assertNotContains('user_agent', $columns);
        $this->assertSame(64, strlen(SelfReportEvent::first()->visitor_hash));
    }
}
