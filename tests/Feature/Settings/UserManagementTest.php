<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Livewire\Settings\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_user_management(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)->get('/settings/users')->assertForbidden();
    }

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('openCreateModal')
            ->set('name', 'ครูสมชาย')
            ->set('email', 'somchai.teacher@retc-cts.test')
            ->set('role', 'teacher')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'somchai.teacher@retc-cts.test', 'role' => 'teacher']);
    }

    public function test_email_must_be_unique_when_creating(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['email' => 'taken@retc-cts.test']);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('openCreateModal')
            ->set('name', 'Test')
            ->set('email', 'taken@retc-cts.test')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_password_must_be_confirmed_when_creating(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('openCreateModal')
            ->set('name', 'Test')
            ->set('email', 'new@retc-cts.test')
            ->set('password', 'password123')
            ->set('password_confirmation', 'different')
            ->call('save')
            ->assertHasErrors(['password' => 'confirmed']);
    }

    public function test_admin_can_edit_a_user_without_touching_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $target = User::factory()->create(['name' => 'เดิม', 'role' => UserRole::Teacher]);
        $originalPassword = $target->password;

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('openEditModal', $target->id)
            ->set('name', 'แก้ไขแล้ว')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();
        $this->assertSame('แก้ไขแล้ว', $target->name);
        $this->assertSame($originalPassword, $target->password);
    }

    public function test_admin_can_change_another_users_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('openPasswordModal', $target->id)
            ->set('newPassword', 'newpassword123')
            ->set('newPassword_confirmation', 'newpassword123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('newpassword123', $target->fresh()->password));
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('confirmDelete', $target->id)
            ->call('delete');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('confirmDelete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertNull($component->get('confirmingDeleteId'));
        $this->assertNotNull($component->get('actionError'));
    }

    public function test_the_last_remaining_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherAdminViewer = User::factory()->create(['role' => UserRole::Executive]);

        $component = Livewire::actingAs($otherAdminViewer)
            ->test(Users::class)
            ->call('confirmDelete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertNotNull($component->get('actionError'));
    }

    public function test_the_last_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('openEditModal', $admin->id)
            ->set('role', 'teacher')
            ->call('save');

        $this->assertSame('admin', $admin->fresh()->role->value);
        $this->assertNotNull($component->get('actionError'));
    }

    public function test_an_admin_can_demote_themselves_if_another_admin_exists(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('openEditModal', $admin->id)
            ->set('role', 'teacher')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('teacher', $admin->fresh()->role->value);
    }
}
