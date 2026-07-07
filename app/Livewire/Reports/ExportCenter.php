<?php

namespace App\Livewire\Reports;

use App\Exports\StudentsReportExport;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Support\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.app')]
#[Title('ส่งออกรายงาน')]
class ExportCenter extends Component
{
    public ?int $academicYearId = null;

    public string $program = '';

    public string $degreeLevel = '';

    public function mount(): void
    {
        $this->academicYearId = AcademicYear::where('is_active', true)->value('id')
            ?? AcademicYear::orderByDesc('year')->value('id');
    }

    protected function rules(): array
    {
        return [
            'academicYearId' => ['required', 'exists:academic_years,id'],
            'program' => ['nullable', 'string'],
            'degreeLevel' => ['nullable', 'string'],
        ];
    }

    private function filenameSuffix(): string
    {
        $year = AcademicYear::find($this->academicYearId)?->year ?? 'all';
        $parts = array_filter([$year, $this->program, $this->degreeLevel]);

        return str_replace(' ', '-', implode('_', $parts));
    }

    private function logExport(string $format): void
    {
        $year = AcademicYear::find($this->academicYearId)?->year;

        $filterSummary = collect([
            $year ? "ปีการศึกษา {$year}" : null,
            $this->program ?: null,
            $this->degreeLevel ?: null,
        ])->filter()->implode(' · ');

        AuditLogger::log(
            action: $format === 'Excel' ? 'export_excel' : 'export_pdf',
            module: 'รายงาน',
            description: "ส่งออกรายงานภาวะการมีงานทำ ({$format}) — {$filterSummary}",
        );
    }

    public function exportExcel()
    {
        $this->validate();
        $this->logExport('Excel');

        return Excel::download(
            new StudentsReportExport($this->academicYearId, $this->program ?: null, $this->degreeLevel ?: null),
            'รายงานภาวะการมีงานทำ_'.$this->filenameSuffix().'.xlsx'
        );
    }

    public function exportPdf()
    {
        $this->validate();
        $this->logExport('PDF');

        $year = AcademicYear::find($this->academicYearId);

        $students = Student::query()
            ->with(['academicYear', 'careerStatuses' => function ($query) {
                $query->where('academic_year_id', $this->academicYearId)->where('is_current', true);
            }])
            ->where('academic_year_id', $this->academicYearId)
            ->when($this->program, fn ($query) => $query->where('program', $this->program))
            ->when($this->degreeLevel, fn ($query) => $query->where('degree_level', $this->degreeLevel))
            ->orderBy('first_name')
            ->get();

        $filterSummary = collect([
            'ปีการศึกษา '.$year?->year,
            $this->program ? 'แผนกวิชา '.$this->program : null,
            $this->degreeLevel ? 'ระดับ '.$this->degreeLevel : null,
        ])->filter()->implode(' · ');

        $pdf = Pdf::loadView('exports.students-report-pdf', [
            'students' => $students,
            'filterSummary' => $filterSummary,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        // dompdf's own ->download() returns a plain Illuminate\Http\Response,
        // which Livewire's file-download support doesn't recognize (only
        // StreamedResponse/BinaryFileResponse trigger it) — stream it instead.
        return response()->streamDownload(
            fn () => print $pdf->output(),
            'รายงานภาวะการมีงานทำ_'.$this->filenameSuffix().'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function render()
    {
        return view('livewire.reports.export-center', [
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'programs' => Student::query()->whereNotNull('program')->distinct()->orderBy('program')->pluck('program'),
            'degreeLevels' => Student::query()->whereNotNull('degree_level')->distinct()->orderBy('degree_level')->pluck('degree_level'),
            'previewCount' => Student::query()
                ->where('academic_year_id', $this->academicYearId)
                ->when($this->program, fn ($query) => $query->where('program', $this->program))
                ->when($this->degreeLevel, fn ($query) => $query->where('degree_level', $this->degreeLevel))
                ->count(),
        ]);
    }
}
