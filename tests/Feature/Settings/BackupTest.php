<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Livewire\Settings\Backup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_non_admin_cannot_access_backup_settings(): void
    {
        $teacher = User::factory()->create(['role' => UserRole::Teacher]);

        $this->actingAs($teacher)->get('/settings/backup')->assertForbidden();
    }

    public function test_admin_can_create_a_backup(): void
    {
        Process::fake([
            '*' => Process::result(output: '-- fake sql dump content --'),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(Backup::class)
            ->call('createBackup');

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('-- fake sql dump content --', Storage::disk('local')->get($files[0]));
    }

    public function test_a_failed_mysqldump_shows_an_error_and_creates_no_file(): void
    {
        Process::fake([
            '*' => Process::result(output: '', errorOutput: 'Access denied for user', exitCode: 1),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $component = Livewire::actingAs($admin)
            ->test(Backup::class)
            ->call('createBackup');

        $this->assertEmpty(Storage::disk('local')->files('backups'));
        $this->assertNotNull($component->get('actionError'));
    }

    public function test_admin_can_delete_a_backup_file(): void
    {
        Storage::disk('local')->put('backups/old_backup.sql', 'dummy');

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(Backup::class)
            ->call('deleteBackup', 'old_backup.sql');

        Storage::disk('local')->assertMissing('backups/old_backup.sql');
    }

    public function test_path_traversal_filenames_are_rejected_for_download_delete_and_restore(): void
    {
        Storage::disk('local')->put('backups/real_backup.sql', 'dummy');
        Storage::disk('local')->put('secrets.txt', 'top secret, outside the backups directory');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $traversal = '../secrets.txt';

        $download = Livewire::actingAs($admin)->test(Backup::class)->call('download', $traversal);
        $this->assertNotNull($download->get('actionError'));

        Livewire::actingAs($admin)->test(Backup::class)->call('deleteBackup', $traversal);
        Storage::disk('local')->assertExists('secrets.txt'); // untouched

        $confirm = Livewire::actingAs($admin)->test(Backup::class)->call('confirmRestore', $traversal);
        $this->assertNotNull($confirm->get('actionError'));
        $this->assertNull($confirm->get('restoreFilename'));
    }

    public function test_restore_is_blocked_until_the_confirmation_word_is_typed_exactly(): void
    {
        Storage::disk('local')->put('backups/existing.sql', '-- existing backup --');

        Process::fake([
            '*' => Process::result(output: '-- safety snapshot --'),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(Backup::class)
            ->call('confirmRestore', 'existing.sql')
            ->set('confirmationText', 'restore') // wrong case
            ->call('performRestore');

        Process::assertNothingRan();
    }

    public function test_restoring_takes_a_safety_backup_before_running_the_restore_command(): void
    {
        Storage::disk('local')->put('backups/existing.sql', '-- existing backup --');

        Process::fake([
            '*' => Process::result(output: '-- safety snapshot --'),
        ]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(Backup::class)
            ->call('confirmRestore', 'existing.sql')
            ->set('confirmationText', 'RESTORE')
            ->call('performRestore')
            ->assertHasNoErrors();

        // One process call for the pre-restore mysqldump safety snapshot,
        // one for the mysql restore itself.
        Process::assertRanTimes(fn () => true, 2);

        $safetyFiles = collect(Storage::disk('local')->files('backups'))
            ->filter(fn ($path) => str_contains($path, 'pre_restore_'));

        $this->assertCount(1, $safetyFiles);
    }
}
