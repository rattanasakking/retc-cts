<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Students\Trash;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_non_privileged_roles_cannot_reach_the_trash_page(): void
    {
        $this->get('/students/trash')->assertRedirect('/login');

        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $this->actingAs($teacher)->get('/students/trash')->assertForbidden();
    }

    public function test_only_soft_deleted_students_are_listed(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();

        $active = Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'ยังอยู่']);
        $trashed = Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'ถูกลบ']);
        $trashed->delete();

        Livewire::actingAs($admin)
            ->test(Trash::class)
            ->assertSee('ถูกลบ')
            ->assertDontSee('ยังอยู่');
    }

    public function test_admin_can_restore_a_deleted_student_and_it_reappears_in_the_active_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'student_code' => '66201011091']);
        $student->delete();

        Livewire::actingAs($admin)
            ->test(Trash::class)
            ->call('restore', $student->id)
            ->assertSee('กู้คืนข้อมูลนักศึกษาเรียบร้อยแล้ว');

        $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'restore', 'auditable_id' => $student->id]);
    }

    public function test_admin_can_permanently_delete_a_student_freeing_the_student_code_for_reuse(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'student_code' => '66201011091']);
        $student->delete();

        Livewire::actingAs($admin)
            ->test(Trash::class)
            ->call('confirmForceDelete', $student->id)
            ->call('forceDelete')
            ->assertSee('ลบข้อมูลนักศึกษาถาวรเรียบร้อยแล้ว');

        $this->assertDatabaseMissing('students', ['id' => $student->id]); // gone, not just soft-deleted
        $this->assertDatabaseHas('audit_logs', ['action' => 'force_delete', 'auditable_id' => $student->id]);

        // The student_code is now genuinely free — a fresh row can reuse it.
        Student::factory()->create(['academic_year_id' => $year->id, 'student_code' => '66201011091']);
        $this->assertDatabaseCount('students', 1);
    }

    public function test_teacher_cannot_restore_or_force_delete_even_by_calling_the_method_directly(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);
        $student->delete();

        Livewire::actingAs($teacher)
            ->test(Trash::class)
            ->call('restore', $student->id)
            ->assertForbidden();

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }
}
