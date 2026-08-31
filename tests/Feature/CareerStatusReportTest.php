<?php

namespace Tests\Feature;

use App\Enums\CareerStatusType;
use App\Enums\UserRole;
use App\Livewire\Reports\CareerStatusReport;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CareerStatusReportTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->year = AcademicYear::factory()->create(['year' => 2568, 'is_active' => true]);
    }

    private function graduate(string $program, string $firstName = 'นักศึกษา'): Student
    {
        return Student::factory()->create([
            'academic_year_id' => $this->year->id,
            'status' => 'graduated',
            'program' => $program,
            'first_name' => $firstName,
        ]);
    }

    private function respond(Student $student, CareerStatusType $status, array $attributes = []): CareerStatus
    {
        return CareerStatus::factory()->create(array_merge([
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'status' => $status,
            'is_current' => true,
        ], $attributes));
    }

    public function test_guests_and_teachers_cannot_view_the_report(): void
    {
        $this->get('/reports/career-status')->assertRedirect('/login');

        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $this->actingAs($teacher)->get('/reports/career-status')->assertForbidden();
    }

    public function test_admin_executive_and_department_head_can_view_the_report(): void
    {
        foreach ([UserRole::Admin, UserRole::Executive, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get('/reports/career-status')
                ->assertOk()
                ->assertSee('รายงานภาวะการมีงานทำ');
        }
    }

    public function test_summary_counts_respondents_and_non_respondents(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::Employed);
        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::Entrepreneur);
        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::FurtherStudy);
        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::Unemployed);
        $this->graduate('ช่างยนต์'); // ยังไม่ตอบแบบสำรวจ

        $component = Livewire::actingAs($admin)->test(CareerStatusReport::class);

        $totals = $component->viewData('totals');

        $this->assertSame(5, $totals->total);
        $this->assertSame(2, $totals->employed);  // employed + entrepreneur นับรวมกัน
        $this->assertSame(1, $totals->further_study);
        $this->assertSame(1, $totals->unemployed);
        $this->assertSame(1, $totals->no_response);
        $this->assertSame(4, $component->viewData('respondents'));
        $this->assertSame(80, $component->viewData('responseRate'));
        $this->assertSame(50, $component->viewData('employedRate')); // 2 จาก 4 คนที่ตอบ
    }

    public function test_students_still_studying_are_excluded_unless_the_toggle_is_turned_off(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->graduate('ช่างยนต์', 'จบแล้ว');
        Student::factory()->create([
            'academic_year_id' => $this->year->id,
            'status' => 'studying',
            'program' => 'ช่างยนต์',
            'first_name' => 'ยังเรียนอยู่',
        ]);

        Livewire::actingAs($admin)
            ->test(CareerStatusReport::class)
            ->assertSee('จบแล้ว')
            ->assertDontSee('ยังเรียนอยู่')
            ->set('graduatedOnly', false)
            ->assertSee('ยังเรียนอยู่');
    }

    public function test_the_program_summary_breaks_the_numbers_down_per_program(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::Employed);
        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::Employed);
        $this->respond($this->graduate('การบัญชี'), CareerStatusType::FurtherStudy);
        $this->graduate('การบัญชี');

        $summary = Livewire::actingAs($admin)
            ->test(CareerStatusReport::class)
            ->viewData('summary')
            ->keyBy('program');

        $this->assertSame(2, $summary['ช่างยนต์']->total);
        $this->assertSame(2, $summary['ช่างยนต์']->employed);
        $this->assertSame(0, $summary['ช่างยนต์']->no_response);

        $this->assertSame(2, $summary['การบัญชี']->total);
        $this->assertSame(1, $summary['การบัญชี']->further_study);
        $this->assertSame(1, $summary['การบัญชี']->no_response);
    }

    public function test_filtering_by_career_status_including_the_non_respondents(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // ชื่อต้องไม่ไปซ้ำกับข้อความในตัวกรอง เช่น "ทำงานแล้ว" หรือ "ยังไม่ตอบแบบสำรวจ"
        // ที่ถูก render อยู่ตลอดไม่ว่าจะกรองอะไร
        $this->respond($this->graduate('ช่างยนต์', 'มานะ'), CareerStatusType::Employed);
        $this->graduate('ช่างยนต์', 'ปิติ');

        Livewire::actingAs($admin)
            ->test(CareerStatusReport::class)
            ->set('careerStatus', CareerStatusType::Employed->value)
            ->assertSee('มานะ')
            ->assertDontSee('ปิติ')
            ->set('careerStatus', 'none')
            ->assertSee('ปิติ')
            ->assertDontSee('มานะ');
    }

    public function test_program_and_degree_filters_narrow_the_report(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->graduate('ช่างยนต์', 'สายช่าง');
        $this->graduate('การบัญชี', 'สายบัญชี');

        Livewire::actingAs($admin)
            ->test(CareerStatusReport::class)
            ->set('program', 'ช่างยนต์')
            ->assertSee('สายช่าง')
            ->assertDontSee('สายบัญชี');
    }

    public function test_average_salary_only_counts_working_respondents(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::Employed, ['monthly_salary' => 15000]);
        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::Employed, ['monthly_salary' => 25000]);
        // ศึกษาต่อ: ถึงจะมีตัวเลขติดมาก็ต้องไม่ถูกนำมาเฉลี่ย
        $this->respond($this->graduate('ช่างยนต์'), CareerStatusType::FurtherStudy, ['monthly_salary' => 90000]);

        $avg = Livewire::actingAs($admin)
            ->test(CareerStatusReport::class)
            ->viewData('avgSalary');

        $this->assertEquals(20000, round($avg));
    }

    public function test_the_report_only_covers_the_selected_academic_year(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherYear = AcademicYear::factory()->create(['year' => 2567]);

        $this->graduate('ช่างยนต์', 'ปีนี้');
        Student::factory()->create([
            'academic_year_id' => $otherYear->id,
            'status' => 'graduated',
            'program' => 'ช่างยนต์',
            'first_name' => 'ปีก่อน',
        ]);

        Livewire::actingAs($admin)
            ->test(CareerStatusReport::class)
            ->assertSee('ปีนี้')
            ->assertDontSee('ปีก่อน')
            ->set('academicYearId', $otherYear->id)
            ->assertSee('ปีก่อน')
            ->assertDontSee('ปีนี้');
    }
}
