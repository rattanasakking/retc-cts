<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutRoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_neither_reports_nor_settings_link(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $response = $this->actingAs($teacher)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('ส่งออกรายงาน');
        $response->assertDontSee('ตั้งค่าระบบ');
    }

    public function test_department_head_sees_reports_but_not_settings(): void
    {
        $departmentHead = User::factory()->create(['role' => UserRole::DepartmentHead]);

        $response = $this->actingAs($departmentHead)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('ส่งออกรายงาน');
        $response->assertDontSee('ตั้งค่าระบบ');
    }

    public function test_admin_sees_both_reports_and_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('ส่งออกรายงาน');
        $response->assertSee('ตั้งค่าระบบ');
    }
}
