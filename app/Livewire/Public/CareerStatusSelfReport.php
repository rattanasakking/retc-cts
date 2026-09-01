<?php

namespace App\Livewire\Public;

use App\Enums\CareerStatusType;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Models\ThaiDistrict;
use App\Models\ThaiProvince;
use App\Models\ThaiSubdistrict;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Public self-service flow for a student to report their own career status,
 * with no login: search by name -> pick themselves from the results ->
 * confirm identity with their date of birth -> fill in the same status
 * details staff would otherwise enter on their behalf.
 *
 * "Verification" here is intentionally lightweight (name + birth date, like
 * many Thai government result-lookup portals) — it's a friction step against
 * casual mistakes, not a security boundary, and the route is rate-limited.
 */
#[Layout('components.layouts.public')]
#[Title('แจ้งข้อมูลภาวะการมีงานทำ')]
class CareerStatusSelfReport extends Component
{
    public string $step = 'search';

    public string $search = '';

    public ?int $candidateId = null;

    public string $birthDateInput = '';

    public ?int $verifiedStudentId = null;

    public ?int $academic_year_id = null;

    public string $status = '';

    public string $effective_date;

    public string $company_name = '';

    public string $position = '';

    public string $monthly_salary = '';

    public string $employment_type = 'full_time';

    public string $work_location = '';

    public ?int $work_province_id = null;

    public ?int $work_district_id = null;

    public ?int $work_subdistrict_id = null;

    public string $institution_name = '';

    public bool $is_related_to_major = false;

    public string $notes = '';

    /** ชื่อสถานที่ที่ระบบดึงที่ตั้งมาให้อัตโนมัติ (ว่าง = ไม่ได้ดึงให้) */
    public string $autofilledLocationFor = '';

    public function mount(): void
    {
        $this->effective_date = now()->toDateString();
        $this->academic_year_id = AcademicYear::where('is_active', true)->value('id');
    }

    public function updatingSearch(): void
    {
        $this->candidateId = null;
    }

    public function selectCandidate(int $studentId): void
    {
        $this->candidateId = $studentId;
        $this->birthDateInput = '';
        $this->resetErrorBag();
        $this->step = 'verify';
    }

    public function backToSearch(): void
    {
        $this->candidateId = null;
        $this->birthDateInput = '';
        $this->step = 'search';
    }

    public function verify(): void
    {
        $this->validate([
            'birthDateInput' => ['required', 'date'],
        ], [
            'birthDateInput.required' => 'กรุณาเลือกวันเดือนปีเกิด',
        ]);

        $student = Student::find($this->candidateId);

        // Same generic message either way — doesn't confirm whether the
        // student record itself exists, only that name+birthdate matched.
        if (! $student || ! $student->birth_date || ! $student->birth_date->isSameDay($this->birthDateInput)) {
            $this->addError('birthDateInput', 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบชื่อและวันเดือนปีเกิดอีกครั้ง');

            return;
        }

        $this->verifiedStudentId = $student->id;
        $this->step = 'form';
    }

    public function backToVerify(): void
    {
        $this->verifiedStudentId = null;
        $this->step = 'verify';
    }

    private function isWorkingStatus(): bool
    {
        return in_array($this->status, [CareerStatusType::Employed->value, CareerStatusType::Entrepreneur->value], true);
    }

    private function isFurtherStudy(): bool
    {
        return $this->status === CareerStatusType::FurtherStudy->value;
    }

    private function needsLocation(): bool
    {
        return $this->isWorkingStatus() || $this->isFurtherStudy();
    }

    public function updatedStatus(): void
    {
        if (! $this->isWorkingStatus()) {
            $this->reset(['company_name', 'position', 'monthly_salary', 'work_location']);
            $this->is_related_to_major = false;
        }

        if (! $this->needsLocation()) {
            $this->reset(['work_province_id', 'work_district_id', 'work_subdistrict_id']);
        }

        if (! $this->isFurtherStudy()) {
            $this->institution_name = '';
        }

        $this->autofilledLocationFor = '';
    }

    public function updatedWorkProvinceId(): void
    {
        $this->reset(['work_district_id', 'work_subdistrict_id']);
        $this->autofilledLocationFor = '';
    }

    public function updatedInstitutionName(): void
    {
        $this->applyKnownLocation($this->institution_name, 'institution_name');
    }

    public function updatedCompanyName(): void
    {
        $this->applyKnownLocation($this->company_name, 'company_name');
    }

    /**
     * เติมที่ตั้งให้อัตโนมัติเมื่อชื่อสถานที่ที่กรอกตรงกับที่เคยมีในระบบแล้ว —
     * สถานศึกษาและบริษัทเดิมถูกกรอกซ้ำกันเป็นสิบ ๆ ครั้ง คนกรอกจึงไม่ต้องไล่
     * เลือกจังหวัด/อำเภอ/ตำบลใหม่ทุกรอบ
     *
     * ไม่เขียนทับสิ่งที่ผู้ใช้เลือกเอง (ถ้าเลือกจังหวัดไว้แล้วจะไม่ยุ่ง) และค่า
     * ที่เติมให้แก้ไขได้ตามปกติ เผื่อสาขาของที่เดียวกันอยู่คนละจังหวัด
     */
    private function applyKnownLocation(string $name, string $column): void
    {
        $name = trim($name);
        $this->autofilledLocationFor = '';

        if ($name === '' || $this->work_province_id) {
            return;
        }

        $match = CareerStatus::query()
            ->where($column, $name)
            ->whereNotNull('work_province_id')
            ->latest('id')
            ->first();

        if (! $match) {
            return;
        }

        $this->work_province_id = $match->work_province_id;
        $this->work_district_id = $match->work_district_id;
        $this->work_subdistrict_id = $match->work_subdistrict_id;

        if ($column === 'company_name' && trim($this->work_location) === '' && $match->work_location) {
            $this->work_location = $match->work_location;
        }

        $this->autofilledLocationFor = $name;
    }

    public function updatedWorkDistrictId(): void
    {
        $this->work_subdistrict_id = null;
    }

    protected function rules(): array
    {
        $rules = [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'status' => ['required', 'in:'.implode(',', array_column(CareerStatusType::cases(), 'value'))],
            'effective_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->isWorkingStatus()) {
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['position'] = ['nullable', 'string', 'max:255'];
            $rules['monthly_salary'] = ['nullable', 'numeric', 'min:0', 'max:9999999.99'];
            $rules['employment_type'] = ['required', 'in:full_time,part_time,contract'];
            $rules['work_location'] = ['nullable', 'string', 'max:255'];
        }

        if ($this->needsLocation()) {
            $rules['work_province_id'] = ['nullable', 'exists:thai_provinces,id'];
            $rules['work_district_id'] = ['nullable', 'exists:thai_districts,id'];
            $rules['work_subdistrict_id'] = ['nullable', 'exists:thai_subdistricts,id'];
        }

        if ($this->isFurtherStudy()) {
            $rules['institution_name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'company_name.required' => 'กรุณากรอกชื่อบริษัท/กิจการ',
            'institution_name.required' => 'กรุณากรอกชื่อสถานศึกษาต่อ',
        ];
    }

    public function submit(): void
    {
        // Re-checked on submit, not just at the verify step — Livewire
        // component state survives across requests, but nothing stops
        // someone from tampering with it between steps.
        abort_unless($this->verifiedStudentId, 403);

        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            CareerStatus::where('student_id', $this->verifiedStudentId)
                ->where('academic_year_id', $validated['academic_year_id'])
                ->update(['is_current' => false]);

            CareerStatus::create([
                'student_id' => $this->verifiedStudentId,
                'academic_year_id' => $validated['academic_year_id'],
                'status' => $validated['status'],
                'company_name' => $validated['company_name'] ?? null,
                'position' => $validated['position'] ?? null,
                'monthly_salary' => ($validated['monthly_salary'] ?? '') !== '' ? $validated['monthly_salary'] : null,
                'employment_type' => $this->isWorkingStatus() ? $validated['employment_type'] : null,
                'work_location' => $validated['work_location'] ?? null,
                'work_province_id' => $this->needsLocation() ? ($validated['work_province_id'] ?? null) : null,
                'work_district_id' => $this->needsLocation() ? ($validated['work_district_id'] ?? null) : null,
                'work_subdistrict_id' => $this->needsLocation() ? ($validated['work_subdistrict_id'] ?? null) : null,
                'institution_name' => $this->isFurtherStudy() ? ($validated['institution_name'] ?? null) : null,
                'is_related_to_major' => $this->isWorkingStatus() ? $this->is_related_to_major : null,
                'effective_date' => $validated['effective_date'],
                'source' => 'self_report',
                'is_current' => true,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $this->step = 'done';
    }

    public function render()
    {
        $candidates = collect();

        if ($this->step === 'search' && mb_strlen(trim($this->search)) >= 2) {
            $like = '%'.trim($this->search).'%';

            $candidates = Student::query()
                ->with('academicYear')
                ->where(function ($query) use ($like) {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like);
                })
                ->orderBy('first_name')
                ->limit(10)
                ->get();
        }

        return view('livewire.public.career-status-self-report', [
            'candidates' => $candidates,
            'candidate' => $this->candidateId ? Student::find($this->candidateId) : null,
            'verifiedStudent' => $this->verifiedStudentId ? Student::find($this->verifiedStudentId) : null,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'statuses' => CareerStatusType::cases(),
            'isWorkingStatus' => $this->isWorkingStatus(),
            'isFurtherStudy' => $this->isFurtherStudy(),
            'needsLocation' => $this->needsLocation(),
            'institutionSuggestions' => $this->isFurtherStudy()
                ? CareerStatus::whereNotNull('institution_name')
                    ->distinct()
                    ->orderBy('institution_name')
                    ->limit(200)
                    ->pluck('institution_name')
                : collect(),
            'companySuggestions' => $this->isWorkingStatus()
                ? CareerStatus::whereNotNull('company_name')
                    ->distinct()
                    ->orderBy('company_name')
                    ->limit(200)
                    ->pluck('company_name')
                : collect(),
            'provinces' => ThaiProvince::orderBy('name_th')->get(),
            'districts' => $this->work_province_id
                ? ThaiDistrict::where('province_id', $this->work_province_id)->orderBy('name_th')->get()
                : collect(),
            'subdistricts' => $this->work_district_id
                ? ThaiSubdistrict::where('district_id', $this->work_district_id)->orderBy('name_th')->get()
                : collect(),
        ]);
    }
}
