<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\ThaiProvince;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_geography_and_creates_the_first_admin(): void
    {
        $this->artisan('app:install', [
            '--admin-name' => 'ผู้ดูแลระบบ',
            '--admin-email' => 'admin@college.ac.th',
            '--admin-password' => 'secret-password',
            '--college' => 'วิทยาลัยเทคนิคทดสอบ',
            '--no-interaction-safe' => true,
        ])->assertSuccessful();

        $admin = User::where('email', 'admin@college.ac.th')->first();
        $this->assertNotNull($admin);
        $this->assertSame(UserRole::Admin, $admin->role);

        $this->assertGreaterThan(0, ThaiProvince::count());
        $this->assertSame('วิทยาลัยเทคนิคทดสอบ', SystemSetting::current()->college_name);
    }

    public function test_running_it_again_does_not_duplicate_anything(): void
    {
        $options = [
            '--admin-email' => 'admin@college.ac.th',
            '--admin-password' => 'secret-password',
            '--no-interaction-safe' => true,
        ];

        $this->artisan('app:install', $options)->assertSuccessful();
        $provinces = ThaiProvince::count();

        $this->artisan('app:install', $options)->assertSuccessful();

        $this->assertSame(1, User::where('email', 'admin@college.ac.th')->count());
        $this->assertSame($provinces, ThaiProvince::count());
    }

    public function test_an_existing_admin_is_left_alone(): void
    {
        User::factory()->create(['role' => UserRole::Admin, 'email' => 'first@college.ac.th']);

        $this->artisan('app:install', [
            '--admin-email' => 'second@college.ac.th',
            '--admin-password' => 'secret-password',
            '--no-interaction-safe' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'second@college.ac.th']);
        $this->assertSame(1, User::where('role', UserRole::Admin->value)->count());
    }

    public function test_a_bad_admin_email_is_reported_without_failing_the_install(): void
    {
        $this->artisan('app:install', [
            '--admin-email' => 'not-an-email',
            '--admin-password' => 'secret-password',
            '--no-interaction-safe' => true,
        ])->assertSuccessful();

        $this->assertSame(0, User::count());
        // The rest of the install still ran.
        $this->assertGreaterThan(0, ThaiProvince::count());
    }

    public function test_it_leaves_no_demo_data_behind(): void
    {
        $this->artisan('app:install', ['--no-interaction-safe' => true])->assertSuccessful();

        // DatabaseSeeder's fake students/companies must never be part of a real install.
        $this->assertSame(0, Student::count());
        $this->assertSame(0, Company::count());
    }
}
