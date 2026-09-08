<?php

namespace Tests\Feature;

use App\Livewire\Public\CareerStatusSelfReport;
use App\Models\AcademicYear;
use App\Models\Company;
use App\Models\Student;
use App\Support\OpenStreetMapCompanies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_juristic_wrapper_is_ignored_when_matching(): void
    {
        Company::create(['name' => 'บริษัท ซีพี ออลล์ จำกัด (มหาชน)']);

        // The words people actually say, none of which start the stored name.
        $this->assertSame(1, Company::matching('ซีพี')->count());
        $this->assertSame(1, Company::matching('ออลล์')->count());
        $this->assertSame(1, Company::matching('ซีพี ออลล์')->count());
    }

    public function test_words_match_in_any_order(): void
    {
        Company::create(['name' => 'บริษัท ไทยซัมมิท ออโตพาร์ท จำกัด']);

        $this->assertSame(1, Company::matching('ออโตพาร์ท ไทยซัมมิท')->count());
    }

    public function test_every_word_typed_has_to_appear(): void
    {
        Company::create(['name' => 'บริษัท ไทยซัมมิท ออโตพาร์ท จำกัด']);

        $this->assertSame(0, Company::matching('ไทยซัมมิท มอเตอร์')->count());
    }

    public function test_a_partnership_prefix_is_ignored_too(): void
    {
        Company::create(['name' => 'ห้างหุ้นส่วนจำกัด รุ่งเรืองการช่าง']);

        $this->assertSame(1, Company::matching('รุ่งเรือง')->count());
        $this->assertSame(1, Company::matching('หจก. รุ่งเรืองการช่าง')->count());
    }

    public function test_names_starting_with_the_search_come_first(): void
    {
        Company::create(['name' => 'ห้างหุ้นส่วนจำกัด ทดสอบการช่าง']);
        Company::create(['name' => 'บริษัท ทดสอบ อุตสาหกรรม จำกัด']);

        $names = Company::matching('ทดสอบ')->pluck('name')->all();

        $this->assertSame('บริษัท ทดสอบ อุตสาหกรรม จำกัด', $names[0]);
    }

    public function test_search_name_is_kept_in_sync_on_save(): void
    {
        $company = Company::create(['name' => 'บริษัท ตัวอย่าง จำกัด']);
        $this->assertSame('ตัวอย่าง', $company->search_name);

        $company->update(['name' => 'ห้างหุ้นส่วนจำกัด ตัวอย่างใหม่']);
        $this->assertSame('ตัวอย่างใหม่', $company->fresh()->search_name);
    }

    private function verifiedForm()
    {
        $year = AcademicYear::factory()->create(['is_active' => true]);
        $student = Student::factory()->create(['academic_year_id' => $year->id, 'birth_date' => '2007-10-02']);

        return Livewire::test(CareerStatusSelfReport::class)
            ->call('selectCandidate', $student->id)
            ->set('birthDateInput', '2007-10-02')
            ->call('verify')
            ->set('status', 'employed');
    }

    public function test_the_online_search_button_appears_only_when_the_local_table_has_nothing(): void
    {
        Company::create(['name' => 'บริษัท มีอยู่แล้ว จำกัด']);

        $this->verifiedForm()
            ->set('company_name', 'มีอยู่แล้ว')
            ->assertDontSee('ค้นหาจากแผนที่ OpenStreetMap')
            ->set('company_name', 'ร้านที่ไม่มีในระบบ')
            ->assertSee('ค้นหาจากแผนที่ OpenStreetMap');
    }

    public function test_picking_an_openstreetmap_result_fills_the_form_and_saves_it(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'name' => 'อู่ช่างเอ การช่าง',
                    'display_name' => 'อู่ช่างเอ การช่าง, ในเมือง, เมืองร้อยเอ็ด, จังหวัดร้อยเอ็ด, ประเทศไทย',
                    'address' => ['province' => 'จังหวัดร้อยเอ็ด', 'county' => 'เมืองร้อยเอ็ด'],
                ],
            ]),
        ]);

        $this->verifiedForm()
            ->set('company_name', 'อู่ช่างเอ')
            ->call('searchOnline')
            ->assertSee('อู่ช่างเอ การช่าง')
            ->call('useOnlineResult', 0)
            ->assertSet('company_name', 'อู่ช่างเอ การช่าง');

        // Stored for the next student, with the จังหวัด prefix stripped so it
        // lines up with the thai_provinces table.
        $this->assertDatabaseHas('companies', [
            'name' => 'อู่ช่างเอ การช่าง',
            'source' => 'osm',
            'province' => 'ร้อยเอ็ด',
        ]);
    }

    public function test_the_form_still_works_when_openstreetmap_is_unreachable(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response('', 503)]);

        $this->verifiedForm()
            ->set('company_name', 'ร้านที่ไม่มีในระบบ')
            ->call('searchOnline')
            ->assertHasNoErrors()
            ->assertSee('ไม่พบในแผนที่เช่นกัน');
    }

    public function test_lookups_are_cached_so_the_same_wording_is_not_asked_twice(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['name' => 'ร้านทดสอบ', 'display_name' => 'ร้านทดสอบ', 'address' => []],
            ]),
        ]);

        $osm = app(OpenStreetMapCompanies::class);
        $osm->search('ร้านทดสอบ');
        $osm->search('ร้านทดสอบ');

        Http::assertSentCount(1);
    }

    public function test_the_lookup_can_be_turned_off(): void
    {
        config(['companies.osm_lookup' => false]);
        Http::fake();

        $this->assertSame([], app(OpenStreetMapCompanies::class)->search('อะไรก็ได้'));
        Http::assertNothingSent();
    }
}
