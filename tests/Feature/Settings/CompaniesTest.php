<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Livewire\Settings\Companies as CompaniesSettings;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Company;
use App\Models\Student;
use App\Models\User;
use App\Support\CompanyImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CompaniesTest extends TestCase
{
    use RefreshDatabase;

    /** A CSV shaped like the DBD open-data juristic person export. */
    private function dbdCsv(string ...$rows): UploadedFile
    {
        $lines = array_merge(
            ['เลขทะเบียนนิติบุคคล,ชื่อนิติบุคคล,ประเภทนิติบุคคล,สถานะนิติบุคคล,จังหวัด,อำเภอ'],
            $rows
        );

        return UploadedFile::fake()->createWithContent('dbd.csv', implode("\n", $lines));
    }

    public function test_only_admin_can_reach_the_company_database_page(): void
    {
        $this->get('/settings/companies')->assertRedirect('/login');

        $head = User::factory()->create(['role' => UserRole::DepartmentHead]);
        $this->actingAs($head)->get('/settings/companies')->assertForbidden();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/settings/companies')->assertOk()->assertSee('ฐานข้อมูลบริษัท');
    }

    public function test_admin_can_import_a_dbd_csv(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = $this->dbdCsv(
            '0455561000123,บริษัท ทดสอบ จำกัด,บริษัทจำกัด,ยังดำเนินกิจการอยู่,ร้อยเอ็ด,เมืองร้อยเอ็ด',
            '0453548000456,ห้างหุ้นส่วนจำกัด รุ่งเรืองการช่าง,ห้างหุ้นส่วนจำกัด,ยังดำเนินกิจการอยู่,ร้อยเอ็ด,เสลภูมิ',
        );

        Livewire::actingAs($admin)
            ->test(CompaniesSettings::class)
            ->set('file', $csv)
            ->call('importFile')
            ->assertHasNoErrors();

        $this->assertSame(2, Company::count());
        $this->assertDatabaseHas('companies', [
            'juristic_id' => '0455561000123',
            'name' => 'บริษัท ทดสอบ จำกัด',
            'type' => 'บริษัทจำกัด',
            'province' => 'ร้อยเอ็ด',
            'source' => 'dbd',
        ]);
    }

    public function test_importing_the_same_file_again_updates_rather_than_duplicates(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $row = '0455561000123,บริษัท ทดสอบ จำกัด,บริษัทจำกัด,ยังดำเนินกิจการอยู่,ร้อยเอ็ด,เมืองร้อยเอ็ด';

        foreach ([1, 2] as $ignored) {
            Livewire::actingAs($admin)
                ->test(CompaniesSettings::class)
                ->set('file', $this->dbdCsv($row))
                ->call('importFile')
                ->assertHasNoErrors();
        }

        $this->assertSame(1, Company::count());
    }

    public function test_the_province_filter_keeps_the_table_to_what_the_college_needs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = $this->dbdCsv(
            '0455561000123,บริษัท ในจังหวัด จำกัด,บริษัทจำกัด,ยังดำเนินกิจการอยู่,ร้อยเอ็ด,เมืองร้อยเอ็ด',
            '0105558000789,บริษัท ต่างจังหวัด จำกัด,บริษัทจำกัด,ยังดำเนินกิจการอยู่,กรุงเทพมหานคร,ปทุมวัน',
        );

        Livewire::actingAs($admin)
            ->test(CompaniesSettings::class)
            ->set('onlyProvince', 'ร้อยเอ็ด')
            ->set('file', $csv)
            ->call('importFile')
            ->assertHasNoErrors();

        $this->assertSame(1, Company::count());
        $this->assertDatabaseHas('companies', ['name' => 'บริษัท ในจังหวัด จำกัด']);
        $this->assertDatabaseMissing('companies', ['name' => 'บริษัท ต่างจังหวัด จำกัด']);
    }

    public function test_a_file_without_a_name_column_reports_the_headers_it_did_find(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = UploadedFile::fake()->createWithContent(
            'wrong.csv',
            "รหัส,ยอดขาย\n001,500\n"
        );

        Livewire::actingAs($admin)
            ->test(CompaniesSettings::class)
            ->set('file', $csv)
            ->call('importFile')
            ->assertHasErrors('file');

        $this->assertSame(0, Company::count());
    }

    public function test_names_already_on_career_statuses_can_be_pulled_in(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        $student = Student::factory()->create(['academic_year_id' => $year->id]);

        CareerStatus::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'company_name' => 'บริษัท เคยกรอกไว้ จำกัด',
        ]);

        Livewire::actingAs($admin)
            ->test(CompaniesSettings::class)
            ->call('importFromExisting');

        $this->assertDatabaseHas('companies', [
            'name' => 'บริษัท เคยกรอกไว้ จำกัด',
            'source' => 'entered',
        ]);
    }

    public function test_matching_puts_prefix_matches_first(): void
    {
        Company::create(['name' => 'ห้างหุ้นส่วนจำกัด ทดสอบการช่าง']);
        Company::create(['name' => 'ทดสอบ อุตสาหกรรม จำกัด']);

        $names = Company::matching('ทดสอบ')->pluck('name')->all();

        $this->assertSame('ทดสอบ อุตสาหกรรม จำกัด', $names[0]);
        $this->assertCount(2, $names);
    }

    public function test_a_newly_typed_workplace_is_remembered_for_next_time(): void
    {
        Company::remember('บริษัท ยังไม่เคยมี จำกัด');
        Company::remember('บริษัท ยังไม่เคยมี จำกัด');

        $this->assertSame(1, Company::where('name', 'บริษัท ยังไม่เคยมี จำกัด')->count());
    }

    public function test_the_importer_leaves_a_dbd_row_alone_when_the_same_name_is_typed_in(): void
    {
        Company::create([
            'name' => 'บริษัท ทดสอบ จำกัด',
            'juristic_id' => '0455561000123',
            'source' => 'dbd',
        ]);

        Company::remember('บริษัท ทดสอบ จำกัด');

        $this->assertSame(1, Company::count());
        $this->assertSame('dbd', Company::first()->source);
    }

    public function test_the_artisan_command_imports_a_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dbd').'.csv';
        file_put_contents($path, "ชื่อนิติบุคคล,จังหวัด\nบริษัท ผ่านคำสั่ง จำกัด,ร้อยเอ็ด\n");

        $this->artisan('companies:import', ['file' => $path])->assertSuccessful();

        $this->assertDatabaseHas('companies', ['name' => 'บริษัท ผ่านคำสั่ง จำกัด']);

        unlink($path);
    }

    public function test_importing_from_a_file_that_does_not_exist_fails_cleanly(): void
    {
        $this->artisan('companies:import', ['file' => 'nope.csv'])->assertFailed();
    }

    public function test_the_importer_can_be_pointed_at_a_path_directly(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dbd').'.csv';
        file_put_contents($path, "ชื่อนิติบุคคล\nบริษัท เรียกตรง จำกัด\n");

        $result = (new CompanyImporter)->import($path);

        $this->assertSame(1, $result['imported']);
        unlink($path);
    }

    public function test_pasting_the_per_company_api_url_is_explained_rather_than_silently_failing(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CompaniesSettings::class)
            ->set('url', 'https://openapi.dbd.go.th/api/v1/juristic_person/{OrganizationJuristicID}')
            ->call('importUrl')
            ->assertHasErrors('url');

        $this->assertSame(0, Company::count());
    }

    public function test_a_spreadsheet_is_read_even_when_it_is_named_csv(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $path = tempnam(sys_get_temp_dir(), 'dbd').'.xlsx';
        Excel::store(new class implements FromArray
        {
            public function array(): array
            {
                return [
                    ['เลขทะเบียนนิติบุคคล', 'ชื่อนิติบุคคล', 'จังหวัด'],
                    ['0455561000999', 'บริษัท จากเอกซ์เซล จำกัด', 'ร้อยเอ็ด'],
                ];
            }
        }, basename($path), 'local');

        $stored = storage_path('app/private/'.basename($path));
        $this->assertFileExists($stored);

        $result = (new CompanyImporter)->import($stored);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('companies', ['name' => 'บริษัท จากเอกซ์เซล จำกัด']);

        @unlink($stored);
        @unlink($path);
    }
}
