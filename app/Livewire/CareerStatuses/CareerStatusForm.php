<?php

namespace App\Livewire\CareerStatuses;

use App\Enums\CareerStatusType;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('บันทึกภาวะการมีงานทำ')]
class CareerStatusForm extends Component
{
    public string $studentSearch = '';

    public ?int $selectedStudentId = null;

    public ?int $academic_year_id = null;

    public string $status = '';

    public string $effective_date;

    public string $company_name = '';

    public string $position = '';

    public string $monthly_salary = '';

    public string $employment_type = 'full_time';

    public string $work_location = '';

    public bool $is_related_to_major = false;

    public string $notes = '';

    public function mount(): void
    {
        $this->effective_date = now()->toDateString();
        $this->academic_year_id = AcademicYear::where('is_active', true)->value('id');
    }

    private function authorizeSubmit(): void
    {
        abort_unless(
            auth()->user()->hasRole(UserRole::Admin, UserRole::Teacher, UserRole::DepartmentHead),
            403
        );
    }

    public function updatingStudentSearch(): void
    {
        $this->selectedStudentId = null;
    }

    public function selectStudent(int $studentId): void
    {
        $this->selectedStudentId = $studentId;
        $this->studentSearch = '';
    }

    public function clearStudent(): void
    {
        $this->selectedStudentId = null;
        $this->reset(['status', 'company_name', 'position', 'monthly_salary', 'employment_type', 'work_location', 'is_related_to_major', 'notes']);
        $this->employment_type = 'full_time';
    }

    public function updatedStatus(): void
    {
        // Clear employment-only fields when switching away from a working status,
        // so stale data never gets silently submitted for an unrelated status.
        if (! $this->isWorkingStatus()) {
            $this->reset(['company_name', 'position', 'monthly_salary', 'work_location']);
            $this->is_related_to_major = false;
        }
    }

    private function isWorkingStatus(): bool
    {
        return in_array($this->status, [CareerStatusType::Employed->value, CareerStatusType::Entrepreneur->value], true);
    }

    protected function rules(): array
    {
        $rules = [
            'selectedStudentId' => ['required', 'exists:students,id'],
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

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'selectedStudentId.required' => 'กรุณาเลือกนักศึกษา',
            'company_name.required' => 'กรุณากรอกชื่อบริษัท/กิจการ',
        ];
    }

    public function save(): void
    {
        $this->authorizeSubmit();

        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            CareerStatus::where('student_id', $validated['selectedStudentId'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->update(['is_current' => false]);

            CareerStatus::create([
                'student_id' => $validated['selectedStudentId'],
                'academic_year_id' => $validated['academic_year_id'],
                'status' => $validated['status'],
                'company_name' => $validated['company_name'] ?? null,
                'position' => $validated['position'] ?? null,
                'monthly_salary' => ($validated['monthly_salary'] ?? '') !== '' ? $validated['monthly_salary'] : null,
                'employment_type' => $this->isWorkingStatus() ? $validated['employment_type'] : null,
                'work_location' => $validated['work_location'] ?? null,
                'is_related_to_major' => $this->isWorkingStatus() ? $this->is_related_to_major : null,
                'effective_date' => $validated['effective_date'],
                'source' => 'manual',
                'is_current' => true,
                'verified_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        session()->flash('success', 'บันทึกภาวะการมีงานทำเรียบร้อยแล้ว');

        $this->reset(['studentSearch', 'selectedStudentId', 'status', 'company_name', 'position', 'monthly_salary', 'work_location', 'is_related_to_major', 'notes']);
        $this->employment_type = 'full_time';
        $this->effective_date = now()->toDateString();
    }

    public function render()
    {
        $matchingStudents = collect();

        if (! $this->selectedStudentId && mb_strlen(trim($this->studentSearch)) >= 2) {
            $like = '%'.trim($this->studentSearch).'%';

            $matchingStudents = Student::query()
                ->where(function ($query) use ($like) {
                    $query->where('student_code', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like);
                })
                ->limit(8)
                ->get();
        }

        return view('livewire.career-statuses.career-status-form', [
            'matchingStudents' => $matchingStudents,
            'selectedStudent' => $this->selectedStudentId ? Student::find($this->selectedStudentId) : null,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'statuses' => CareerStatusType::cases(),
            'isWorkingStatus' => $this->isWorkingStatus(),
        ]);
    }
}
