<?php

namespace Tests\Feature;

use App\Enums\CareerStatusType;
use App\Livewire\Public\StudentSearch;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Support\ThaiDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicStudentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_access_the_search_page_without_logging_in(): void
    {
        $this->get('/search')->assertOk();
    }

    public function test_no_results_are_shown_before_a_search_term_is_entered(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมชาย']);

        Livewire::test(StudentSearch::class)
            ->assertDontSee('สมชาย')
            ->assertSee('กรอกชื่อหรือรหัสนักศึกษา');
    }

    public function test_searching_by_name_finds_the_student(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมชาย', 'last_name' => 'ใจดี']);
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมหญิง', 'last_name' => 'รักเรียน']);

        Livewire::test(StudentSearch::class)
            ->set('search', 'สมชาย')
            ->assertSee('สมชาย')
            ->assertDontSee('สมหญิง');
    }

    public function test_searching_by_student_code_finds_the_student(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'student_code' => 'FIND-001']);

        Livewire::test(StudentSearch::class)
            ->set('search', 'FIND-001')
            ->assertSee('FIND-001');
    }

    public function test_a_single_character_does_not_trigger_a_search(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'ก']);

        Livewire::test(StudentSearch::class)
            ->set('search', 'ก')
            ->assertSee('กรอกชื่อหรือรหัสนักศึกษา');
    }

    public function test_no_match_shows_a_not_found_message(): void
    {
        Livewire::test(StudentSearch::class)
            ->set('search', 'ไม่มีตัวตนแบบนี้แน่นอน')
            ->assertSee('ไม่พบข้อมูลนักศึกษา');
    }

    public function test_sensitive_personal_data_is_never_rendered_on_the_public_page(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create([
            'academic_year_id' => $year->id,
            'student_code' => 'PII-001',
            'first_name' => 'ทดสอบ',
            'national_id' => '1234567890123',
            'phone' => '0899999999',
            'email' => 'secret@example.com',
            'address' => '123 ถนนความลับ',
        ]);

        Livewire::test(StudentSearch::class)
            ->set('search', 'PII-001')
            ->assertSee('ทดสอบ')
            ->assertDontSee('1234567890123')
            ->assertDontSee('0899999999')
            ->assertDontSee('secret@example.com')
            ->assertDontSee('123 ถนนความลับ');
    }

    public function test_a_result_shows_when_the_career_status_was_last_updated(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create([
            'academic_year_id' => $year->id,
            'student_code' => 'UPD-001',
            'first_name' => 'มานะ',
        ]);

        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'status' => CareerStatusType::Employed,
            'is_current' => true,
        ]);

        Livewire::test(StudentSearch::class)
            ->set('search', 'UPD-001')
            ->assertSee('ภาวะการมีงานทำ')
            ->assertSee('ทำงานแล้ว')
            ->assertSee('อัปเดตล่าสุด')
            ->assertSee(ThaiDate::short(now()));
    }

    public function test_a_student_who_has_never_reported_is_pointed_at_the_self_report_form(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create([
            'academic_year_id' => $year->id,
            'student_code' => 'UPD-002',
        ]);

        Livewire::test(StudentSearch::class)
            ->set('search', 'UPD-002')
            ->assertSee('ยังไม่มีข้อมูลภาวะการมีงานทำ')
            ->assertSee('แจ้งข้อมูลของฉัน');
    }

    public function test_employer_details_stay_off_the_public_page(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create([
            'academic_year_id' => $year->id,
            'student_code' => 'UPD-003',
        ]);

        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'status' => CareerStatusType::Employed,
            'company_name' => 'บริษัทลับ จำกัด',
            'position' => 'ตำแหน่งลับ',
            'monthly_salary' => 45678,
            'is_current' => true,
        ]);

        Livewire::test(StudentSearch::class)
            ->set('search', 'UPD-003')
            ->assertSee('ทำงานแล้ว')
            ->assertDontSee('บริษัทลับ จำกัด')
            ->assertDontSee('ตำแหน่งลับ')
            ->assertDontSee('45678');
    }
}
