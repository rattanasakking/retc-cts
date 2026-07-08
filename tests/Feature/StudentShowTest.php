<?php

namespace Tests\Feature;

use App\Enums\CareerStatusType;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        $this->get("/students/{$student->id}")->assertRedirect('/login');
    }

    public function test_any_authenticated_role_can_view_a_students_details(): void
    {
        $year = AcademicYear::factory()->create(['year' => 2569]);
        $student = Student::factory()->create([
            'academic_year_id' => $year->id,
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'student_code' => '67-00001',
        ]);

        foreach ([UserRole::Admin, UserRole::Teacher, UserRole::Executive, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get("/students/{$student->id}")
                ->assertOk()
                ->assertSee('สมชาย')
                ->assertSee('67-00001');
        }
    }

    public function test_shows_career_status_history_for_the_student(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'status' => CareerStatusType::Employed,
            'company_name' => 'บริษัท ทดสอบ จำกัด',
            'is_current' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get("/students/{$student->id}")
            ->assertOk()
            ->assertSee('บริษัท ทดสอบ จำกัด')
            ->assertSee(CareerStatusType::Employed->label());
    }

    public function test_a_student_with_no_career_history_shows_an_empty_state(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get("/students/{$student->id}")
            ->assertOk()
            ->assertSee('ยังไม่มีการบันทึกภาวะการมีงานทำสำหรับนักศึกษาคนนี้');
    }

    public function test_soft_deleted_student_is_not_viewable(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);
        $student->delete();

        $user = User::factory()->create();

        $this->actingAs($user)->get("/students/{$student->id}")->assertNotFound();
    }

    public function test_student_name_and_code_link_to_the_detail_page_from_the_index(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'student_code' => '67-00099']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/students')
            ->assertOk()
            ->assertSee(route('students.show', $student), false);
    }
}
