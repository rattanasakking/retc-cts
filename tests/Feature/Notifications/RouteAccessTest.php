<?php

namespace Tests\Feature\Notifications;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_either_notification_page(): void
    {
        $this->get('/notifications/reminders')->assertRedirect('/login');
        $this->get('/notifications/logs')->assertRedirect('/login');
    }

    public function test_admin_teacher_and_department_head_can_send_reminders(): void
    {
        foreach ([UserRole::Admin, UserRole::Teacher, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/notifications/reminders')->assertOk();
        }
    }

    public function test_executive_cannot_send_reminders(): void
    {
        $executive = User::factory()->create(['role' => UserRole::Executive]);

        $this->actingAs($executive)->get('/notifications/reminders')->assertForbidden();
    }

    public function test_only_admin_can_view_notification_logs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/notifications/logs')->assertOk();

        foreach ([UserRole::Teacher, UserRole::Executive, UserRole::DepartmentHead] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get('/notifications/logs')->assertForbidden();
        }
    }
}
