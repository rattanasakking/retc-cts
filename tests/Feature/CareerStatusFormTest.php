<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\CareerStatuses\CareerStatusForm;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CareerStatusFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_executive_cannot_access_the_form(): void
    {
        $executive = User::factory()->create(['role' => UserRole::Executive]);

        $this->actingAs($executive)->get('/career-statuses/create')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/career-statuses/create')->assertRedirect('/login');
    }

    public function test_admin_teacher_and_department_head_can_access_the_form(): void
    {
        foreach ([UserRole::Admin, UserRole::Teacher, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/career-statuses/create')->assertOk();
        }
    }

    public function test_student_search_finds_matching_students(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมชาย']);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->set('studentSearch', 'สมชาย')
            ->assertSee('สมชาย');
    }

    public function test_employment_fields_are_hidden_until_a_working_status_is_selected(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        $component = Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id);

        $component->assertDontSee('ชื่อบริษัท *');

        $component->set('status', 'employed');
        $component->assertSee('ชื่อบริษัท *');

        $component->set('status', 'unemployed');
        $component->assertDontSee('ชื่อบริษัท *');
    }

    public function test_entrepreneur_status_relabels_company_field(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('status', 'entrepreneur')
            ->assertSee('ชื่อกิจการ *')
            ->assertDontSee('ชื่อบริษัท *');
    }

    public function test_company_name_is_required_when_status_is_employed(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('academic_year_id', $year->id)
            ->set('status', 'employed')
            ->set('effective_date', now()->toDateString())
            ->call('save')
            ->assertHasErrors(['company_name' => 'required']);
    }

    public function test_company_name_is_not_required_when_status_is_unemployed(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('academic_year_id', $year->id)
            ->set('status', 'unemployed')
            ->set('effective_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('career_statuses', [
            'student_id' => $student->id,
            'status' => 'unemployed',
        ]);
    }

    public function test_saving_a_full_employment_record(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('academic_year_id', $year->id)
            ->set('status', 'employed')
            ->set('company_name', 'บริษัท ทดสอบ จำกัด')
            ->set('position', 'โปรแกรมเมอร์')
            ->set('monthly_salary', '25000')
            ->set('employment_type', 'full_time')
            ->set('is_related_to_major', true)
            ->set('effective_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('career_statuses', [
            'student_id' => $student->id,
            'status' => 'employed',
            'company_name' => 'บริษัท ทดสอบ จำกัด',
            'position' => 'โปรแกรมเมอร์',
            'is_related_to_major' => 1,
            'is_current' => 1,
            'source' => 'manual',
        ]);

        $created = CareerStatus::where('student_id', $student->id)->first();
        $this->assertSame($teacher->id, $created->verified_by);
    }

    public function test_submitting_a_new_status_for_the_same_year_supersedes_the_previous_one(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        $old = CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'status' => 'unemployed',
            'is_current' => true,
        ]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('academic_year_id', $year->id)
            ->set('status', 'employed')
            ->set('company_name', 'บริษัท ใหม่ จำกัด')
            ->set('employment_type', 'full_time')
            ->set('effective_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($old->fresh()->is_current);
        $this->assertSame(2, CareerStatus::where('student_id', $student->id)->count());
        $this->assertSame(1, CareerStatus::where('student_id', $student->id)->where('is_current', true)->count());
    }

    public function test_teacher_cannot_save_without_selecting_a_student(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->set('academic_year_id', $year->id)
            ->set('status', 'unemployed')
            ->call('save')
            ->assertHasErrors(['selectedStudentId' => 'required']);
    }
}
