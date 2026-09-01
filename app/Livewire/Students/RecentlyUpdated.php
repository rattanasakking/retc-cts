<?php

namespace App\Livewire\Students;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\CareerStatus;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('ข้อมูลนักศึกษาที่ปรับปรุงล่าสุด')]
class RecentlyUpdated extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $filterAcademicYearId = null;

    /** '' = ทุกแหล่ง, 'student' = แก้ที่ประวัตินักศึกษา, 'career_status' = แก้ที่ภาวะการมีงานทำ */
    public string $filterSource = '';

    /** ย้อนหลังกี่วัน (0 = ไม่จำกัดช่วงเวลา) */
    public int $days = 30;

    public int $perPage = 15;

    /** นักศึกษาที่กำลังเปิดดูใน popup (null = ปิดอยู่) */
    public ?int $viewingId = null;

    public function openDetail(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetail(): void
    {
        $this->viewingId = null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAcademicYearId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSource(): void
    {
        $this->resetPage();
    }

    public function updatingDays(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'filterAcademicYearId', 'filterSource', 'days');
        $this->resetPage();
    }

    /**
     * เวลาที่ภาวะการมีงานทำของนักศึกษาคนนั้นถูกแก้ไขล่าสุด (null ถ้ายังไม่เคยบันทึก)
     */
    private function careerUpdatedSql(): string
    {
        return '(select max(career_statuses.updated_at) from career_statuses where career_statuses.student_id = students.id)';
    }

    /**
     * "ปรับปรุงล่าสุด" = เวลาที่ใหม่กว่าระหว่างแถวนักศึกษาเองกับภาวะการมีงานทำของเขา
     * เขียนด้วย CASE WHEN แทน GREATEST()/MAX() หลายอาร์กิวเมนต์ เพราะ MySQL กับ
     * SQLite (ที่ใช้ตอนรันเทสต์) รองรับฟังก์ชันคนละตัวกัน แต่ CASE ใช้ได้ทั้งคู่
     */
    private function lastUpdatedSql(): string
    {
        $career = $this->careerUpdatedSql();

        return "(case when {$career} is not null and {$career} > students.updated_at then {$career} else students.updated_at end)";
    }

    private function baseQuery()
    {
        $career = $this->careerUpdatedSql();
        $lastUpdated = $this->lastUpdatedSql();

        return Student::query()
            ->with('academicYear')
            ->select('students.*')
            ->selectRaw("{$lastUpdated} as last_updated_at")
            ->selectRaw("{$career} as career_updated_at")
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('student_code', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('national_id', 'like', $term);
                });
            })
            ->when($this->filterAcademicYearId, fn ($query) => $query->where('academic_year_id', $this->filterAcademicYearId))
            ->when($this->days > 0, fn ($query) => $query->whereRaw("{$lastUpdated} >= ?", [now()->subDays($this->days)]))
            ->when(
                $this->filterSource === 'career_status',
                fn ($query) => $query->whereRaw("{$career} is not null and {$career} > students.updated_at")
            )
            ->when(
                $this->filterSource === 'student',
                fn ($query) => $query->whereRaw("({$career} is null or students.updated_at >= {$career})")
            );
    }

    private function countUpdatedSince(CarbonInterface $since): int
    {
        return Student::query()
            ->whereRaw($this->lastUpdatedSql().' >= ?', [$since])
            ->count();
    }

    /**
     * ผู้แก้ไขล่าสุดของนักศึกษาแต่ละคนในหน้านี้ ดึงมาเป็นก้อนเดียวเพื่อเลี่ยง N+1
     * โดยนับทั้ง log ของตัวนักศึกษาเองและของภาวะการมีงานทำที่เป็นของเขา
     *
     * @param  array<int, int>  $studentIds
     * @return array<int, array{name: string, detail: string, self_reported: bool}>
     */
    private function latestEditorsFor(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        $studentType = (new Student)->getMorphClass();
        $careerType = (new CareerStatus)->getMorphClass();

        // เก็บ source มาด้วย เพราะ log ที่นักศึกษาแจ้งเองผ่านหน้าสาธารณะไม่มี
        // user_id (ไม่ได้ล็อกอิน) แยกจาก log ที่ระบบเขียนเองไม่ได้ถ้าดูแค่ log
        $careerStatuses = CareerStatus::whereIn('student_id', $studentIds)->get(['id', 'student_id', 'source']);
        $careerOwners = $careerStatuses->pluck('student_id', 'id');
        $careerSources = $careerStatuses->pluck('source', 'id');

        $logs = AuditLog::query()
            ->with('user')
            ->where(function ($query) use ($studentType, $studentIds, $careerType, $careerOwners) {
                $query->where(function ($q) use ($studentType, $studentIds) {
                    $q->where('auditable_type', $studentType)->whereIn('auditable_id', $studentIds);
                })->orWhere(function ($q) use ($careerType, $careerOwners) {
                    $q->where('auditable_type', $careerType)->whereIn('auditable_id', $careerOwners->keys());
                });
            })
            // id เป็นตัวตัดสินรอง เพราะหลาย log อาจถูกเขียนในวินาทีเดียวกัน
            // (เช่น สร้างนักศึกษาแล้วแก้ไขต่อทันที) แล้วลำดับจะสลับกันได้
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $latest = [];

        foreach ($logs as $log) {
            $isCareerLog = $log->auditable_type === $careerType;

            $studentId = $isCareerLog
                ? ($careerOwners[$log->auditable_id] ?? null)
                : $log->auditable_id;

            if (! $studentId || isset($latest[$studentId])) {
                continue;
            }

            $selfReported = $isCareerLog
                && ! $log->user
                && ($careerSources[$log->auditable_id] ?? null) === 'self_report';

            $latest[$studentId] = [
                'name' => $selfReported ? 'นักศึกษาแจ้งด้วยตนเอง' : ($log->user?->name ?? 'ระบบ'),
                'detail' => $selfReported
                    ? 'ผ่านหน้าแจ้งข้อมูลด้วยตนเอง'
                    : $log->action->label().' — '.$log->module,
                'self_reported' => $selfReported,
            ];
        }

        return $latest;
    }

    /**
     * "3 ชั่วโมงที่แล้ว" ฯลฯ — เขียนเองเพราะ locale ของแอปเป็น en
     * diffForHumans() จึงคืนข้อความภาษาอังกฤษ
     */
    private function humanDiff(Carbon $moment): string
    {
        $minutes = $moment->diffInMinutes(now());

        return match (true) {
            $minutes < 1 => 'เมื่อสักครู่',
            $minutes < 60 => floor($minutes).' นาทีที่แล้ว',
            $minutes < 1440 => floor($minutes / 60).' ชั่วโมงที่แล้ว',
            $minutes < 43200 => floor($minutes / 1440).' วันที่แล้ว',
            default => floor($minutes / 43200).' เดือนที่แล้ว',
        };
    }

    public function render()
    {
        $students = $this->baseQuery()
            ->orderByDesc('last_updated_at')
            ->paginate($this->perPage);

        // คอลัมน์คำนวณกลับมาเป็นสตริง (ไม่ได้อยู่ใน $casts ของโมเดล) จึงแปลงเป็น Carbon ให้วิวใช้ต่อได้เลย
        $students->getCollection()->each(function (Student $student) {
            $student->last_updated_at = Carbon::parse($student->last_updated_at);
            $student->career_updated_at = $student->career_updated_at ? Carbon::parse($student->career_updated_at) : null;
            $student->last_updated_human = $this->humanDiff($student->last_updated_at);
        });

        // นักศึกษาใน popup หยิบจากหน้าที่แสดงอยู่ ไม่ query ซ้ำ — ได้ last_updated_at
        // ที่คำนวณไว้แล้วติดมาด้วย และถ้าแถวนั้นหลุดจากตัวกรองไปแล้ว popup ก็ปิดเอง
        $viewingStudent = $this->viewingId
            ? $students->getCollection()->firstWhere('id', $this->viewingId)
            : null;

        $viewingStudent?->load(['careerStatuses' => fn ($query) => $query
            ->with(['academicYear', 'workProvince', 'workDistrict', 'workSubdistrict', 'verifiedBy'])
            ->orderByDesc('effective_date')]);

        return view('livewire.students.recently-updated', [
            'students' => $students,
            'viewingStudent' => $viewingStudent,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'editors' => $this->latestEditorsFor($students->getCollection()->pluck('id')->all()),
            'updatedTodayCount' => $this->countUpdatedSince(now()->subDay()),
            'updatedThisWeekCount' => $this->countUpdatedSince(now()->subWeek()),
            'updatedThisMonthCount' => $this->countUpdatedSince(now()->subDays(30)),
        ]);
    }
}
