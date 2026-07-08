<?php

namespace App\Livewire\Students;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('จัดการข้อมูลนักศึกษา')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $filterAcademicYearId = null;

    public string $filterStatus = '';

    public int $perPage = 15;

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;

    // Form fields
    public string $student_code = '';

    public string $national_id = '';

    public string $prefix = 'นาย';

    public string $first_name = '';

    public string $last_name = '';

    public string $birth_date = '';

    public ?int $academic_year_id = null;

    public string $program = '';

    public string $degree_level = '';

    public string $phone = '';

    public string $email = '';

    public string $line_user_id = '';

    public string $address = '';

    public string $status = 'studying';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAcademicYearId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'filterAcademicYearId', 'filterStatus');
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'student_code' => ['required', 'string', 'max:255', Rule::unique('students', 'student_code')->ignore($this->editingId)],
            'national_id' => ['nullable', 'digits:13', Rule::unique('students', 'national_id')->ignore($this->editingId)],
            'prefix' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'program' => ['nullable', 'string', 'max:255'],
            'degree_level' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'line_user_id' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:studying,graduated,dropped_out'],
        ];
    }

    protected function messages(): array
    {
        return [
            'student_code.unique' => 'รหัสนักศึกษานี้มีอยู่ในระบบแล้ว',
            'national_id.unique' => 'เลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว',
            'national_id.digits' => 'เลขบัตรประชาชนต้องมี 13 หลัก',
        ];
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->hasRole(UserRole::Admin, UserRole::DepartmentHead), 403);
    }

    public function openCreateModal(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorizeManage();

        $student = Student::findOrFail($id);

        $this->editingId = $student->id;
        $this->student_code = $student->student_code;
        $this->national_id = (string) $student->national_id;
        $this->prefix = (string) $student->prefix;
        $this->first_name = $student->first_name;
        $this->last_name = $student->last_name;
        $this->birth_date = $student->birth_date?->toDateString() ?? '';
        $this->academic_year_id = $student->academic_year_id;
        $this->program = (string) $student->program;
        $this->degree_level = (string) $student->degree_level;
        $this->phone = (string) $student->phone;
        $this->email = (string) $student->email;
        $this->line_user_id = (string) $student->line_user_id;
        $this->address = (string) $student->address;
        $this->status = $student->status;

        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->authorizeManage();

        $validated = $this->validate();
        $validated['national_id'] = $validated['national_id'] !== '' ? $validated['national_id'] : null;
        $validated['line_user_id'] = $validated['line_user_id'] !== '' ? $validated['line_user_id'] : null;
        $validated['birth_date'] = $validated['birth_date'] !== '' ? $validated['birth_date'] : null;

        if ($this->editingId) {
            Student::findOrFail($this->editingId)->update($validated);
            session()->flash('success', 'บันทึกการแก้ไขข้อมูลนักศึกษาเรียบร้อยแล้ว');
        } else {
            Student::create($validated);
            session()->flash('success', 'เพิ่มข้อมูลนักศึกษาเรียบร้อยแล้ว');
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeManage();
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $this->authorizeManage();

        if ($this->confirmingDeleteId) {
            Student::findOrFail($this->confirmingDeleteId)->delete();
            session()->flash('success', 'ลบข้อมูลนักศึกษาเรียบร้อยแล้ว');
        }

        $this->confirmingDeleteId = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'student_code', 'national_id', 'prefix', 'first_name', 'last_name', 'birth_date',
            'academic_year_id', 'program', 'degree_level', 'phone', 'email', 'line_user_id', 'address', 'status',
        ]);
        $this->prefix = 'นาย';
        $this->status = 'studying';
        $this->resetErrorBag();
    }

    public function render()
    {
        $students = Student::query()
            ->with('academicYear')
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
            ->when($this->filterStatus, fn ($query) => $query->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.students.index', [
            'students' => $students,
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'canManage' => auth()->user()->hasRole(UserRole::Admin, UserRole::DepartmentHead),
        ]);
    }
}
