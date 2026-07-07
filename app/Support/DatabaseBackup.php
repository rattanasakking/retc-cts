<?php

namespace App\Support;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Shells out to mysqldump to produce a SQL dump, and stores it on the
 * backup disk/directory (config/backup.php). Shared by the Settings >
 * Backup Livewire page (interactive, triggered by an admin) and the
 * `backup:database` Artisan command (scheduled) so both go through
 * identical, tested logic instead of two copies drifting apart.
 */
class DatabaseBackup
{
    public static function dump(): ProcessResult
    {
        $db = config('database.connections.mysql');

        $command = [
            config('backup.mysqldump_path'),
            '--host='.$db['host'],
            '--port='.$db['port'],
            '--user='.$db['username'],
            '--single-transaction',
            '--skip-comments',
        ];

        if (! empty($db['password'])) {
            $command[] = '--password='.$db['password'];
        }

        $command[] = $db['database'];

        return Process::timeout(120)->run($command);
    }

    /**
     * Run a dump and store it under a given filename prefix. Returns the
     * stored filename on success, or null (with $error set) on failure.
     */
    public static function createSnapshot(string $prefix = 'backup', ?string &$error = null): ?string
    {
        $result = static::dump();

        if (! $result->successful()) {
            $error = $result->errorOutput();

            return null;
        }

        $filename = $prefix.'_'.now()->format('Y_m_d_His').'.sql';
        Storage::disk(config('backup.disk'))->put(config('backup.directory').'/'.$filename, $result->output());

        return $filename;
    }

    /**
     * Delete all but the $keep most-recently-modified backups (safety
     * snapshots taken before a restore are included in that count) — keeps
     * the backups directory from growing unbounded once this runs daily
     * via the scheduler.
     */
    public static function pruneOldBackups(int $keep = 30): int
    {
        $disk = Storage::disk(config('backup.disk'));
        $directory = config('backup.directory');

        $files = collect($disk->files($directory))
            ->sortByDesc(fn ($path) => $disk->lastModified($path))
            ->values();

        $toDelete = $files->slice($keep);

        foreach ($toDelete as $path) {
            $disk->delete($path);
        }

        return $toDelete->count();
    }
}
