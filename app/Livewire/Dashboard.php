<?php

namespace App\Livewire;

use App\Enums\CareerStatusType;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\ThaiProvince;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public ?int $selectedYearId = null;

    public string $selectedProgram = '';

    public string $selectedDegreeLevel = '';

    public function mount(): void
    {
        $this->selectedYearId = AcademicYear::where('is_active', true)->value('id')
            ?? AcademicYear::orderByDesc('year')->value('id');
    }

    public function resetFilters(): void
    {
        $this->selectedProgram = '';
        $this->selectedDegreeLevel = '';
        $this->selectedYearId = AcademicYear::where('is_active', true)->value('id')
            ?? AcademicYear::orderByDesc('year')->value('id');
    }

    /**
     * Apply the program/degree-level filters to a Student query builder.
     */
    private function applyStudentFilters($query)
    {
        return $query
            ->when($this->selectedProgram, fn ($q) => $q->where('program', $this->selectedProgram))
            ->when($this->selectedDegreeLevel, fn ($q) => $q->where('degree_level', $this->selectedDegreeLevel));
    }

    public function render()
    {
        $years = AcademicYear::orderByDesc('year')->get();
        $year = $years->firstWhere('id', $this->selectedYearId);

        $programs = Student::query()->whereNotNull('program')->distinct()->orderBy('program')->pluck('program');
        $degreeLevels = Student::query()->whereNotNull('degree_level')->distinct()->orderBy('degree_level')->pluck('degree_level');

        $graduates = 0;
        $respondents = 0;
        $breakdown = collect();
        $avgSalary = null;
        $topProvince = null;
        $topCompany = null;
        $relatedYes = 0;
        $relatedNo = 0;
        $departmentRows = collect();
        $provinceRows = collect();

        if ($year) {
            $graduates = $this->applyStudentFilters(
                Student::where('academic_year_id', $year->id)->where('status', 'graduated')
            )->count();

            $careerBase = fn () => CareerStatus::where('academic_year_id', $year->id)
                ->where('is_current', true)
                ->whereHas('student', fn ($q) => $this->applyStudentFilters($q));

            $respondents = $careerBase()->distinct()->count('student_id');

            $breakdown = $careerBase()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $working = $careerBase()->whereIn('status', ['employed', 'entrepreneur']);
            $avgSalary = (clone $working)->whereNotNull('monthly_salary')->avg('monthly_salary');

            $provinceRows = $careerBase()
                ->join('thai_provinces', 'thai_provinces.id', '=', 'career_statuses.work_province_id')
                ->whereIn('career_statuses.status', ['employed', 'entrepreneur', 'further_study'])
                ->selectRaw("thai_provinces.id as province_id, thai_provinces.name_th as name, thai_provinces.lat as lat, thai_provinces.lng as lng,
                    SUM(CASE WHEN career_statuses.status IN ('employed','entrepreneur') THEN 1 ELSE 0 END) as employed,
                    SUM(CASE WHEN career_statuses.status = 'further_study' THEN 1 ELSE 0 END) as further_study,
                    COUNT(*) as total")
                ->groupBy('thai_provinces.id', 'thai_provinces.name_th', 'thai_provinces.lat', 'thai_provinces.lng')
                ->orderByDesc('total')
                ->get();

            $topProvince = $provinceRows->first()->name ?? null;

            $topCompany = (clone $working)
                ->whereNotNull('company_name')
                ->selectRaw('company_name, count(*) as total')
                ->groupBy('company_name')
                ->orderByDesc('total')
                ->value('company_name');

            $relatedYes = (clone $working)->where('is_related_to_major', true)->count();
            $relatedNo = (clone $working)->where('is_related_to_major', false)->count();

            $departmentRows = Student::query()
                ->join('career_statuses', 'career_statuses.student_id', '=', 'students.id')
                ->where('students.academic_year_id', $year->id)
                ->where('career_statuses.academic_year_id', $year->id)
                ->where('career_statuses.is_current', true)
                ->when($this->selectedProgram, fn ($q) => $q->where('students.program', $this->selectedProgram))
                ->when($this->selectedDegreeLevel, fn ($q) => $q->where('students.degree_level', $this->selectedDegreeLevel))
                ->selectRaw("students.program as program,
                    SUM(CASE WHEN career_statuses.status IN ('employed','entrepreneur') THEN 1 ELSE 0 END) as employed,
                    SUM(CASE WHEN career_statuses.status = 'unemployed' THEN 1 ELSE 0 END) as unemployed,
                    SUM(CASE WHEN career_statuses.status = 'further_study' THEN 1 ELSE 0 END) as further_study")
                ->groupBy('students.program')
                ->orderByDesc('employed')
                ->limit(8)
                ->get();
        }

        $employed = (int) ($breakdown[CareerStatusType::Employed->value] ?? 0)
            + (int) ($breakdown[CareerStatusType::Entrepreneur->value] ?? 0);
        $furtherStudy = (int) ($breakdown[CareerStatusType::FurtherStudy->value] ?? 0);
        $unemployed = (int) ($breakdown[CareerStatusType::Unemployed->value] ?? 0);
        $other = (int) ($breakdown[CareerStatusType::MilitaryService->value] ?? 0)
            + (int) ($breakdown[CareerStatusType::Other->value] ?? 0);

        $responseRate = $graduates > 0 ? round($respondents / $graduates * 100) : 0;
        $employedRate = $respondents > 0 ? round($employed / $respondents * 100) : 0;
        $relatedTotal = $relatedYes + $relatedNo;
        $relatedToMajorRate = $relatedTotal > 0 ? round($relatedYes / $relatedTotal * 100) : 0;

        // Trend across every academic year (respects the program/degree filters, ignores the year filter).
        $trendYears = $years->sortBy('year')->values();
        $trendLabels = [];
        $trendResponseRate = [];
        $trendEmployedRate = [];

        foreach ($trendYears as $trendYear) {
            $yearGraduates = $this->applyStudentFilters(
                Student::where('academic_year_id', $trendYear->id)->where('status', 'graduated')
            )->count();

            $yearCareerBase = fn () => CareerStatus::where('academic_year_id', $trendYear->id)
                ->where('is_current', true)
                ->whereHas('student', fn ($q) => $this->applyStudentFilters($q));

            $yearRespondents = $yearCareerBase()->distinct()->count('student_id');
            $yearEmployed = $yearCareerBase()->whereIn('status', ['employed', 'entrepreneur'])->count();

            $trendLabels[] = (string) $trendYear->year;
            $trendResponseRate[] = $yearGraduates > 0 ? round($yearRespondents / $yearGraduates * 100, 1) : 0;
            $trendEmployedRate[] = $yearRespondents > 0 ? round($yearEmployed / $yearRespondents * 100, 1) : 0;
        }

        return view('livewire.dashboard', [
            'filterKey' => $this->selectedYearId.'-'.$this->selectedProgram.'-'.$this->selectedDegreeLevel,
            'years' => $years,
            'programs' => $programs,
            'degreeLevels' => $degreeLevels,
            'selectedYear' => $year,
            'stats' => [
                'graduates' => $graduates,
                'respondents' => $respondents,
                'employed' => $employed,
                'further_study' => $furtherStudy,
                'unemployed' => $unemployed,
                'other' => $other,
            ],
            'rates' => [
                'response' => $responseRate,
                'employed' => $employedRate,
            ],
            'metrics' => [
                'avg_salary' => $avgSalary,
                'top_province' => $topProvince,
                'top_company' => $topCompany,
                'related_to_major_rate' => $relatedToMajorRate,
            ],
            'statusChart' => [
                'labels' => ['มีงานทำ', 'ว่างงาน', 'ศึกษาต่อ', 'อื่นๆ'],
                'colors' => ['#2563a8', '#b5484a', '#4fb3a0', '#8b98a5'],
                'data' => [$employed, $unemployed, $furtherStudy, $other],
            ],
            'relatedChart' => [
                'labels' => ['ตรงสาย', 'ไม่ตรงสาย'],
                'colors' => ['#2563a8', '#a67c1f'],
                'data' => [$relatedYes, $relatedNo],
            ],
            'departmentChart' => [
                'labels' => $departmentRows->pluck('program')->all(),
                'employed' => $departmentRows->pluck('employed')->map(fn ($v) => (int) $v)->all(),
                'unemployed' => $departmentRows->pluck('unemployed')->map(fn ($v) => (int) $v)->all(),
                'further_study' => $departmentRows->pluck('further_study')->map(fn ($v) => (int) $v)->all(),
            ],
            'trendChart' => [
                'labels' => $trendLabels,
                'response_rate' => $trendResponseRate,
                'employed_rate' => $trendEmployedRate,
            ],
            'provinceMap' => $provinceRows
                ->filter(fn ($row) => $row->lat !== null && $row->lng !== null)
                ->map(fn ($row) => [
                    'name' => $row->name,
                    'lat' => (float) $row->lat,
                    'lng' => (float) $row->lng,
                    'employed' => (int) $row->employed,
                    'further_study' => (int) $row->further_study,
                    'total' => (int) $row->total,
                ])
                ->values()
                ->all(),
        ]);
    }
}
