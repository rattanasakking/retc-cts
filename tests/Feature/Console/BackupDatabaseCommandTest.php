<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_it_creates_a_backup_file(): void
    {
        Process::fake([
            '*' => Process::result(output: '-- dump --'),
        ]);

        $this->artisan('backup:database')->assertSuccessful();

        $this->assertCount(1, Storage::disk('local')->files('backups'));
    }

    public function test_it_reports_failure_and_exits_non_zero(): void
    {
        Process::fake([
            '*' => Process::result(output: '', errorOutput: 'access denied', exitCode: 1),
        ]);

        $this->artisan('backup:database')->assertFailed();

        $this->assertEmpty(Storage::disk('local')->files('backups'));
    }

    public function test_it_prunes_old_backups_beyond_the_keep_count(): void
    {
        Process::fake([
            '*' => Process::result(output: '-- dump --'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            Storage::disk('local')->put("backups/old_{$i}.sql", 'x');
        }

        $this->artisan('backup:database', ['--keep' => 3])->assertSuccessful();

        // 5 pre-existing + 1 new = 6, keep 3 most recent.
        $this->assertCount(3, Storage::disk('local')->files('backups'));
    }
}
