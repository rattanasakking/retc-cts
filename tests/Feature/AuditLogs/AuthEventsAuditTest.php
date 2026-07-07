<?php

namespace Tests\Feature\AuditLogs;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthEventsAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_in_writes_a_login_audit_log(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $log = AuditLog::where('action', 'login')->firstOrFail();

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('ระบบยืนยันตัวตน', $log->module);
        $this->assertStringContainsString($user->name, $log->description);
    }

    public function test_a_failed_login_attempt_writes_no_audit_log(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password')
            ->call('login');

        $this->assertSame(0, AuditLog::where('action', 'login')->count());
    }

    public function test_logging_out_writes_a_logout_audit_log(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Volt::test('layout.navigation')->call('logout');

        $log = AuditLog::where('action', 'logout')->firstOrFail();

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('ระบบยืนยันตัวตน', $log->module);
    }
}
