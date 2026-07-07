<?php

namespace App\Exports;

use App\Enums\CareerStatusType;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly int $academicYearId,
        private readonly ?string $program = null,
        private readonly ?string $degreeLevel = null,
    ) {
    }

    public function query(): Builder
    {
        return Student::query()
            ->with(['academicYear', 'careerStatuses' => function ($query) {
                $query->where('academic_year_id', $this->academicYearId)->where('is_current', true);
            }])
            ->where('academic_year_id', $this->academicYearId)
            ->when($this->program, fn ($query) => $query->where('program', $this->program))
            ->when($this->degreeLevel, fn ($query) => $query->where('degree_level', $this->degreeLevel))
            ->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'รหัสนักศึกษา', 'คำนำหน้า', 'ชื่อ', 'นามสกุล', 'แผนกวิชา', 'ระดับ',
            'ปีการศึกษา', 'สถานะนักศึกษา', 'ภาวะการมีงานทำ', 'สถานที่ทำงาน/กิจการ', 'ตำแหน่ง', 'เงินเดือน (บาท)',
        ];
    }

    public function map($student): array
    {
        $career = $student->careerStatuses->first();

        return [
            $student->student_code,
            $student->prefix,
            $student->first_name,
            $student->last_name,
            $student->program,
            $student->degree_level,
            $student->academicYear?->year,
            match ($student->status) {
                'studying' => 'กำลังศึกษา',
                'graduated' => 'จบการศึกษา',
                'dropped_out' => 'ออกกลางคัน',
                default => $student->status,
            },
            $career?->status instanceof CareerStatusType ? $career->status->label() : 'ยังไม่ตอบแบบสำรวจ',
            $career?->company_name,
            $career?->position,
            $career?->monthly_salary,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
