<?php

namespace App\Console\Commands;

use App\Support\DatabaseBackup;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=30 : Number of most recent backups to retain}';

    protected $description = 'Dump the MySQL database via mysqldump and store it on the backup disk, pruning old backups';

    public function handle(): int
    {
        $this->info('Running mysqldump...');

        $filename = DatabaseBackup::createSnapshot('backup', $error);

        if ($filename === null) {
            $this->error("Backup failed: {$error}");

            return self::FAILURE;
        }

        $this->info("Backup stored: {$filename}");

        $deleted = DatabaseBackup::pruneOldBackups((int) $this->option('keep'));

        if ($deleted > 0) {
            $this->info("Pruned {$deleted} old backup(s), keeping the {$this->option('keep')} most recent.");
        }

        return self::SUCCESS;
    }
}
