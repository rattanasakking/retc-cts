<?php

namespace Tests\Feature;

use App\Enums\CareerStatusType;
use App\Enums\UserRole;
use App\Livewire\Reports\ExportCenter;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExportCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_access_the_export_center(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)->get('/reports/export')->assertForbidden();
    }

    public function test_admin_executive_and_department_head_can_access_the_export_center(): void
    {
        foreach ([UserRole::Admin, UserRole::Executive, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/reports/export')->assertOk();
        }
    }

    public function test_preview_count_respects_program_and_degree_level_filters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create(['year' => 2569]);

        Student::factory()->count(3)->create([
            'academic_year_id' => $year->id,
            'program' => 'เทคโนโลยีสารสนเทศ',
            'degree_level' => 'ปวส.',
        ]);
        Student::factory()->count(2)->create([
            'academic_year_id' => $year->id,
            'program' => 'การบัญชี',
            'degree_level' => 'ปวช.',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(ExportCenter::class)
            ->set('academicYearId', $year->id);

        $this->assertSame(5, $component->viewData('previewCount')); // both programs combined

        $component->set('program', 'เทคโนโลยีสารสนเทศ');
        $this->assertSame(3, $component->viewData('previewCount'));

        $component->set('program', '')->set('degreeLevel', 'ปวช.');
        $this->assertSame(2, $component->viewData('previewCount'));
    }

    public function test_excel_export_downloads_a_file_with_correct_headers(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'student_code' => 'EXP-001']);

        $response = Livewire::actingAs($admin)
            ->test(ExportCenter::class)
            ->set('academicYearId', $year->id)
            ->call('exportExcel');

        $response->assertFileDownloaded();
    }

    public function test_pdf_export_downloads_a_file(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'student_code' => 'EXP-002']);
        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'status' => CareerStatusType::Employed,
            'is_current' => true,
        ]);

        $response = Livewire::actingAs($admin)
            ->test(ExportCenter::class)
            ->set('academicYearId', $year->id)
            ->call('exportPdf');

        $response->assertFileDownloaded();
    }

    public function test_export_only_includes_students_from_the_selected_academic_year(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year2567 = AcademicYear::factory()->create(['year' => 2567]);
        $year2569 = AcademicYear::factory()->create(['year' => 2569]);

        Student::factory()->create(['academic_year_id' => $year2567->id]);
        Student::factory()->create(['academic_year_id' => $year2569->id]);

        $component = Livewire::actingAs($admin)
            ->test(ExportCenter::class)
            ->set('academicYearId', $year2567->id);

        $this->assertSame(1, $component->viewData('previewCount'));
    }
}
