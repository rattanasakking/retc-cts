<?php

namespace Tests\Feature\AuditLogs;

use App\Enums\UserRole;
use App\Livewire\Reports\ExportCenter;
use App\Livewire\Students\StudentImporter;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImportExportAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_importing_a_csv_writes_an_import_csv_audit_log_attributed_to_the_uploader(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $csv = "student_code,first_name,last_name,academic_year,status\n".
            "67-00001,สมชาย,ใจดี,2569,graduated\n";

        Livewire::actingAs($admin)
            ->test(StudentImporter::class)
            ->set('file', UploadedFile::fake()->createWithContent('students.csv', $csv))
            ->call('import');

        $log = AuditLog::where('action', 'import_csv')->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('นำเข้าข้อมูล', $log->module);
        $this->assertStringContainsString('students.csv', $log->description);
        $this->assertStringContainsString('สำเร็จ 1 แถว', $log->description);
    }

    public function test_exporting_excel_writes_an_export_excel_audit_log(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($admin)
            ->test(ExportCenter::class)
            ->set('academicYearId', $year->id)
            ->call('exportExcel');

        $log = AuditLog::where('action', 'export_excel')->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('รายงาน', $log->module);
        $this->assertStringContainsString((string) $year->year, $log->description);
    }

    public function test_exporting_pdf_writes_an_export_pdf_audit_log(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id]);

        Livewire::actingAs($admin)
            ->test(ExportCenter::class)
            ->set('academicYearId', $year->id)
            ->call('exportPdf');

        $log = AuditLog::where('action', 'export_pdf')->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('รายงาน', $log->module);
    }
}
