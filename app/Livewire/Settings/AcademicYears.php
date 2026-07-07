<?php

namespace App\Livewire\Settings;

use App\Models\AcademicYear;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ตั้งค่า: ปีการศึกษา')]
class AcademicYears extends Component
{
    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;

    public ?string $deleteError = null;

    public string $year = '';

    public string $start_date = '';

    public string $end_date = '';

    public bool $is_active = false;

    protected function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2500', 'max:2700', Rule::unique('academic_years', 'year')->ignore($this->editingId)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'year.unique' => 'ปีการศึกษานี้มีอยู่ในระบบแล้ว',
            'end_date.after_or_equal' => 'วันที่สิ้นสุดต้องไม่ก่อนวันที่เริ่มต้น',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $year = AcademicYear::findOrFail($id);

        $this->editingId = $year->id;
        $this->year = (string) $year->year;
        $this->start_date = $year->start_date?->toDateString() ?? '';
        $this->end_date = $year->end_date?->toDateString() ?? '';
        $this->is_active = $year->is_active;

        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($validated['is_active']) {
            // Only one academic year may be active at a time.
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        if ($this->editingId) {
            AcademicYear::findOrFail($this->editingId)->update($validated);
            session()->flash('success', 'บันทึกการแก้ไขปีการศึกษาเรียบร้อยแล้ว');
        } else {
            AcademicYear::create($validated);
            session()->flash('success', 'เพิ่มปีการศึกษาเรียบร้อยแล้ว');
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
        $this->confirmingDeleteId = $id;
        $this->deleteError = null;
    }

    public function delete(): void
    {
        if (! $this->confirmingDeleteId) {
            return;
        }

        try {
            AcademicYear::findOrFail($this->confirmingDeleteId)->delete();
            $this->confirmingDeleteId = null;
            session()->flash('success', 'ลบปีการศึกษาเรียบร้อยแล้ว');
        } catch (QueryException $e) {
            // Foreign key RESTRICT: students/career_statuses still reference this year.
            $this->deleteError = 'ไม่สามารถลบปีการศึกษานี้ได้ เนื่องจากมีข้อมูลนักศึกษาหรือภาวะการมีงานทำผูกอยู่';
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'year', 'start_date', 'end_date', 'is_active']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.settings.academic-years', [
            'years' => AcademicYear::orderByDesc('year')->get(),
        ]);
    }
}
