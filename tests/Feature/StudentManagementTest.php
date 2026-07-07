<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Students\Index;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_authenticated_roles_can_view_the_student_list(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมชาย']);

        foreach ([UserRole::Admin, UserRole::Teacher, UserRole::Executive, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get('/students')
                ->assertOk()
                ->assertSee('สมชาย');
        }
    }

    public function test_guests_cannot_view_the_student_list(): void
    {
        $this->get('/students')->assertRedirect('/login');
    }

    public function test_teacher_and_executive_do_not_see_management_actions(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id]);

        foreach ([UserRole::Teacher, UserRole::Executive] as $role) {
            $user = User::factory()->create(['role' => $role]);

            Livewire::actingAs($user)
                ->test(Index::class)
                ->assertDontSee('openCreateModal')
                ->assertDontSee('confirmDelete');
        }
    }

    public function test_admin_can_create_a_student(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('student_code', 'NEW-001')
            ->set('first_name', 'ทดสอบ')
            ->set('last_name', 'สร้างใหม่')
            ->set('academic_year_id', $year->id)
            ->set('status', 'studying')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('students', [
            'student_code' => 'NEW-001',
            'first_name' => 'ทดสอบ',
        ]);
    }

    public function test_create_requires_unique_student_code(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['student_code' => 'DUP-100', 'academic_year_id' => $year->id]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreateModal')
            ->set('student_code', 'DUP-100')
            ->set('first_name', 'ทดสอบ')
            ->set('last_name', 'ซ้ำ')
            ->set('academic_year_id', $year->id)
            ->call('save')
            ->assertHasErrors(['student_code' => 'unique']);
    }

    public function test_teacher_cannot_create_a_student_even_by_calling_the_method_directly(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();

        Livewire::actingAs($teacher)
            ->test(Index::class)
            ->set('student_code', 'HACK-001')
            ->set('first_name', 'ไม่ได้รับอนุญาต')
            ->set('last_name', 'ทดสอบ')
            ->set('academic_year_id', $year->id)
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('students', ['student_code' => 'HACK-001']);
    }

    public function test_admin_can_update_a_student(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'เดิม']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openEditModal', $student->id)
            ->set('first_name', 'แก้ไขแล้ว')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('แก้ไขแล้ว', $student->fresh()->first_name);
    }

    public function test_admin_can_delete_a_student(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmDelete', $student->id)
            ->call('delete');

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    public function test_search_filters_by_name_and_student_code(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมชาย', 'student_code' => 'AAA-001']);
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมหญิง', 'student_code' => 'BBB-002']);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'สมชาย')
            ->assertSee('สมชาย')
            ->assertDontSee('สมหญิง');

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'BBB-002')
            ->assertSee('สมหญิง')
            ->assertDontSee('สมชาย');
    }

    public function test_filter_by_academic_year_and_status(): void
    {
        $year2567 = AcademicYear::factory()->create(['year' => 2567]);
        $year2569 = AcademicYear::factory()->create(['year' => 2569]);

        Student::factory()->create(['academic_year_id' => $year2567->id, 'status' => 'graduated', 'first_name' => 'จบแล้ว']);
        Student::factory()->create(['academic_year_id' => $year2569->id, 'status' => 'studying', 'first_name' => 'กำลังเรียน']);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('filterAcademicYearId', $year2567->id)
            ->assertSee('จบแล้ว')
            ->assertDontSee('กำลังเรียน');

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('filterStatus', 'studying')
            ->assertSee('กำลังเรียน')
            ->assertDontSee('จบแล้ว');
    }

    public function test_pagination_limits_results_per_page(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->count(20)->create(['academic_year_id' => $year->id]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Index::class);

        $this->assertCount(15, $component->viewData('students')->items());
        $this->assertSame(20, $component->viewData('students')->total());
    }
}
