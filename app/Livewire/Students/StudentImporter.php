<?php

namespace App\Livewire\Students;

use App\Imports\SchoolJobTrackingImport;
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

    /** 'standard' (this app's own CSV template) or 'school_report' (the school SIS's job-tracking report export, as-is). */
    public string $format = 'standard';

    public ?int $activeImportLogId = null;

    /** Rows to skip before data starts, per format — the school report has 4 title/metadata rows plus its own header row. */
    private const HEADER_ROWS = [
        'standard' => 1,
        'school_report' => 5,
    ];

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'format' => ['required', 'in:standard,school_report'],
        ];
    }

    public function import(): void
    {
        $this->validate();

        $originalName = $this->file->getClientOriginalName();
        $storedPath = $this->file->store('imports/students', 'local');

        $totalRows = max(0, $this->countDataRows($storedPath, self::HEADER_ROWS[$this->format]));

        $importLog = ImportLog::create([
            'user_id' => auth()->id(),
            'type' => 'students',
            'file_name' => $originalName,
            'disk_path' => $storedPath,
            'status' => 'pending',
            'total_rows' => $totalRows,
        ]);

        $import = $this->format === 'school_report'
            ? new SchoolJobTrackingImport($importLog->id)
            : new StudentsImport($importLog->id);

        Excel::queueImport($import, $storedPath, 'local');

        $this->activeImportLogId = $importLog->id;
        $this->reset('file');
    }

    /**
     * Count data rows in the CSV (excludes header/metadata rows) — used as
     * the progress bar's denominator. Reads via a stream so large files
     * don't need to be loaded into memory, and stays disk-agnostic (works
     * with Storage::fake() in tests too).
     */
    private function countDataRows(string $storedPath, int $headerRows): int
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

        return max(0, $lines - $headerRows);
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
