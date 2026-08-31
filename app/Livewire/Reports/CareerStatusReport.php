<?php

namespace App\Livewire\Reports;

use App\Enums\CareerStatusType;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('รายงานภาวะการมีงานทำ')]
class CareerStatusReport extends Component
{
    use WithPagination;

    public ?int $academicYearId = null;

    public string $program = '';

    public string $degreeLevel = '';

    /** '' = ทุกภาวะ, 'none' = ยังไม่ตอบแบบสำรวจ, ที่เหลือคือค่าใน CareerStatusType */
    public string $careerStatus = '';

    /**
     * รายงานภาวะการมีงานทำโดยปกติดูเฉพาะผู้สำเร็จการศึกษา ตัวเลขทุกตัวในหน้านี้
     * จึงคิดจากฐานเดียวกันนี้ ปิดได้ถ้าอยากเห็นนักศึกษาทุกคนในปีนั้น
     */
    public bool $graduatedOnly = true;

    public string $search = '';

    public int $perPage = 20;

    public function mount(): void
    {
        $this->academicYearId = AcademicYear::where('is_active', true)->value('id')
            ?? AcademicYear::orderByDesc('year')->value('id');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('program', 'degreeLevel', 'careerStatus', 'search');
        $this->graduatedOnly = true;
        $this->resetPage();
    }

    /**
     * นักศึกษาทั้งหมดที่รายงานนี้พูดถึง — ทุกตัวเลขในหน้านี้คิดจากฐานนี้
     */
    private function studentBase()
    {
        // ทุกคอลัมน์ต้องมีชื่อตารางนำหน้า เพราะ programSummary() เอา query นี้ไป
        // join กับ career_statuses ซึ่งมีคอลัมน์ชื่อซ้ำกันหลายตัว
        return Student::query()
            ->where('students.academic_year_id', $this->academicYearId)
            ->when($this->graduatedOnly, fn ($query) => $query->where('students.status', 'graduated'))
            ->when($this->program, fn ($query) => $query->where('students.program', $this->program))
            ->when($this->degreeLevel, fn ($query) => $query->where('students.degree_level', $this->degreeLevel));
    }

    /**
     * เงื่อนไขของ "ภาวะการมีงานทำที่ถือเป็นคำตอบล่าสุดของปีนี้"
     */
    private function currentStatusConstraint(): callable
    {
        return fn ($query) => $query
            ->where('academic_year_id', $this->academicYearId)
            ->where('is_current', true);
    }

    /**
     * สรุปแยกตามแผนกวิชา — left join เพื่อให้คนที่ยังไม่ตอบแบบสำรวจถูกนับด้วย
     * (whereHas จะตัดคนกลุ่มนี้ทิ้ง ซึ่งเป็นตัวเลขที่รายงานต้องการมากที่สุด)
     */
    private function programSummary()
    {
        return $this->studentBase()
            ->leftJoin('career_statuses', function ($join) {
                $join->on('career_statuses.student_id', '=', 'students.id')
                    ->where('career_statuses.academic_year_id', '=', $this->academicYearId)
                    ->where('career_statuses.is_current', '=', true);
            })
            ->selectRaw("students.program as program,
                COUNT(*) as total,
                SUM(CASE WHEN career_statuses.id IS NULL THEN 1 ELSE 0 END) as no_response,
                SUM(CASE WHEN career_statuses.status IN ('employed','entrepreneur') THEN 1 ELSE 0 END) as employed,
                SUM(CASE WHEN career_statuses.status = 'further_study' THEN 1 ELSE 0 END) as further_study,
                SUM(CASE WHEN career_statuses.status = 'unemployed' THEN 1 ELSE 0 END) as unemployed,
                SUM(CASE WHEN career_statuses.status IN ('military_service','other') THEN 1 ELSE 0 END) as other")
            ->groupBy('students.program')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => (object) [
                'program' => $row->program ?: 'ไม่ระบุแผนกวิชา',
                'total' => (int) $row->total,
                'no_response' => (int) $row->no_response,
                'employed' => (int) $row->employed,
                'further_study' => (int) $row->further_study,
                'unemployed' => (int) $row->unemployed,
                'other' => (int) $row->other,
            ]);
    }

    public function render()
    {
        $constraint = $this->currentStatusConstraint();

        $students = $this->studentBase()
            ->with(['careerStatuses' => fn ($query) => $constraint($query)->with(['workProvince'])])
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('student_code', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term);
                });
            })
            ->when($this->careerStatus === 'none', fn ($query) => $query->whereDoesntHave('careerStatuses', $constraint))
            ->when(
                $this->careerStatus !== '' && $this->careerStatus !== 'none',
                fn ($query) => $query->whereHas('careerStatuses', fn ($q) => $constraint($q)->where('status', $this->careerStatus))
            )
            ->orderBy('program')
            ->orderBy('first_name')
            ->paginate($this->perPage);

        $summary = $this->programSummary();

        $totals = (object) [
            'total' => $summary->sum('total'),
            'no_response' => $summary->sum('no_response'),
            'employed' => $summary->sum('employed'),
            'further_study' => $summary->sum('further_study'),
            'unemployed' => $summary->sum('unemployed'),
            'other' => $summary->sum('other'),
        ];

        $respondents = $totals->total - $totals->no_response;

        $avgSalary = CareerStatus::query()
            ->whereIn('student_id', $this->studentBase()->select('students.id'))
            ->where('academic_year_id', $this->academicYearId)
            ->where('is_current', true)
            ->whereIn('status', ['employed', 'entrepreneur'])
            ->whereNotNull('monthly_salary')
            ->avg('monthly_salary');

        return view('livewire.reports.career-status-report', [
            'students' => $students,
            'summary' => $summary,
            'totals' => $totals,
            'respondents' => $respondents,
            'responseRate' => $totals->total > 0 ? (int) round($respondents / $totals->total * 100) : 0,
            'employedRate' => $respondents > 0 ? (int) round($totals->employed / $respondents * 100) : 0,
            'avgSalary' => $avgSalary,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'selectedYear' => AcademicYear::find($this->academicYearId),
            'programs' => Student::query()->whereNotNull('program')->distinct()->orderBy('program')->pluck('program'),
            'degreeLevels' => Student::query()->whereNotNull('degree_level')->distinct()->orderBy('degree_level')->pluck('degree_level'),
            'careerStatusTypes' => CareerStatusType::cases(),
        ]);
    }
}
