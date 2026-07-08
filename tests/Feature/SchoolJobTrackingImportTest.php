<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Students\StudentImporter;
use App\Models\ImportLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolJobTrackingImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mirrors the real "รายงานติดตามภาวะการมีงานทำและศึกษาต่อ" export: 4
     * title/metadata rows, a Thai header row, then data rows at fixed
     * column positions (see SchoolJobTrackingImport's class docblock).
     */
    private function reportCsv(string ...$dataRows): UploadedFile
    {
        $lines = [
            'รายงานทดสอบภาวะการมีงานทำ,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            'เป้าหมายปีการศึกษาที่สำเร็จการศึกษา 2569,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            'รอบการติดตามที่ : 1,,,,,,,,,,,,,,,,,,,,,,,,,,,',
            ',สถานศึกษา : วิทยาลัยทดสอบ,,,,,,,,,,,,,,,,,,,,,,,,,,',
            'ชื่อ นามสกุล,,ระดับชั้น,รหัสบัตรประชาชน,ประเภทวิชา,เพศ,ปีที่จบ,เทอมที่จบ,เลขรหัสนักเรียน,วันเกิด,อีเมล,โทรศัพท์,รหัสสาขาวิชา,สาขาวิชา,ปีหลักสูตร,รหัสสถานะการติดตาม,ศึกษาต่อในระดับ,ชื่อสถานศึกษาเรียนต่อ,สถานะเรียนตรงสาย ไม่ตรงสาย,ประเภทวิชา/คณะ,สาขาวิชาที่เรียนต่อ,ชื่อสถานที่ทำงาน,ตำแหน่งงาน,เงินเดือน,ทำงานตรงสาย ไม่ตรงสาย,สถานะการติดตาม,ปีการศึกษาที่จบ,ประเภทนักเรียน',
            ...$dataRows,
        ];

        return UploadedFile::fake()->createWithContent('school-report.csv', implode("\n", $lines));
    }

    public function test_admin_can_import_the_school_job_tracking_report_directly(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['role' => UserRole::Admin]); // a second recipient, to make the notification assertion meaningful

        $csv = $this->reportCsv(
            // employed row
            'สมชาย ใจดี,,ประกาศนียบัตรวิชาชีพ 3,1234567890123,อุตสาหกรรม,,2569,,67-00001,,somchai@test.com,0812345678,20101,ช่างยนต์,,,,,,,,บริษัท ทดสอบ จำกัด,พนักงาน,"9,001 - 15,000",ตรง,ตรง,2569,ปกติ',
            // not yet surveyed — neither workplace nor further-study filled
            'สมหญิง รักเรียน,,ประกาศนียบัตรวิชาชีพ 3,,อุตสาหกรรม,,2569,,67-00002,,,,,,,,,,,,,,,,,2569,ปกติ',
        );

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('format', 'school_report')
            ->set('file', $csv)
            ->call('import');

        $this->assertDatabaseCount('students', 2);

        $employed = Student::where('student_code', '67-00001')->first();
        $this->assertSame('สมชาย', $employed->first_name);
        $this->assertSame('ใจดี', $employed->last_name);
        $this->assertSame('graduated', $employed->status);
        $this->assertSame('ช่างยนต์', $employed->program);

        $this->assertDatabaseHas('career_statuses', [
            'student_id' => $employed->id,
            'status' => 'employed',
            'company_name' => 'บริษัท ทดสอบ จำกัด',
            'position' => 'พนักงาน',
            'is_related_to_major' => true,
        ]);
        $this->assertSame(12000.50, (float) $employed->currentCareerStatus->monthly_salary);

        $notSurveyed = Student::where('student_code', '67-00002')->first();
        $this->assertNotNull($notSurveyed);
        $this->assertDatabaseCount('career_statuses', 1); // only the employed row got one

        $log = ImportLog::first();
        $this->assertSame('completed', $log->status);
        $this->assertSame(2, $log->total_rows);
        $this->assertSame(2, $log->imported_rows);
        $this->assertSame(0, $log->failed_rows);

        // A bulk import must not flood every admin with a per-row notification.
        Notification::assertNothingSent();
    }

    public function test_further_study_row_records_a_further_study_career_status(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = $this->reportCsv(
            'มานี มีนา,,ประกาศนียบัตรวิชาชีพ 3,,อุตสาหกรรม,,2569,,67-00003,,,,,,,,,วิทยาลัยเทคนิคทดสอบ,ตรง,,วิศวกรรมคอมพิวเตอร์,,,,,,2569,ปกติ',
        );

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('format', 'school_report')
            ->set('file', $csv)
            ->call('import');

        $student = Student::where('student_code', '67-00003')->first();

        $this->assertDatabaseHas('career_statuses', [
            'student_id' => $student->id,
            'status' => 'further_study',
            'is_related_to_major' => true,
        ]);
        $this->assertStringContainsString('วิทยาลัยเทคนิคทดสอบ', $student->currentCareerStatus->notes);
        Notification::assertNothingSent();
    }

    public function test_birth_date_column_is_parsed_from_dd_mm_yyyy_gregorian_into_a_stored_date(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = $this->reportCsv(
            'ธีรวัฒน์ ยิ่งกำแหง,,ประกาศนียบัตรวิชาชีพ 3,1459901234462,อุตสาหกรรม,,2569,,67-00004,02/10/2007,,,,,,,,,,,,,,,,,2569,ปกติ',
        );

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('format', 'school_report')
            ->set('file', $csv)
            ->call('import');

        $student = Student::where('student_code', '67-00004')->first();

        $this->assertNotNull($student->birth_date);
        $this->assertSame('2007-10-02', $student->birth_date->toDateString());
    }

    public function test_rows_missing_the_student_code_column_are_rejected_and_logged(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = $this->reportCsv(
            'ไม่มี รหัส,,ประกาศนียบัตรวิชาชีพ 3,,อุตสาหกรรม,,2569,,,,,,,,,,,,,,,,,,,,2569,ปกติ',
        );

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('format', 'school_report')
            ->set('file', $csv)
            ->call('import');

        $this->assertDatabaseCount('students', 0);

        $log = ImportLog::first();
        $this->assertSame(0, $log->imported_rows);
        $this->assertSame(1, $log->failed_rows);
        $this->assertNotEmpty($log->errors);
    }
}
