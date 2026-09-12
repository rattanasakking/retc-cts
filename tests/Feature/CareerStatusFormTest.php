<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\CareerStatuses\CareerStatusForm;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Company;
use App\Models\Student;
use App\Models\ThaiDistrict;
use App\Models\ThaiProvince;
use App\Models\ThaiSubdistrict;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_the_form_can_be_opened_with_a_student_already_chosen(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'first_name' => 'สมหญิง']);

        // The button on the student page links here with ?student=ID.
        $this->actingAs($teacher)
            ->get('/career-statuses/create?student='.$student->id)
            ->assertOk()
            ->assertSee('สมหญิง')
            ->assertSee('กลับไปหน้านักศึกษา');

        // Saving from there lands back on the student, not on an empty form.
        Livewire::actingAs($teacher)
            ->withQueryParams(['student' => $student->id])
            ->test(CareerStatusForm::class)
            ->assertSet('selectedStudentId', $student->id)
            ->set('status', 'unemployed')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('students.show', $student));

        $this->assertDatabaseHas('career_statuses', ['student_id' => $student->id, 'status' => 'unemployed']);
    }

    public function test_an_unknown_student_id_in_the_url_is_ignored(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        Livewire::actingAs($teacher)
            ->withQueryParams(['student' => 999999])
            ->test(CareerStatusForm::class)
            ->assertSet('selectedStudentId', null)
            ->assertSet('returnToStudentId', null);
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

    public function test_institution_name_is_required_when_status_is_further_study(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('academic_year_id', $year->id)
            ->set('status', 'further_study')
            ->set('effective_date', now()->toDateString())
            ->call('save')
            ->assertHasErrors(['institution_name' => 'required']);
    }

    public function test_saving_a_further_study_record_stores_the_institution_name(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('academic_year_id', $year->id)
            ->set('status', 'further_study')
            ->set('institution_name', 'มหาวิทยาลัยเชียงใหม่')
            ->set('effective_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('career_statuses', [
            'student_id' => $student->id,
            'status' => 'further_study',
            'institution_name' => 'มหาวิทยาลัยเชียงใหม่',
        ]);
    }

    public function test_institution_suggestions_come_from_the_places_table(): void
    {
        Http::fake();

        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Company::create([
            'name' => 'มหาวิทยาลัยเชียงใหม่',
            'kind' => Company::INSTITUTION,
        ]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('status', 'further_study')
            ->set('institution_name', 'เชียงใหม่')
            ->assertSee('มหาวิทยาลัยเชียงใหม่');
    }

    public function test_a_workplace_is_never_offered_as_a_place_of_study(): void
    {
        Http::fake();

        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Company::create(['name' => 'บริษัท เชียงใหม่ค้าไม้ จำกัด']);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('status', 'further_study')
            ->set('institution_name', 'เชียงใหม่')
            ->assertDontSee('บริษัท เชียงใหม่ค้าไม้ จำกัด');
    }

    /**
     * @return array{province: ThaiProvince, district: ThaiDistrict, subdistrict: ThaiSubdistrict}
     */
    private function seedGeography(): array
    {
        $province = ThaiProvince::create(['id' => 50, 'name_th' => 'เชียงใหม่', 'lat' => 18.79, 'lng' => 98.98]);
        $district = ThaiDistrict::create(['id' => 5001, 'name_th' => 'เมืองเชียงใหม่', 'province_id' => $province->id]);
        $subdistrict = ThaiSubdistrict::create(['id' => 500101, 'name_th' => 'ศรีภูมิ', 'district_id' => $district->id]);

        return compact('province', 'district', 'subdistrict');
    }

    public function test_location_fields_appear_for_working_and_further_study_status_but_not_others(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        $component = Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id);

        $component->assertDontSee('จังหวัด');

        $component->set('status', 'employed')->assertSee('จังหวัด');
        $component->set('status', 'further_study')->assertSee('จังหวัด');
        $component->set('status', 'unemployed')->assertDontSee('จังหวัด');
    }

    public function test_changing_province_resets_the_previously_selected_district_and_subdistrict(): void
    {
        ['province' => $province, 'district' => $district, 'subdistrict' => $subdistrict] = $this->seedGeography();
        $otherProvince = ThaiProvince::create(['id' => 10, 'name_th' => 'นนทบุรี']);

        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        $component = Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('status', 'employed')
            ->set('work_province_id', $province->id)
            ->set('work_district_id', $district->id)
            ->set('work_subdistrict_id', $subdistrict->id);

        $component->set('work_province_id', $otherProvince->id);

        $this->assertNull($component->get('work_district_id'));
        $this->assertNull($component->get('work_subdistrict_id'));
    }

    public function test_saving_records_the_selected_work_location(): void
    {
        ['province' => $province, 'district' => $district, 'subdistrict' => $subdistrict] = $this->seedGeography();

        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($teacher)
            ->test(CareerStatusForm::class)
            ->call('selectStudent', $student->id)
            ->set('academic_year_id', $year->id)
            ->set('status', 'employed')
            ->set('company_name', 'บริษัท ทดสอบ จำกัด')
            ->set('employment_type', 'full_time')
            ->set('work_province_id', $province->id)
            ->set('work_district_id', $district->id)
            ->set('work_subdistrict_id', $subdistrict->id)
            ->set('effective_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('career_statuses', [
            'student_id' => $student->id,
            'work_province_id' => $province->id,
            'work_district_id' => $district->id,
            'work_subdistrict_id' => $subdistrict->id,
        ]);
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
