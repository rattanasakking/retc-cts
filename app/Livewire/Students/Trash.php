<?php

namespace App\Livewire\Students;

use App\Enums\UserRole;
use App\Models\Student;
use App\Support\AuditLogger;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('ถังขยะ - นักศึกษา')]
class Trash extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $confirmingForceDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->hasRole(UserRole::Admin, UserRole::DepartmentHead), 403);
    }

    public function restore(int $id): void
    {
        $this->authorizeManage();

        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();

        AuditLogger::log(
            action: 'restore',
            module: 'นักศึกษา',
            auditable: $student,
            description: "กู้คืนข้อมูลนักศึกษา {$student->first_name} {$student->last_name} ({$student->student_code})",
        );

        session()->flash('success', 'กู้คืนข้อมูลนักศึกษาเรียบร้อยแล้ว');
    }

    public function confirmForceDelete(int $id): void
    {
        $this->authorizeManage();
        $this->confirmingForceDeleteId = $id;
    }

    public function forceDelete(): void
    {
        $this->authorizeManage();

        if ($this->confirmingForceDeleteId) {
            $student = Student::onlyTrashed()->findOrFail($this->confirmingForceDeleteId);

            // Logged before the actual purge (not just relying on
            // Auditable's automatic "delete" hook, which still fires here
            // too) so the audit trail explicitly says this was a permanent
            // purge, not just another soft delete, and that the student
            // code is free again.
            AuditLogger::log(
                action: 'force_delete',
                module: 'นักศึกษา',
                auditable: $student,
                oldValues: $student->attributesToArray(),
                description: "ลบข้อมูลนักศึกษาถาวร {$student->first_name} {$student->last_name} (รหัส {$student->student_code} จะสามารถนำมาใช้ซ้ำได้)",
            );

            $student->forceDelete();
            session()->flash('success', 'ลบข้อมูลนักศึกษาถาวรเรียบร้อยแล้ว');
        }

        $this->confirmingForceDeleteId = null;
    }

    public function render()
    {
        $students = Student::onlyTrashed()
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
            ->orderByDesc('deleted_at')
            ->paginate(15);

        return view('livewire.students.trash', [
            'students' => $students,
        ]);
    }
}
