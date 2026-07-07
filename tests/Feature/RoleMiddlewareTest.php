<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_on_role_protected_routes(): void
    {
        $this->get('/settings/academic-years')->assertRedirect('/login');
    }

    public function test_a_teacher_is_forbidden_from_the_admin_only_route(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)->get('/settings/academic-years')->assertForbidden();
    }

    public function test_an_admin_can_reach_the_admin_only_route(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/settings/academic-years')->assertOk();
    }

    public function test_executives_and_department_heads_can_reach_reports_but_teachers_cannot(): void
    {
        $executive = User::factory()->create(['role' => UserRole::Executive]);
        $departmentHead = User::factory()->create(['role' => UserRole::DepartmentHead]);
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($executive)->get('/reports/export')->assertOk();
        $this->actingAs($departmentHead)->get('/reports/export')->assertOk();
        $this->actingAs($teacher)->get('/reports/export')->assertForbidden();
    }
}
