<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Livewire\Settings\SystemInformation;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SystemInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_system_information_settings(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)->get('/settings/system')->assertForbidden();
    }

    public function test_admin_can_update_system_name_and_college_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SystemInformation::class)
            ->set('system_name', 'RETC Career Hub')
            ->set('college_name', 'วิทยาลัยเทคนิคทดสอบ')
            ->call('save')
            ->assertHasNoErrors();

        $setting = SystemSetting::current();
        $this->assertSame('RETC Career Hub', $setting->system_name);
        $this->assertSame('วิทยาลัยเทคนิคทดสอบ', $setting->college_name);
    }

    public function test_system_name_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SystemInformation::class)
            ->set('system_name', '')
            ->call('save')
            ->assertHasErrors(['system_name' => 'required']);
    }

    public function test_admin_can_upload_a_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $logo = UploadedFile::fake()->image('logo.png', 200, 200);

        Livewire::actingAs($admin)
            ->test(SystemInformation::class)
            ->set('logo', $logo)
            ->call('save')
            ->assertHasNoErrors();

        $setting = SystemSetting::current();
        $this->assertNotNull($setting->logo_path);
        Storage::disk('public')->assertExists($setting->logo_path);
    }

    public function test_admin_can_remove_the_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $path = UploadedFile::fake()->image('logo.png')->store('logos', 'public');
        SystemSetting::current()->update(['logo_path' => $path]);

        Livewire::actingAs($admin)
            ->test(SystemInformation::class)
            ->call('removeLogo');

        $this->assertNull(SystemSetting::current()->logo_path);
        Storage::disk('public')->assertMissing($path);
    }
}
