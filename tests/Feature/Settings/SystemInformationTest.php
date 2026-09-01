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

    public function test_admin_can_set_the_branding_a_college_needs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SystemInformation::class)
            ->set('system_name', 'ระบบติดตามภาวะการมีงานทำ')
            ->set('short_name', 'RTC-CTS')
            ->set('college_name', 'วิทยาลัยเทคนิคทดสอบ')
            ->set('primary_color', '#0f766e')
            ->set('contact_email', 'guidance@college.ac.th')
            ->set('contact_phone', '043-511-111')
            ->call('save')
            ->assertHasNoErrors();

        $setting = SystemSetting::current();
        $this->assertSame('RTC-CTS', $setting->short_name);
        $this->assertSame('#0f766e', $setting->primary_color);
        $this->assertSame('guidance@college.ac.th', $setting->contact_email);
        $this->assertSame('043-511-111', $setting->contact_phone);
    }

    public function test_the_brand_colour_must_be_a_hex_code(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(SystemInformation::class)
            ->set('primary_color', 'เขียว')
            ->call('save')
            ->assertHasErrors(['primary_color']);
    }

    public function test_the_configured_names_replace_the_defaults_across_the_site(): void
    {
        SystemSetting::current()->update([
            'system_name' => 'ระบบติดตามภาวะการมีงานทำ',
            'short_name' => 'RTC-CTS',
            'college_name' => 'วิทยาลัยเทคนิคทดสอบ',
            'primary_color' => '#0f766e',
            'contact_phone' => '043-511-111',
        ]);
        SystemSetting::forgetCached();

        // หน้าสาธารณะ (ไม่ต้องล็อกอิน)
        $this->get('/search')
            ->assertOk()
            ->assertSee('ระบบติดตามภาวะการมีงานทำ')
            ->assertSee('RTC-CTS')
            ->assertSee('วิทยาลัยเทคนิคทดสอบ')
            ->assertSee('043-511-111')
            ->assertSee('#0f766e')
            ->assertDontSee('RETC Smart Career Tracking System');

        // หลังบ้าน
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('RTC-CTS')
            ->assertSee('วิทยาลัยเทคนิคทดสอบ');
    }

    public function test_the_pwa_manifest_is_built_from_the_settings(): void
    {
        SystemSetting::current()->update([
            'system_name' => 'ระบบติดตามภาวะการมีงานทำ',
            'short_name' => 'RTC-CTS',
            'primary_color' => '#0f766e',
        ]);
        SystemSetting::forgetCached();

        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertJsonPath('name', 'ระบบติดตามภาวะการมีงานทำ')
            ->assertJsonPath('short_name', 'RTC-CTS')
            ->assertJsonPath('theme_color', '#0f766e');
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
