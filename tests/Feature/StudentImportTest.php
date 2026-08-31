<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Students\StudentImporter;
use App\Models\ImportLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('students.csv', $content);
    }

    public function test_guests_and_non_privileged_roles_cannot_reach_the_import_page(): void
    {
        $this->get('/students/import')->assertRedirect('/login');

        $teacher = User::factory()->create(['role' => UserRole::Teacher]);
        $this->actingAs($teacher)->get('/students/import')->assertForbidden();
    }

    public function test_admin_can_import_valid_students_from_csv(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "student_code,first_name,last_name,academic_year,status\n".
            "67-00001,สมชาย,ใจดี,2569,graduated\n".
            "67-00002,สมหญิง,รักเรียน,2569,graduated\n";

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('file', $this->csv($csv))
            ->call('import');

        $this->assertDatabaseCount('students', 2);
        $this->assertDatabaseHas('students', ['student_code' => '67-00001', 'first_name' => 'สมชาย']);
        $this->assertDatabaseHas('students', ['student_code' => '67-00002', 'first_name' => 'สมหญิง']);

        $log = ImportLog::first();
        $this->assertSame('students', $log->type);
        $this->assertSame('completed', $log->status);
        $this->assertSame(2, $log->total_rows);
        $this->assertSame(2, $log->imported_rows);
        $this->assertSame(0, $log->failed_rows);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_invalid_rows_are_rejected_and_logged_without_failing_the_whole_import(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "student_code,first_name,last_name,academic_year,status\n".
            "67-00003,สมชาย,ใจดี,2569,graduated\n".
            ",สมหญิง,รักเรียน,2569,graduated\n"; // missing required student_code

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('file', $this->csv($csv))
            ->call('import');

        $this->assertDatabaseCount('students', 1);

        $log = ImportLog::first();
        $this->assertSame(2, $log->total_rows);
        $this->assertSame(1, $log->imported_rows);
        $this->assertSame(1, $log->failed_rows);
        $this->assertNotEmpty($log->errors);
    }

    public function test_rows_with_nothing_left_to_update_are_counted_as_skipped(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "student_code,first_name,last_name,academic_year,status\n".
            "67-00030,สมชาย,ใจดี,2569,graduated\n";

        // นำเข้าไฟล์เดิมซ้ำในโหมดอัปเดต — ไม่มีอะไรให้เติมแล้ว
        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('updateExisting', true)
            ->set('file', $this->csv($csv))
            ->call('import');

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('updateExisting', true)
            ->set('file', $this->csv($csv))
            ->call('import');

        $second = ImportLog::orderByDesc('id')->first();

        $this->assertSame(1, $second->total_rows);
        $this->assertSame(0, $second->imported_rows);
        $this->assertSame(0, $second->failed_rows);
        $this->assertSame(1, $second->skipped_rows); // ไม่หายไปเฉยๆ อีกต่อไป
        $this->assertDatabaseCount('students', 1);
    }

    public function test_validation_failures_are_recorded_in_thai(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "student_code,first_name,last_name,national_id,academic_year,status\n".
            "67-00010,สมชาย,,1234567890123,2569,graduated\n".  // ไม่มีนามสกุล
            "67-00011,สมหญิง,รักเรียน,12345,2569,graduated\n"; // เลขบัตรไม่ครบ 13 หลัก

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('file', $this->csv($csv))
            ->call('import');

        $messages = collect(ImportLog::first()->errors)->pluck('messages')->flatten();

        $this->assertTrue($messages->contains('ไม่ได้กรอกนามสกุล'));
        $this->assertTrue($messages->contains('เลขบัตรประชาชน ต้องมี 13 หลัก'));
    }

    public function test_admin_can_reopen_the_error_details_of_a_past_import(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "student_code,first_name,last_name,academic_year,status\n".
            "67-00020,สมชาย,ใจดี,2569,graduated\n".
            ",สมหญิง,รักเรียน,2569,graduated\n";

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('file', $this->csv($csv))
            ->call('import');

        $log = ImportLog::first();

        // A fresh visit to the page — the import panel is gone, but the
        // history row must still be able to show why that row was rejected.
        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->assertDontSee('รายละเอียดข้อผิดพลาดในการนำเข้า')
            ->call('viewErrors', $log->id)
            ->assertSee('รายละเอียดข้อผิดพลาดในการนำเข้า')
            ->assertSee('ไม่ได้กรอกรหัสนักศึกษา')
            ->call('closeErrors')
            ->assertDontSee('รายละเอียดข้อผิดพลาดในการนำเข้า');
    }

    public function test_the_error_summary_groups_the_same_cause_together(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Student::factory()->create(['student_code' => 'DUP-100']);
        Student::factory()->create(['student_code' => 'DUP-200']);

        $csv = "student_code,first_name,last_name,academic_year,status\n".
            "DUP-100,สมชาย,ใจดี,2569,graduated\n".
            "DUP-200,สมหญิง,รักเรียน,2569,graduated\n";

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('file', $this->csv($csv))
            ->call('import');

        $summary = Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->call('viewErrors', ImportLog::first()->id)
            ->instance()
            ->errorSummary;

        // ทั้งสองแถวตกด้วยเหตุผลเดียวกัน ต่างกันแค่ตัวรหัส — ต้องถูกรวมเป็นบรรทัดเดียว
        $this->assertCount(1, $summary);
        $this->assertSame(2, $summary[0]['count']);
        $this->assertStringContainsString('ซ้ำกับข้อมูลที่มีอยู่แล้ว', $summary[0]['message']);
    }

    public function test_duplicate_student_code_against_existing_data_is_rejected(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Student::factory()->create(['student_code' => 'DUP-001']);

        $csv = "student_code,first_name,last_name,academic_year,status\n".
            "DUP-001,สมชาย,ใจดี,2569,graduated\n";

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('file', $this->csv($csv))
            ->call('import');

        $this->assertDatabaseCount('students', 1); // still just the pre-existing one

        $log = ImportLog::first();
        $this->assertSame(0, $log->imported_rows);
        $this->assertSame(1, $log->failed_rows);
        $this->assertStringContainsString('ซ้ำ', $log->errors[0]['messages'][0]);
    }

    public function test_update_existing_mode_fills_only_currently_empty_fields(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $student = Student::factory()->create([
            'student_code' => 'UPD-001',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'birth_date' => null,
            'phone' => null,
            'email' => 'original@test.com', // already set — must survive the import untouched
        ]);

        $csv = "student_code,first_name,last_name,birth_date,phone,email,academic_year,status\n".
            "UPD-001,สมชาย,ใจดี,2007-10-02,0812345678,incoming@test.com,2569,graduated\n";

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('updateExisting', true)
            ->set('file', $this->csv($csv))
            ->call('import');

        $this->assertDatabaseCount('students', 1); // no new row created

        $student->refresh();
        $this->assertSame('2007-10-02', $student->birth_date->toDateString());
        $this->assertSame('0812345678', $student->phone);
        $this->assertSame('original@test.com', $student->email); // untouched, was already set

        $log = ImportLog::first();
        $this->assertSame(1, $log->imported_rows);
        $this->assertSame(1, $log->updated_rows);
        $this->assertSame(0, $log->failed_rows);
    }

    public function test_duplicate_student_code_is_still_rejected_when_update_existing_is_off(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Student::factory()->create(['student_code' => 'UPD-002', 'birth_date' => null]);

        $csv = "student_code,first_name,last_name,birth_date,academic_year,status\n".
            "UPD-002,สมชาย,ใจดี,2007-10-02,2569,graduated\n";

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('updateExisting', false)
            ->set('file', $this->csv($csv))
            ->call('import');

        $log = ImportLog::first();
        $this->assertSame(0, $log->imported_rows);
        $this->assertSame(0, $log->updated_rows);
        $this->assertSame(1, $log->failed_rows);
    }

    public function test_duplicate_student_code_within_the_same_file_is_rejected(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "student_code,first_name,last_name,academic_year,status\n".
            "67-00009,สมชาย,ใจดี,2569,graduated\n".
            "67-00009,สมหญิง,รักเรียน,2569,graduated\n";

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('file', $this->csv($csv))
            ->call('import');

        $this->assertDatabaseCount('students', 1);

        $log = ImportLog::first();
        $this->assertSame(1, $log->imported_rows);
        $this->assertSame(1, $log->failed_rows);
    }
}
