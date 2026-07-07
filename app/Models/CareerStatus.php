<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\CareerStatusType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerStatus extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'status',
        'company_name',
        'position',
        'monthly_salary',
        'employment_type',
        'work_location',
        'is_related_to_major',
        'effective_date',
        'source',
        'is_current',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'is_related_to_major' => 'boolean',
            'effective_date' => 'date',
            'is_current' => 'boolean',
            'status' => CareerStatusType::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function auditModule(): string
    {
        return 'ภาวะการมีงานทำ';
    }

    public function auditLabel(): string
    {
        $student = $this->student;
        $studentLabel = $student
            ? "{$student->first_name} {$student->last_name} ({$student->student_code})"
            : "นักศึกษา #{$this->student_id}";

        $statusLabel = $this->status instanceof CareerStatusType ? $this->status->label() : $this->status;

        return "{$studentLabel} — {$statusLabel}";
    }
}
