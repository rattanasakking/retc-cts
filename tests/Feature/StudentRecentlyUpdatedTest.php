<?php

namespace Tests\Feature;

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
}
