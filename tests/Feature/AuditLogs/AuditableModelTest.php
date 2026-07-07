<?php

namespace Tests\Feature\AuditLogs;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditableModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_student_writes_a_create_audit_log(): void
    {
        $student = Student::factory()->create(['first_name' => 'สมชาย', 'last_name' => 'ใจดี', 'student_code' => 'AUD-001']);

        $log = AuditLog::where('auditable_type', Student::class)
            ->where('auditable_id', $student->id)
            ->where('action', 'create')
            ->firstOrFail();

        $this->assertSame('นักศึกษา', $log->module);
        $this->assertSame('สมชาย ใจดี (AUD-001)', $log->description);
        $this->assertNull($log->old_values);
        $this->assertSame('AUD-001', $log->new_values['student_code']);
    }

    public function test_updating_a_student_writes_an_update_audit_log_with_old_and_new_values(): void
    {
        $student = Student::factory()->create(['first_name' => 'เดิม']);

        $student->update(['first_name' => 'ใหม่']);

        $log = AuditLog::where('auditable_type', Student::class)
            ->where('auditable_id', $student->id)
            ->where('action', 'update')
            ->firstOrFail();

        $this->assertSame('เดิม', $log->old_values['first_name']);
        $this->assertSame('ใหม่', $log->new_values['first_name']);
    }

    public function test_deleting_a_student_writes_a_delete_audit_log(): void
    {
        $student = Student::factory()->create();
        $studentId = $student->id;

        $student->delete();

        $log = AuditLog::where('auditable_type', Student::class)
            ->where('auditable_id', $studentId)
            ->where('action', 'delete')
            ->firstOrFail();

        $this->assertSame($studentId, $log->old_values['id']);
    }

    public function test_a_no_op_update_writes_no_audit_log(): void
    {
        $student = Student::factory()->create(['first_name' => 'สมชาย']);
        AuditLog::query()->delete();

        $student->update(['first_name' => 'สมชาย']); // same value — no real change

        $this->assertSame(0, AuditLog::count());
    }

    public function test_creating_a_career_status_writes_an_audit_log_naming_the_student_and_status(): void
    {
        $student = Student::factory()->create(['first_name' => 'วิชัย', 'last_name' => 'มั่นคง', 'student_code' => 'AUD-002']);

        $careerStatus = CareerStatus::factory()->create([
            'student_id' => $student->id,
            'status' => \App\Enums\CareerStatusType::Employed,
        ]);

        $log = AuditLog::where('auditable_type', CareerStatus::class)
            ->where('auditable_id', $careerStatus->id)
            ->where('action', 'create')
            ->firstOrFail();

        $this->assertSame('ภาวะการมีงานทำ', $log->module);
        $this->assertSame('วิชัย มั่นคง (AUD-002) — ทำงานแล้ว', $log->description);
    }

    public function test_creating_a_user_writes_an_audit_log_without_leaking_the_password_hash(): void
    {
        $user = User::factory()->create(['name' => 'Admin Two', 'email' => 'admintwo@example.com']);

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('action', 'create')
            ->firstOrFail();

        $this->assertSame('ผู้ใช้งาน', $log->module);
        $this->assertArrayNotHasKey('password', $log->new_values);
        $this->assertArrayNotHasKey('remember_token', $log->new_values);
    }

    public function test_changing_only_a_password_logs_an_update_without_exposing_the_hash(): void
    {
        $user = User::factory()->create();
        AuditLog::query()->delete();

        $user->update(['password' => \Illuminate\Support\Facades\Hash::make('new-password')]);

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('action', 'update')
            ->firstOrFail();

        $this->assertStringContainsString('เปลี่ยนรหัสผ่าน', $log->description);
        $this->assertNull($log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_deleting_a_user_writes_a_delete_audit_log(): void
    {
        $user = User::factory()->create(['role' => UserRole::Teacher]);
        $userId = $user->id;

        $user->delete();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => User::class,
            'auditable_id' => $userId,
            'action' => 'delete',
        ]);
    }

    public function test_deleting_a_user_preserves_audit_history_by_nulling_user_id_not_cascading(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        $student->update(['first_name' => 'triggers a log attributed to nobody in particular']);

        // Attribute an unrelated audit row to this user directly, the way
        // the login listener or AuditLogger's auth()->id() fallback would.
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'module' => 'ระบบยืนยันตัวตน',
            'description' => 'ทดสอบ',
        ]);

        $user->delete();

        $log = AuditLog::where('action', 'login')->where('description', 'ทดสอบ')->firstOrFail();
        $this->assertNull($log->user_id);
    }

    public function test_creating_updating_and_deleting_an_academic_year_writes_audit_logs(): void
    {
        $year = AcademicYear::factory()->create(['year' => 2599]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => AcademicYear::class,
            'auditable_id' => $year->id,
            'action' => 'create',
            'module' => 'ปีการศึกษา',
            'description' => 'ปีการศึกษา 2599',
        ]);

        $year->update(['is_active' => true]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => AcademicYear::class,
            'auditable_id' => $year->id,
            'action' => 'update',
        ]);

        $yearId = $year->id;
        $year->delete();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => AcademicYear::class,
            'auditable_id' => $yearId,
            'action' => 'delete',
        ]);
    }
}
