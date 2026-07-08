<?php

namespace App\Livewire\Students;

use App\Enums\UserRole;
use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public Student $student;

    public function mount(Student $student): void
    {
        $this->student = $student;
    }

    #[Title('ข้อมูลนักศึกษา')]
    public function render()
    {
        $careerStatuses = $this->student->careerStatuses()
            ->with(['academicYear', 'workProvince', 'workDistrict', 'workSubdistrict', 'verifiedBy'])
            ->orderByDesc('effective_date')
            ->get();

        return view('livewire.students.show', [
            'careerStatuses' => $careerStatuses,
            'canManage' => auth()->user()->hasRole(UserRole::Admin, UserRole::DepartmentHead),
        ]);
    }
}
