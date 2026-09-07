<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Student extends Model
{
    use Auditable, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'academic_year_id',
        'student_code',
        'national_id',
        'prefix',
        'first_name',
        'last_name',
        'birth_date',
        'program',
        'degree_level',
        'phone',
        'email',
        'line_user_id',
        'address',
        'graduated_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'graduated_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function careerStatuses(): HasMany
    {
        return $this->hasMany(CareerStatus::class);
    }

    public function currentCareerStatus(): HasOne
    {
        return $this->hasOne(CareerStatus::class)->where('is_current', true);
    }

    /**
     * The most recently touched career status, whichever academic year it
     * belongs to — "when did this student last tell us anything", where
     * currentCareerStatus() answers "what is true for this survey round".
     */
    public function latestCareerStatus(): HasOne
    {
        return $this->hasOne(CareerStatus::class)->latestOfMany('updated_at');
    }

    /**
     * Route notifications for the LineChannel — returns the LINE user ID
     * to push-message, or null to make the channel skip this recipient.
     */
    public function routeNotificationForLine(): ?string
    {
        return $this->line_user_id;
    }

    public function auditModule(): string
    {
        return 'นักศึกษา';
    }

    public function auditLabel(): string
    {
        return "{$this->first_name} {$this->last_name} ({$this->student_code})";
    }
}
