<?php

namespace App\Livewire\Students;

use App\Imports\StudentsImport;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.app')]
#[Title('นำเข้าข้อมูลนักศึกษา')]
class StudentImporter extends Component
{
    use WithFileUploads;

    public $file = null;

    public ?int $activeImportLogId = null;

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }

    public function import(): void
    {
        $this->validate();

        $originalName = $this->file->getClientOriginalName();
        $storedPath = $this->file->store('imports/students', 'local');

        $totalRows = max(0, $this->countDataRows($storedPath));

        $importLog = ImportLog::create([
            'user_id' => auth()->id(),
            'type' => 'students',
            'file_name' => $originalName,
            'disk_path' => $storedPath,
            'status' => 'pending',
            'total_rows' => $totalRows,
        ]);

        Excel::queueImport(new StudentsImport($importLog->id), $storedPath, 'local');

        $this->activeImportLogId = $importLog->id;
        $this->reset('file');
    }

    /**
     * Count data rows in the CSV (excludes the header row) — used as the
     * progress bar's denominator. Reads via a stream so large files don't
     * need to be loaded into memory, and stays disk-agnostic (works with
     * Storage::fake() in tests too).
     */
    private function countDataRows(string $storedPath): int
    {
        $stream = Storage::disk('local')->readStream($storedPath);

        if ($stream === null) {
            return 0;
        }

        $lines = 0;

        while (($line = fgets($stream)) !== false) {
            if (trim($line) !== '') {
                $lines++;
            }
        }

        fclose($stream);

        return max(0, $lines - 1);
    }

    public function getActiveImportProperty(): ?ImportLog
    {
        return $this->activeImportLogId ? ImportLog::find($this->activeImportLogId) : null;
    }

    public function getRecentImportsProperty()
    {
        return ImportLog::where('type', 'students')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.students.student-importer');
    }
}
