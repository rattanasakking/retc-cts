<?php

namespace App\Livewire\Public;

use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
#[Title('ค้นหาข้อมูลนักศึกษา')]
class StudentSearch extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $term = trim($this->search);
        $hasSearched = mb_strlen($term) >= 2;

        $students = null;

        if ($hasSearched) {
            $like = '%'.$term.'%';

            $students = Student::query()
                ->with('academicYear')
                ->where(function ($query) use ($like) {
                    $query->where('student_code', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like);
                })
                ->orderBy('first_name')
                ->paginate(10);
        }

        return view('livewire.public.student-search', [
            'students' => $students,
            'hasSearched' => $hasSearched,
            'term' => $term,
        ]);
    }
}
