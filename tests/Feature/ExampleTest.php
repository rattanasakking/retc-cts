<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_land_on_the_self_report_form(): void
    {
        $this->get('/')->assertRedirect('/report-status');
    }

    public function test_signed_in_staff_still_land_on_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/')->assertRedirect('/dashboard');
    }
}
