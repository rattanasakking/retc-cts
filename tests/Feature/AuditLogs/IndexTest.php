<?php

namespace Tests\Feature\AuditLogs;

use App\Enums\UserRole;
use App\Livewire\AuditLogs\Index;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(array $overrides = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'action' => 'create',
            'module' => 'นักศึกษา',
            'description' => 'ทดสอบรายการ',
        ], $overrides));
    }

    public function test_only_admin_can_view_the_audit_log_page(): void
    {
        $this->get('/audit-logs')->assertRedirect('/login');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/audit-logs')->assertOk();

        foreach ([UserRole::Teacher, UserRole::Executive, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/audit-logs')->assertForbidden();
        }
    }

    public function test_search_matches_description_or_user_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'name' => 'Somsak Manager']);

        $byDescription = $this->makeLog(['description' => 'ลบข้อมูลนักศึกษา ABC-999', 'user_id' => $admin->id]);
        $byUserName = $this->makeLog(['description' => 'สร้างข้อมูล', 'user_id' => $admin->id]);
        $unrelated = $this->makeLog(['description' => 'ไม่เกี่ยวข้อง']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('search', 'ABC-999')
            ->assertSee($byDescription->description)
            ->assertDontSee($unrelated->description);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('search', 'Somsak')
            ->assertSee($byUserName->description)
            ->assertDontSee($unrelated->description);
    }

    public function test_filters_by_date_range(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $old = $this->makeLog(['description' => 'เก่ามาก']);
        $old->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();

        $recent = $this->makeLog(['description' => 'เพิ่งเกิด']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('dateFrom', now()->subDay()->toDateString())
            ->assertSee($recent->description)
            ->assertDontSee($old->description);
    }

    public function test_filters_by_user_module_and_action(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $adminLog = $this->makeLog(['description' => 'โดยแอดมิน', 'user_id' => $admin->id, 'module' => 'นักศึกษา', 'action' => 'create']);
        $teacherLog = $this->makeLog(['description' => 'โดยครู', 'user_id' => $teacher->id, 'module' => 'ผู้ใช้งาน', 'action' => 'update']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('userId', $admin->id)
            ->assertSee($adminLog->description)
            ->assertDontSee($teacherLog->description);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('module', 'ผู้ใช้งาน')
            ->assertSee($teacherLog->description)
            ->assertDontSee($adminLog->description);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('action', 'update')
            ->assertSee($teacherLog->description)
            ->assertDontSee($adminLog->description);
    }

    public function test_it_paginates_results(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Creating the admin above already wrote its own "create" audit log
        // (the Auditable trait on User) — clear it so the count below only
        // reflects the 25 rows this test explicitly creates.
        AuditLog::query()->delete();

        for ($i = 0; $i < 25; $i++) {
            $this->makeLog();
        }

        $component = Livewire::actingAs($admin)->test(Index::class);

        $this->assertCount(20, $component->viewData('logs')->items());
        $this->assertSame(25, $component->viewData('logs')->total());
    }

    public function test_export_log_downloads_a_file(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->makeLog();

        $response = Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('exportLog');

        $response->assertFileDownloaded();
    }
}
