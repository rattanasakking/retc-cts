<?php

namespace App\Livewire\Notifications;

use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use App\Notifications\StudentSurveyReminder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('แจ้งเตือนนักศึกษา')]
class SendReminders extends Component
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
        ];
    }

    /**
     * Graduated students in the selected year/filters who have no
     * is_current career_status record for that year yet.
     */
    private function nonResponders()
    {
        $respondedIds = CareerStatus::where('academic_year_id', $this->academicYearId)
            ->where('is_current', true)
            ->pluck('student_id');

        return Student::where('academic_year_id', $this->academicYearId)
            ->where('status', 'graduated')
            ->whereNotIn('id', $respondedIds)
            ->when($this->program, fn ($q) => $q->where('program', $this->program))
            ->when($this->degreeLevel, fn ($q) => $q->where('degree_level', $this->degreeLevel));
    }

    public function sendReminders(): void
    {
        $this->validate();

        $year = AcademicYear::findOrFail($this->academicYearId);
        $students = $this->nonResponders()->get();

        foreach ($students as $student) {
            $student->notify(new StudentSurveyReminder($year));
        }

        session()->flash('success', "ส่งการแจ้งเตือนไปยังนักศึกษา {$students->count()} คนเรียบร้อยแล้ว (ประมวลผลผ่านคิวเบื้องหลัง)");
    }

    public function render()
    {
        return view('livewire.notifications.send-reminders', [
            'academicYears' => AcademicYear::orderByDesc('year')->get(),
            'programs' => Student::query()->whereNotNull('program')->distinct()->orderBy('program')->pluck('program'),
            'degreeLevels' => Student::query()->whereNotNull('degree_level')->distinct()->orderBy('degree_level')->pluck('degree_level'),
            'nonResponderCount' => $this->academicYearId ? $this->nonResponders()->count() : 0,
        ]);
    }
}
