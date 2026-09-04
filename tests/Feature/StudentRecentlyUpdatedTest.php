<?php

namespace Tests\Feature;

use App\Enums\CareerStatusType;
use App\Enums\UserRole;
use App\Livewire\Students\RecentlyUpdated;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class StudentRecentlyUpdatedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creates a student whose row genuinely looks like it was last touched at
     * $updatedAt — written straight to the DB so Eloquent doesn't stamp
     * updated_at back to now().
     */
    private function studentUpdatedAt(AcademicYear $year, string $name, string $updatedAt): Student
    {
        $student = Student::factory()->create([
            'academic_year_id' => $year->id,
            'first_name' => $name,
        ]);

        DB::table('students')->where('id', $student->id)->update(['updated_at' => $updatedAt]);

        return $student->refresh();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/students/recently-updated')->assertRedirect('/login');
    }

    public function test_any_authenticated_role_can_view_the_page(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)
            ->get('/students/recently-updated')
            ->assertOk()
            ->assertSee('ข้อมูลนักศึกษาที่ปรับปรุงล่าสุด');
    }

    public function test_students_are_listed_newest_update_first(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();

        $this->studentUpdatedAt($year, 'มานะ', now()->subDays(5)->toDateTimeString());
        $this->studentUpdatedAt($year, 'ปิติ', now()->subHour()->toDateTimeString());

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->assertSeeInOrder(['ปิติ', 'มานะ']);
    }

    public function test_the_period_filter_hides_students_updated_before_the_window(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();

        $this->studentUpdatedAt($year, 'มานะ', now()->subDays(60)->toDateTimeString());
        $this->studentUpdatedAt($year, 'ปิติ', now()->subDays(2)->toDateTimeString());

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->assertSee('ปิติ')
            ->assertDontSee('มานะ')
            ->set('days', 0) // ทุกช่วงเวลา
            ->assertSee('มานะ');
    }

    public function test_a_career_status_edit_counts_as_an_update_even_when_the_student_row_is_untouched(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();

        $student = $this->studentUpdatedAt($year, 'ชูใจ', now()->subDays(90)->toDateTimeString());
        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
        ]);

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->assertSee('ชูใจ') // อยู่ในช่วง 30 วันเพราะภาวะการมีงานทำเพิ่งถูกบันทึก
            ->set('filterSource', 'career_status')
            ->assertSee('ชูใจ')
            ->set('filterSource', 'student')
            ->assertDontSee('ชูใจ');
    }

    public function test_search_and_academic_year_filters_narrow_the_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create(['year' => 2568]);
        $otherYear = AcademicYear::factory()->create(['year' => 2569]);

        $this->studentUpdatedAt($year, 'มานะ', now()->subHour()->toDateTimeString());
        $this->studentUpdatedAt($otherYear, 'ปิติ', now()->subHour()->toDateTimeString());

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->set('search', 'มานะ')
            ->assertSee('มานะ')
            ->assertDontSee('ปิติ')
            ->set('search', '')
            ->set('filterAcademicYearId', $otherYear->id)
            ->assertSee('ปิติ')
            ->assertDontSee('มานะ');
    }

    public function test_clicking_a_student_opens_the_detail_popup_and_closing_hides_it(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = $this->studentUpdatedAt($year, 'มานะ', now()->subHour()->toDateTimeString());

        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'status' => CareerStatusType::Employed,
            'company_name' => 'บริษัททดสอบ จำกัด',
            'position' => 'ช่างเทคนิค',
        ]);

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->assertDontSee('บริษัททดสอบ จำกัด') // popup ยังไม่เปิด
            ->call('openDetail', $student->id)
            ->assertSee('บริษัททดสอบ จำกัด')
            ->assertSee('ช่างเทคนิค')
            ->assertSee('ดูข้อมูลเต็ม')
            ->call('closeDetail')
            ->assertDontSee('บริษัททดสอบ จำกัด');
    }

    public function test_a_self_reported_career_status_is_credited_to_the_student(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = $this->studentUpdatedAt($year, 'มานะ', now()->subDays(2)->toDateTimeString());

        // ไม่มีใครล็อกอินตอนสร้าง เหมือนตอนนักศึกษากรอกผ่านหน้าสาธารณะ
        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source' => 'self_report',
        ]);

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->assertSee('นักศึกษาแจ้งด้วยตนเอง')
            ->assertSee('ผ่านหน้าแจ้งข้อมูลด้วยตนเอง')
            ->assertDontSee('ระบบ');
    }

    public function test_an_imported_career_status_is_still_credited_to_the_system(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = $this->studentUpdatedAt($year, 'ปิติ', now()->subDays(2)->toDateTimeString());

        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'source' => 'imported',
        ]);

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->assertSee('ระบบ')
            ->assertDontSee('นักศึกษาแจ้งด้วยตนเอง');
    }

    public function test_the_latest_editor_from_the_audit_log_is_shown(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'name' => 'ผู้ดูแลระบบทดสอบ']);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        // Writes an "update" audit_logs row attributed to $admin.
        $this->actingAs($admin);
        $student->update(['phone' => '0800000000']);

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->assertSee('ผู้ดูแลระบบทดสอบ')
            ->assertSee('แก้ไขข้อมูล');
    }

    public function test_staff_can_mark_a_student_as_recorded_in_vcop(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'name' => 'เจ้าหน้าที่ทดสอบ']);
        $year = AcademicYear::factory()->create();
        $student = $this->studentUpdatedAt($year, 'มานะ', now()->subHour()->toDateTimeString());

        $career = CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'is_current' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->assertSee('บันทึก V-COP แล้ว')
            ->call('markVcop', $student->id)
            ->assertSee('บันทึก V-COP แล้ว')
            ->assertSee('เจ้าหน้าที่ทดสอบ');

        $career->refresh();
        $this->assertNotNull($career->vcop_recorded_at);
        $this->assertSame($admin->id, $career->vcop_recorded_by);
    }

    public function test_marking_can_be_undone(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = $this->studentUpdatedAt($year, 'มานะ', now()->subHour()->toDateTimeString());

        $career = CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'is_current' => true,
            'vcop_recorded_at' => now(),
            'vcop_recorded_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->call('unmarkVcop', $student->id);

        $career->refresh();
        $this->assertNull($career->vcop_recorded_at);
        $this->assertNull($career->vcop_recorded_by);
    }

    public function test_a_newer_report_from_the_student_needs_recording_again(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = $this->studentUpdatedAt($year, 'มานะ', now()->subDays(3)->toDateTimeString());

        // รอบแรก: คีย์เข้า V-COP แล้ว
        $first = CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'is_current' => false,
            'vcop_recorded_at' => now()->subDays(2),
            'vcop_recorded_by' => $admin->id,
        ]);

        // นักศึกษาแจ้งข้อมูลใหม่ทับ — ข้อมูลชุดใหม่ยังไม่ได้คีี่ย์
        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'is_current' => true,
        ]);

        $status = Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->viewData('vcopStatus');

        $this->assertSame('pending', $status[$student->id]['state']);
        $this->assertNotNull($first->fresh()->vcop_recorded_at); // ของเดิมไม่ถูกแตะ
    }

    public function test_the_vcop_filter_separates_the_queue_from_the_finished_ones(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();

        $pending = $this->studentUpdatedAt($year, 'ยังไม่คีย์', now()->subHour()->toDateTimeString());
        CareerStatus::factory()->create(['student_id' => $pending->id, 'academic_year_id' => $year->id, 'is_current' => true]);

        $done = $this->studentUpdatedAt($year, 'คีย์แล้ว', now()->subHour()->toDateTimeString());
        CareerStatus::factory()->create([
            'student_id' => $done->id,
            'academic_year_id' => $year->id,
            'is_current' => true,
            'vcop_recorded_at' => now(),
            'vcop_recorded_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(RecentlyUpdated::class)
            ->set('filterVcop', 'pending')
            ->assertSee('ยังไม่คีย์')
            ->assertDontSee('คีย์แล้ว')
            ->set('filterVcop', 'done')
            ->assertSee('คีย์แล้ว')
            ->assertDontSee('ยังไม่คีย์');
    }

    public function test_an_executive_can_see_the_status_but_not_change_it(): void
    {
        $executive = User::factory()->create(['role' => UserRole::Executive]);
        $year = AcademicYear::factory()->create();
        $student = $this->studentUpdatedAt($year, 'มานะ', now()->subHour()->toDateTimeString());
        CareerStatus::factory()->create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'is_current' => true]);

        Livewire::actingAs($executive)
            ->test(RecentlyUpdated::class)
            ->assertSee('ยังไม่ได้บันทึก')
            ->call('markVcop', $student->id)
            ->assertForbidden();
    }
}
