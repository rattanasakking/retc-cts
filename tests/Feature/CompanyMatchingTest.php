<?php

namespace Tests\Feature;

use App\Livewire\Public\CareerStatusSelfReport;
use App\Models\AcademicYear;
use App\Models\Company;
use App\Models\Student;
use App\Support\OpenStreetMapCompanies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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

    public function test_an_unknown_name_is_looked_up_without_being_asked(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['name' => 'ร้านจากแผนที่', 'display_name' => 'ร้านจากแผนที่, ร้อยเอ็ด', 'address' => []],
            ]),
        ]);

        // No button press — typing is enough.
        $this->verifiedForm()
            ->set('company_name', 'ร้านที่ไม่มีในระบบ')
            ->assertSee('ร้านจากแผนที่');

        Http::assertSentCount(1);
    }

    public function test_local_and_map_results_are_offered_side_by_side(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['name' => 'ร้านชื่อคล้ายกันจากแผนที่', 'display_name' => 'ร้านชื่อคล้ายกันจากแผนที่', 'address' => []],
            ]),
        ]);

        Company::create(['name' => 'บริษัท ชื่อคล้ายกัน จำกัด']);

        $this->verifiedForm()
            ->set('company_name', 'ชื่อคล้ายกัน')
            ->assertSee('บริษัท ชื่อคล้ายกัน จำกัด')   // จากฐานข้อมูลของระบบ
            ->assertSee('ร้านชื่อคล้ายกันจากแผนที่')     // จากแผนที่
            ->assertSee('ในระบบ')
            ->assertSee('จากแผนที่');
    }

    public function test_a_short_term_is_not_looked_up_online(): void
    {
        Http::fake();

        $this->verifiedForm()->set('company_name', 'ทด');

        Http::assertNothingSent();
    }

    public function test_the_throttle_drops_a_second_lookup_within_the_same_second(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['name' => 'ร้านหนึ่ง', 'display_name' => 'ร้านหนึ่ง', 'address' => []],
            ]),
        ]);

        $osm = app(OpenStreetMapCompanies::class);

        $this->assertNotSame([], $osm->search('คำค้นแรก'));
        // Different wording, so the cache cannot answer it — the rate limit does.
        $this->assertSame([], $osm->search('คำค้นที่สอง'));

        Http::assertSentCount(1);
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
            ->assertSee('อู่ช่างเอ การช่าง')
            ->call('usePlaceSuggestion', 'company_name', 0)
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
            ->assertHasNoErrors()
            ->assertSee('ไม่พบชื่อสถานประกอบการนี้');
    }

    public function test_the_form_still_works_when_the_database_is_behind_on_migrations(): void
    {
        Http::fake();

        // A college that pulled the code but never ran migrate: the columns
        // the lookup relies on are not there. Typing must still not error out,
        // and the submission must still go through.
        Schema::table('companies', function ($table) {
            $table->dropIndex(['kind', 'search_name']);
            $table->dropIndex(['search_name']);
            $table->dropColumn(['kind', 'search_name']);
        });

        $this->verifiedForm()
            ->set('company_name', 'ร้านที่ค้นไม่ได้')
            ->assertHasNoErrors()
            ->assertSet('placeSuggestions.company_name', []);

        Company::remember('ร้านที่ค้นไม่ได้', ['kind' => Company::COMPANY]);

        $this->assertDatabaseMissing('companies', ['name' => 'ร้านที่ค้นไม่ได้']);
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
