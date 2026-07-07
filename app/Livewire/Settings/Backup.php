<?php

namespace App\Livewire\Settings;

use App\Support\DatabaseBackup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('ตั้งค่า: สำรอง/กู้คืนข้อมูล')]
class Backup extends Component
{
    use WithFileUploads;

    public bool $isRunning = false;

    public ?string $actionError = null;

    // Restore flow
    public ?string $restoreFilename = null;

    public $restoreUpload = null;

    public string $confirmationText = '';

    public function createBackup(): void
    {
        $this->isRunning = true;
        $this->actionError = null;

        $filename = DatabaseBackup::createSnapshot('backup', $error);

        if ($filename === null) {
            $this->actionError = 'สำรองข้อมูลล้มเหลว: '.$error;
            $this->isRunning = false;

            return;
        }

        Log::info('Database backup created', ['user_id' => auth()->id(), 'file' => $filename]);

        session()->flash('success', 'สร้างข้อมูลสำรองเรียบร้อยแล้ว: '.$filename);
        $this->isRunning = false;
    }

    /**
     * Livewire component methods are callable directly with an arbitrary
     * string argument (not just via the rendered UI), so $filename can't be
     * trusted as-is. Re-resolving it against the real backup directory
     * listing — rather than just sanitizing the string — is what actually
     * closes the path-traversal risk: only names that exist as real backup
     * files, right now, in that directory ever reach Storage calls.
     */
    private function resolveBackupFilename(string $filename): ?string
    {
        $safeName = basename($filename);

        $exists = collect(Storage::disk(config('backup.disk'))->files(config('backup.directory')))
            ->contains(fn ($path) => basename($path) === $safeName);

        return $exists ? $safeName : null;
    }

    public function download(string $filename): mixed
    {
        $safeName = $this->resolveBackupFilename($filename);

        if (! $safeName) {
            $this->actionError = 'ไม่พบไฟล์สำรองข้อมูลนี้';

            return null;
        }

        return Storage::disk(config('backup.disk'))->download(config('backup.directory').'/'.$safeName);
    }

    public function deleteBackup(string $filename): void
    {
        $safeName = $this->resolveBackupFilename($filename);

        if (! $safeName) {
            $this->actionError = 'ไม่พบไฟล์สำรองข้อมูลนี้';

            return;
        }

        Storage::disk(config('backup.disk'))->delete(config('backup.directory').'/'.$safeName);
        session()->flash('success', 'ลบไฟล์สำรองข้อมูลเรียบร้อยแล้ว');
    }

    public function confirmRestore(string $filename): void
    {
        $safeName = $this->resolveBackupFilename($filename);

        if (! $safeName) {
            $this->actionError = 'ไม่พบไฟล์สำรองข้อมูลนี้';

            return;
        }

        $this->restoreFilename = $safeName;
        $this->restoreUpload = null;
        $this->confirmationText = '';
        $this->actionError = null;
    }

    public function confirmRestoreFromUpload(): void
    {
        $this->validate(['restoreUpload' => ['required', 'file', 'extensions:sql', 'max:51200']]);

        $this->restoreFilename = null;
        $this->confirmationText = '';
        $this->actionError = null;
    }

    public function cancelRestore(): void
    {
        $this->restoreFilename = null;
        $this->restoreUpload = null;
        $this->confirmationText = '';
    }

    public function performRestore(): void
    {
        if ($this->confirmationText !== 'RESTORE') {
            $this->actionError = 'กรุณาพิมพ์ RESTORE เพื่อยืนยัน';

            return;
        }

        $this->isRunning = true;
        $this->actionError = null;

        // restoreFilename is a public Livewire property, so it could in
        // theory be set directly via a crafted component-update request
        // rather than through confirmRestore() — re-resolve it here too
        // instead of trusting whatever confirmRestore() left it as.
        $safeName = $this->restoreFilename ? $this->resolveBackupFilename($this->restoreFilename) : null;

        $sql = $this->restoreUpload
            ? file_get_contents($this->restoreUpload->getRealPath())
            : ($safeName ? Storage::disk(config('backup.disk'))->get(config('backup.directory').'/'.$safeName) : null);

        if (! $this->restoreUpload && ! $safeName) {
            $this->actionError = 'ไม่พบไฟล์สำรองข้อมูลนี้';
            $this->isRunning = false;

            return;
        }

        if ($sql === null || $sql === false) {
            $this->actionError = 'ไม่สามารถอ่านไฟล์สำรองข้อมูลได้';
            $this->isRunning = false;

            return;
        }

        // Safety net: always snapshot the current database before overwriting it.
        DatabaseBackup::createSnapshot('pre_restore');

        $db = config('database.connections.mysql');

        $command = [
            config('backup.mysql_cli_path'),
            '--host='.$db['host'],
            '--port='.$db['port'],
            '--user='.$db['username'],
        ];

        if (! empty($db['password'])) {
            $command[] = '--password='.$db['password'];
        }

        $command[] = $db['database'];

        $result = Process::timeout(120)->input($sql)->run($command);

        $this->isRunning = false;

        if (! $result->successful()) {
            $this->actionError = 'กู้คืนข้อมูลล้มเหลว: '.$result->errorOutput();

            return;
        }

        Log::warning('Database restored from backup', [
            'user_id' => auth()->id(),
            'source' => $this->restoreUpload ? 'upload' : $this->restoreFilename,
        ]);

        $this->cancelRestore();
        session()->flash('success', 'กู้คืนข้อมูลเรียบร้อยแล้ว (มีการสำรองข้อมูลชุดก่อนกู้คืนไว้ให้อัตโนมัติ)');
    }

    public function render()
    {
        $files = collect(Storage::disk(config('backup.disk'))->files(config('backup.directory')))
            ->map(fn ($path) => [
                'name' => basename($path),
                'size' => Storage::disk(config('backup.disk'))->size($path),
                'modified' => Storage::disk(config('backup.disk'))->lastModified($path),
            ])
            ->sortByDesc('modified')
            ->values();

        return view('livewire.settings.backup', [
            'files' => $files,
        ]);
    }
}
