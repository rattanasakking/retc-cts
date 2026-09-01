<?php

namespace Tests\Feature;

use App\Livewire\Public\CareerStatusSelfReport;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\ThaiDistrict;
use App\Models\ThaiProvince;
use App\Models\ThaiSubdistrict;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CareerStatusSelfReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_is_publicly_accessible(): void
    {
        $this->get('/report-status')->assertOk();
    }

    public function test_searching_by_name_lists_matching_candidates(): void
    {
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมชาย', 'last_name' => 'ใจดี']);
        Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมหญิง', 'last_name' => 'รักเรียน']);

        Livewire::test(CareerStatusSelfReport::class)
            ->set('search', 'สมชาย')
            ->assertSee('สมชาย')
            ->assertDontSee('สมหญิง');
    }

    public function test_selecting_a_candidate_moves_to_the_verify_step(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->assertSet('step', 'verify')
            ->assertSet('candidateId', $student->id);
    }

    public function test_correct_birth_date_verifies_identity_and_moves_to_the_form_step(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2007-10-02')
            ->call('verify')
            ->assertHasNoErrors()
            ->assertSet('step', 'form')
            ->assertSet('verifiedStudentId', $student->id);
    }

    public function test_incorrect_birth_date_fails_verification(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2000-01-01')
            ->call('verify')
            ->assertHasErrors(['birthDateInput'])
            ->assertSet('step', 'verify')
            ->assertSet('verifiedStudentId', null);
    }

    public function test_a_student_with_no_recorded_birth_date_can_never_be_verified(): void
    {
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => null]);

        Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2007-10-02')
            ->call('verify')
            ->assertHasErrors(['birthDateInput'])
            ->assertSet('verifiedStudentId', null);
    }

    public function test_calling_submit_directly_without_verifying_is_forbidden(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::test(CareerStatusSelfReport::class)
            ->set('status', 'unemployed')
            ->set('effective_date', now()->toDateString())
            ->call('submit')
            ->assertForbidden();
    }

    public function test_verified_student_can_submit_an_employed_status(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2007-10-02')
            ->call('verify')
            ->set('academic_year_id', $year->id)
            ->set('status', 'employed')
            ->set('company_name', 'บริษัท ทดสอบ จำกัด')
            ->set('employment_type', 'full_time')
            ->set('effective_date', now()->toDateString())
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('step', 'done');

        $this->assertDatabaseHas('career_statuses', [
            'student_id' => $student->id,
            'status' => 'employed',
            'company_name' => 'บริษัท ทดสอบ จำกัด',
            'source' => 'self_report',
            'is_current' => 1,
        ]);
    }

    /**
     * @return array{province: ThaiProvince, district: ThaiDistrict, subdistrict: ThaiSubdistrict}
     */
    private function seedGeography(): array
    {
        $province = ThaiProvince::create(['id' => 45, 'name_th' => 'ร้อยเอ็ด', 'lat' => 16.05, 'lng' => 103.65]);
        $district = ThaiDistrict::create(['id' => 4501, 'name_th' => 'เมืองร้อยเอ็ด', 'province_id' => $province->id]);
        $subdistrict = ThaiSubdistrict::create(['id' => 450101, 'name_th' => 'ในเมือง', 'district_id' => $district->id]);

        return compact('province', 'district', 'subdistrict');
    }

    private function verifiedComponent(): Testable
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        return Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2007-10-02')
            ->call('verify');
    }

    public function test_a_known_institution_fills_in_its_location_automatically(): void
    {
        ['province' => $province, 'district' => $district, 'subdistrict' => $subdistrict] = $this->seedGeography();

        CareerStatus::factory()->create([
            'status' => 'further_study',
            'institution_name' => 'วิทยาลัยเทคนิคร้อยเอ็ด',
            'work_province_id' => $province->id,
            'work_district_id' => $district->id,
            'work_subdistrict_id' => $subdistrict->id,
        ]);

        $this->verifiedComponent()
            ->set('status', 'further_study')
            ->set('institution_name', 'วิทยาลัยเทคนิคร้อยเอ็ด')
            ->assertSet('work_province_id', $province->id)
            ->assertSet('work_district_id', $district->id)
            ->assertSet('work_subdistrict_id', $subdistrict->id)
            ->assertSee('กรอกที่ตั้งให้อัตโนมัติ');
    }

    public function test_a_known_company_fills_in_its_location_and_address(): void
    {
        ['province' => $province, 'district' => $district] = $this->seedGeography();

        CareerStatus::factory()->create([
            'status' => 'employed',
            'company_name' => 'บริษัท ทดสอบ จำกัด',
            'work_location' => '99 ถนนเทวาภิบาล',
            'work_province_id' => $province->id,
            'work_district_id' => $district->id,
            'work_subdistrict_id' => null,
        ]);

        $this->verifiedComponent()
            ->set('status', 'employed')
            ->set('company_name', 'บริษัท ทดสอบ จำกัด')
            ->assertSet('work_province_id', $province->id)
            ->assertSet('work_district_id', $district->id)
            ->assertSet('work_location', '99 ถนนเทวาภิบาล');
    }

    public function test_a_location_the_student_already_chose_is_never_overwritten(): void
    {
        ['province' => $province, 'district' => $district] = $this->seedGeography();
        $otherProvince = ThaiProvince::create(['id' => 40, 'name_th' => 'ขอนแก่น']);

        CareerStatus::factory()->create([
            'status' => 'further_study',
            'institution_name' => 'วิทยาลัยเทคนิคร้อยเอ็ด',
            'work_province_id' => $province->id,
            'work_district_id' => $district->id,
        ]);

        $this->verifiedComponent()
            ->set('status', 'further_study')
            ->set('work_province_id', $otherProvince->id)
            ->set('institution_name', 'วิทยาลัยเทคนิคร้อยเอ็ด')
            ->assertSet('work_province_id', $otherProvince->id)
            ->assertDontSee('กรอกที่ตั้งให้อัตโนมัติ');
    }

    public function test_an_unknown_place_leaves_the_location_alone(): void
    {
        $this->seedGeography();

        $this->verifiedComponent()
            ->set('status', 'further_study')
            ->set('institution_name', 'สถานศึกษาที่ยังไม่เคยมีใครกรอก')
            ->assertSet('work_province_id', null)
            ->assertDontSee('กรอกที่ตั้งให้อัตโนมัติ');
    }

    public function test_further_study_requires_an_institution_name(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2007-10-02')
            ->call('verify')
            ->set('academic_year_id', $year->id)
            ->set('status', 'further_study')
            ->set('effective_date', now()->toDateString())
            ->call('submit')
            ->assertHasErrors(['institution_name' => 'required']);
    }

    public function test_submitting_supersedes_the_previous_status_for_the_same_academic_year(): void
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        $old = CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'status' => 'unemployed',
            'is_current' => true,
        ]);

        Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2007-10-02')
            ->call('verify')
            ->set('academic_year_id', $year->id)
            ->set('status', 'unemployed')
            ->set('effective_date', now()->toDateString())
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertFalse($old->fresh()->is_current);
        $this->assertSame(2, CareerStatus::where('student_id', $student->id)->count());
    }
}
