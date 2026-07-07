<?php

namespace Tests\Feature;

use App\Enums\CareerStatusType;
use App\Livewire\Dashboard;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_counts_for_the_active_academic_year(): void
    {
        $year = AcademicYear::factory()->create(['year' => 2569, 'is_active' => true]);

        $graduates = Student::factory()->graduated()->count(10)->create(['academic_year_id' => $year->id]);

        foreach ($graduates->take(6) as $i => $student) {
            CareerStatus::factory()->create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'status' => $i < 4 ? CareerStatusType::Employed : CareerStatusType::Unemployed,
            ]);
        }

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('10'); // graduates
        $response->assertSee('6');  // respondents
        $response->assertSee('4');  // employed
        $response->assertSee('2');  // unemployed
        $response->assertSee('ผู้สำเร็จการศึกษา');
        $response->assertSee('ผู้ตอบแบบสอบถาม');
        $response->assertSee('มีงานทำ');
        $response->assertSee('ศึกษาต่อ');
        $response->assertSee('ว่างงาน');
        $response->assertSee('อื่นๆ');
    }

    public function test_dashboard_handles_no_academic_years_gracefully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('0');
    }

    public function test_employed_count_combines_employed_and_entrepreneur(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $students = Student::factory()->graduated()->count(2)->create(['academic_year_id' => $year->id]);

        CareerStatus::factory()->create(['student_id' => $students[0]->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Employed]);
        CareerStatus::factory()->create(['student_id' => $students[1]->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Entrepreneur]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $this->assertSame(2, $component->viewData('stats')['employed']);
    }

    public function test_other_count_combines_military_service_and_other(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $students = Student::factory()->graduated()->count(2)->create(['academic_year_id' => $year->id]);

        CareerStatus::factory()->create(['student_id' => $students[0]->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::MilitaryService]);
        CareerStatus::factory()->create(['student_id' => $students[1]->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Other]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $this->assertSame(2, $component->viewData('stats')['other']);
    }

    public function test_filtering_by_program_narrows_all_stats(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);

        $itStudent = Student::factory()->graduated()->create(['academic_year_id' => $year->id, 'program' => 'เทคโนโลยีสารสนเทศ']);
        $accStudent = Student::factory()->graduated()->create(['academic_year_id' => $year->id, 'program' => 'การบัญชี']);

        CareerStatus::factory()->create(['student_id' => $itStudent->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Employed]);
        CareerStatus::factory()->create(['student_id' => $accStudent->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Unemployed]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('selectedProgram', 'เทคโนโลยีสารสนเทศ');

        $this->assertSame(1, $component->viewData('stats')['graduates']);
        $this->assertSame(1, $component->viewData('stats')['employed']);
        $this->assertSame(0, $component->viewData('stats')['unemployed']);
    }

    public function test_average_salary_only_counts_working_statuses(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $students = Student::factory()->graduated()->count(2)->create(['academic_year_id' => $year->id]);

        CareerStatus::factory()->create([
            'student_id' => $students[0]->id,
            'academic_year_id' => $year->id,
            'status' => CareerStatusType::Employed,
            'monthly_salary' => 20000,
        ]);
        CareerStatus::factory()->create([
            'student_id' => $students[1]->id,
            'academic_year_id' => $year->id,
            'status' => CareerStatusType::Employed,
            'monthly_salary' => 30000,
        ]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $this->assertEquals(25000, $component->viewData('metrics')['avg_salary']);
    }

    public function test_top_province_and_top_company_reflect_the_most_frequent_value(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $students = Student::factory()->graduated()->count(3)->create(['academic_year_id' => $year->id]);

        foreach ($students as $student) {
            CareerStatus::factory()->create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'status' => CareerStatusType::Employed,
                'work_location' => 'ชลบุรี',
                'company_name' => 'บริษัท ทดสอบ จำกัด',
            ]);
        }

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $this->assertSame('ชลบุรี', $component->viewData('metrics')['top_province']);
        $this->assertSame('บริษัท ทดสอบ จำกัด', $component->viewData('metrics')['top_company']);
    }

    public function test_related_to_major_rate_is_computed_from_working_students_only(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $students = Student::factory()->graduated()->count(4)->create(['academic_year_id' => $year->id]);

        CareerStatus::factory()->create(['student_id' => $students[0]->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Employed, 'is_related_to_major' => true]);
        CareerStatus::factory()->create(['student_id' => $students[1]->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Employed, 'is_related_to_major' => true]);
        CareerStatus::factory()->create(['student_id' => $students[2]->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Employed, 'is_related_to_major' => false]);
        CareerStatus::factory()->create(['student_id' => $students[3]->id, 'academic_year_id' => $year->id, 'status' => CareerStatusType::Unemployed]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        // 2 related out of 3 working (unemployed student excluded) = 67%
        $this->assertEquals(67, $component->viewData('metrics')['related_to_major_rate']);
    }
}
