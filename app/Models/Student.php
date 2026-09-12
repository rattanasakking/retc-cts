<?php

namespace App\Models;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * เวลาที่ภาวะการมีงานทำของนักศึกษาคนนั้นถูกแก้ไขล่าสุด (null ถ้ายังไม่เคยบันทึก)
     * เป็น SQL ดิบเพื่อให้ใช้ได้ทั้งใน select และ where ของ query เดียวกัน
     */
    public static function careerUpdatedSql(): string
    {
        return '(select max(career_statuses.updated_at) from career_statuses where career_statuses.student_id = students.id)';
    }

    /**
     * "ปรับปรุงล่าสุด" = เวลาที่ใหม่กว่าระหว่างแถวนักศึกษาเองกับภาวะการมีงานทำของเขา
     * เขียนด้วย CASE WHEN แทน GREATEST()/MAX() หลายอาร์กิวเมนต์ เพราะ MySQL กับ
     * SQLite (ที่ใช้ตอนรันเทสต์) รองรับฟังก์ชันคนละตัวกัน แต่ CASE ใช้ได้ทั้งคู่
     */
    public static function lastUpdatedSql(): string
    {
        $career = static::careerUpdatedSql();

        return "(case when {$career} is not null and {$career} > students.updated_at then {$career} else students.updated_at end)";
    }

    /**
     * เพิ่มคอลัมน์ last_updated_at และ career_updated_at ให้ทุกแถว — หน้าที่แสดง
     * "ปรับปรุงล่าสุด" ใช้นิยามเดียวกันหมด จะได้ไม่มีหน้าไหนบอกเวลาไม่ตรงกัน
     */
    public function scopeWithLastUpdated(Builder $query): Builder
    {
        return $query
            ->select('students.*')
            ->selectRaw(static::lastUpdatedSql().' as last_updated_at')
            ->selectRaw(static::careerUpdatedSql().' as career_updated_at');
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
