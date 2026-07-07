<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Livewire\Settings\AcademicYears;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_academic_year_settings(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)->get('/settings/academic-years')->assertForbidden();
    }

    public function test_admin_can_create_an_academic_year(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(AcademicYears::class)
            ->call('openCreateModal')
            ->set('year', '2570')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('academic_years', ['year' => 2570, 'is_active' => 1]);
    }

    public function test_setting_a_year_active_deactivates_the_previous_active_year(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $oldActive = AcademicYear::factory()->create(['year' => 2568, 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test(AcademicYears::class)
            ->call('openCreateModal')
            ->set('year', '2569')
            ->set('is_active', true)
            ->call('save');

        $this->assertFalse($oldActive->fresh()->is_active);
        $this->assertTrue(AcademicYear::where('year', 2569)->value('is_active'));
    }

    public function test_year_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        AcademicYear::factory()->create(['year' => 2569]);

        Livewire::actingAs($admin)
            ->test(AcademicYears::class)
            ->call('openCreateModal')
            ->set('year', '2569')
            ->call('save')
            ->assertHasErrors(['year' => 'unique']);
    }

    public function test_admin_can_update_an_academic_year(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create(['year' => 2569]);

        Livewire::actingAs($admin)
            ->test(AcademicYears::class)
            ->call('openEditModal', $year->id)
            ->set('start_date', '2026-06-01')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('2026-06-01', $year->fresh()->start_date->toDateString());
    }

    public function test_deleting_an_unreferenced_academic_year_succeeds(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();

        Livewire::actingAs($admin)
            ->test(AcademicYears::class)
            ->call('confirmDelete', $year->id)
            ->call('delete');

        $this->assertDatabaseMissing('academic_years', ['id' => $year->id]);
    }

    public function test_deleting_a_referenced_academic_year_is_blocked_with_a_friendly_error(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $year = AcademicYear::factory()->create();
        Student::factory()->create(['academic_year_id' => $year->id]);

        $component = Livewire::actingAs($admin)
            ->test(AcademicYears::class)
            ->call('confirmDelete', $year->id)
            ->call('delete');

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
        $this->assertNotNull($component->get('deleteError'));
    }
}
