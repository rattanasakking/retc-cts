<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'year',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function careerStatuses(): HasMany
    {
        return $this->hasMany(CareerStatus::class);
    }

    public function auditModule(): string
    {
        return 'ปีการศึกษา';
    }

    public function auditLabel(): string
    {
        return "ปีการศึกษา {$this->year}";
    }
}
